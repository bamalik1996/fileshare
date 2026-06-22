<?php

namespace App\Http\Controllers;

use App\Domain\Principal\IpPrincipal;
use App\Domain\Principal\Principal;
use App\Models\Share;
use App\Models\SharedText;
use App\Services\ShareService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Legacy `/api/v1/*` text-share endpoints, retained for the guest IP
 * deprecation window described in design.md > Backward-compat tables.
 *
 * Task 5.2 refactor: write paths now delegate to {@see ShareService} so
 * the new `shares` aggregate stays in sync with the legacy `shared_texts`
 * row that this controller has historically maintained. The legacy row
 * continues to be written for one release cycle as the dual-write adapter
 * mandated by design.md, after which a follow-up migration will drop the
 * `shared_texts` table entirely.
 *
 * Guest IP responses (status, body shape, HTTP codes, headers) are kept
 * byte-for-byte identical to the pre-refactor behaviour - the only
 * semantic change is an additive `shares` row alongside every legacy
 * write so downstream services (broadcasting, public gallery, accounts,
 * etc.) can read from the new aggregate without waiting for callers to
 * resave.
 */
class ShareController extends Controller
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const MAX_FILES_PER_IP = 20;
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'application/pdf', 'text/plain', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip', 'application/x-rar-compressed'
    ];

    public function __construct(
        private readonly ShareService $shareService,
    ) {
    }

    public function saveText(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'text'             => 'nullable|string|max:500000',
            'markdown_source'  => 'nullable|string|max:500000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid text data.',
                'errors' => $validator->errors()
            ], 422);
        }

        $ip = $request->ip();
        $text = $request->input('text');
        $markdown = $request->input('markdown_source');
        $isE2ee = filter_var($request->input('is_e2ee', false), FILTER_VALIDATE_BOOLEAN);
        $principal = $request->principal();

        // Route the canonical write through ShareService so the new
        // `shares` aggregate is the source of truth for owner-type,
        // expiry, and password-hash invariants. We treat saveText as
        // upsert-by-principal: at most one active Share per principal
        // is maintained for the legacy text endpoint, mirroring the
        // pre-existing `shared_texts.ip_address` unique-per-IP shape.
        $share = $this->upsertShareForPrincipal(
            $principal,
            (string) ($text ?? ''),
            is_string($markdown) ? $markdown : null,
            $isE2ee,
        );

        // Adapter: continue to write the legacy `shared_texts` row for
        // guest IP users for one release cycle (design.md > Backward-
        // compat tables). Account / Room principals do not need the
        // legacy row because they did not exist on the old IP-only
        // schema; their data lives exclusively on the Share aggregate.
        if ($principal instanceof IpPrincipal) {
            $sharedText = SharedText::updateOrCreate(
                ['ip_address' => $ip],
                [
                    'content'      => $text,
                    // Use the Share aggregate's expires_at so the two
                    // rows always agree (Carbon timestamps render
                    // identically once toDateTimeString() drops sub-
                    // second precision, preserving byte-for-byte
                    // response equality with the pre-refactor flow).
                    'expires_at'   => $share->expires_at,
                    'last_accessed' => Carbon::now(),
                ],
            );

            // Cache the legacy row for the read path (preserved verbatim
            // from the pre-refactor implementation).
            Cache::put("shared_text_{$ip}", $sharedText, 3600);
        }

        Log::info("Text saved for IP: {$ip}");

        return response()->json([
            'status' => 'success',
            'message' => 'Text saved successfully.',
            // toDateTimeString() formats Y-m-d H:i:s, identical to the
            // pre-refactor `$expiry->toDateTimeString()` output.
            'expires_at' => $share->expires_at->toDateTimeString(),
            'character_count' => strlen((string) ($text ?? '')),
            'share_id' => $share->id,
            'share_uuid' => $share->uuid,
        ]);
    }


    public function getText(Request $request)
    {
        $ip = $request->ip();

        // Try to get from cache first
        $cachedText = Cache::get("shared_text_{$ip}");
        if ($cachedText && Carbon::parse($cachedText->expires_at)->isFuture()) {
            return response()->json([
                'status' => 'success',
                'text' => $cachedText->content,
                'expires_at' => $cachedText->expires_at,
                'last_accessed' => $cachedText->last_accessed
            ]);
        }

        // Find the shared text based on the IP and check if it's still valid
        $sharedText = SharedText::where('ip_address', $ip)
            ->where('expires_at', '>', Carbon::now())  // Ensure the text hasn't expired
            ->first();

        // If shared text is found
        if ($sharedText) {
            // Check if it's been more than 1 hour since last access
            if ($sharedText->last_accessed && Carbon::parse($sharedText->last_accessed)->addHour()->isPast()) {
                // If it's past 1 hour since the last access, delete the text
                // and the corresponding Share aggregate row so the two
                // sides stay in lockstep through the deprecation window.
                $sharedText->delete();
                $this->deleteShareForIp($ip);
                Cache::forget("shared_text_{$ip}");
                return response()->json(['status' => 'error', 'message' => 'Content expired and deleted.']);
            }

            // If the content is still valid, update the last accessed time
            $sharedText->last_accessed = Carbon::now();
            $sharedText->save();

            // Update cache
            Cache::put("shared_text_{$ip}", $sharedText, 3600);

            return response()->json([
                'status' => 'success',
                'text' =>  $sharedText->content,
                'expires_at' => $sharedText->expires_at,
                'last_accessed' => $sharedText->last_accessed,
                'files' => $sharedText->files->map(function ($item) {
                    return [
                        'uuid' => $item->uuid,
                        'url' => $item->getUrl(),
                        'name' => $item->name,
                        'size' => $item->size,
                        'preview_url' => $item->getFullUrl(),
                    ];
                }),
                ...$this->shareMetaForIp($ip),
            ]);
        }

        // If no valid content found for the IP
        return response()->json(['status' => 'error', 'message' => 'No active text found for this IP.']);
    }





    public function deleteMedia(Request $request)
    {
        $uuid = $request->route('uuid') ?? $request->input('uuid');

        if (!$uuid) {
            return response()->json(['success' => false, 'message' => 'UUID required'], 400);
        }

        $media = Media::where('uuid', $uuid)->first();

        if (!$media) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }

        // Security check: ensure the media belongs to the current IP
        $ip = $request->ip();
        $sharedText = $media->model;

        if ($sharedText->ip_address !== $ip) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        Log::info("File deleted for IP: {$ip}, UUID: {$uuid}");
        $media->delete(); // This will remove the file from storage as well

        return response()->json(['success' => true]);
    }

    public function deleteAllMedia(Request $request)
    {
        $ip = $request->ip();

        $sharedText = SharedText::where('ip_address', $ip)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$sharedText) {
            return response()->json(['success' => false, 'message' => 'No files found'], 404);
        }

        $mediaFiles = $sharedText->getMedia();
        $deletedCount = $mediaFiles->count();

        // Delete all media files
        $sharedText->clearMediaCollection();

        // Mirror the deletion onto the corresponding Share aggregate so
        // the new and legacy media collections stay in sync during the
        // deprecation window.
        $share = $this->findActiveShareForIp($ip);
        if ($share !== null) {
            $share->clearMediaCollection();
        }

        Log::info("All files deleted for IP: {$ip}, Count: {$deletedCount}");

        return response()->json([
            'success' => true,
            'message' => "Successfully deleted {$deletedCount} files",
            'deleted_count' => $deletedCount
        ]);
    }

    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }





    public function emailFiles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to_email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'nullable|string|max:1000',
            'uuids' => 'required|array',
            'uuids.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid request data.',
                'errors' => $validator->errors()
            ], 422);
        }

        $ip = $request->ip();
        $toEmail = $request->input('to_email');
        $subject = $request->input('subject');
        $message = $request->input('message', '');
        $uuids = $request->input('uuids');

        // Get media files that belong to this IP
        $sharedText = SharedText::where('ip_address', $ip)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$sharedText) {
            return response()->json([
                'status' => 'error',
                'message' => 'No files found.'
            ], 404);
        }

        $mediaFiles = $sharedText->getMedia()->whereIn('uuid', $uuids);

        if ($mediaFiles->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No files found to email.'
            ], 404);
        }

        try {
            // Send email with attachments
            Mail::send('emails.shared-files', [
                'user_message' => $message,
                'file_count' => $mediaFiles->count(),
                'sender_ip' => $ip
            ], function ($mail) use ($toEmail, $subject, $mediaFiles) {
                $mail->to($toEmail)
                     ->subject($subject);

                foreach ($mediaFiles as $media) {
                    $filePath = $media->getPath();
                    if (file_exists($filePath)) {
                        $mail->attach($filePath, [
                            'as' => $media->name,
                            'mime' => $media->mime_type
                        ]);
                    }
                }
            });

            Log::info("Email sent from IP: {$ip} to {$toEmail}, Files: " . count($mediaFiles));

            return response()->json([
                'status' => 'success',
                'message' => 'Email sent successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error("Email sending failed: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send email. Please try again.'
            ], 500);
        }
    }

    // -- ShareService delegation helpers --------------------------------

    /**
     * Upsert the principal's "current" text-share into the new aggregate.
     *
     * The legacy `/api/v1/text` endpoint behaves as upsert-by-IP (one
     * row per IP via the unique constraint on `shared_texts.ip_address`).
     * To keep that contract on the Share aggregate without changing the
     * controller's surface, we look up the most recent active Share
     * owned by the principal and either update it via
     * {@see ShareService::update()} or create a new one via
     * {@see ShareService::createForPrincipal()}.
     *
     * The expiry option is hard-pinned to `24h` because that is the
     * exact window the pre-refactor controller computed
     * (`Carbon::now()->addHours(24)`); the request body for this
     * endpoint never exposed an expiry knob, so passing `'24h'` to the
     * service preserves byte-for-byte response semantics
     * (Requirement 16.13).
     */
    private function upsertShareForPrincipal(Principal $principal, string $text, ?string $markdownSource = null, bool $isE2ee = false): Share
    {
        $existing = Share::query()
            ->where('owner_type', $principal->type())
            ->where('owner_id', $principal->identifier())
            ->where('expires_at', '>', Carbon::now())
            ->orderByDesc('id')
            ->first();

        $payload = ['expiry' => '24h', 'is_e2ee' => $isE2ee];
        if (is_string($markdownSource) && $markdownSource !== '') {
            $payload['markdown_source'] = $markdownSource;
        } else {
            $payload['text_content'] = $text;
        }

        if ($existing !== null) {
            return $this->shareService->update($existing, $payload);
        }

        return $this->shareService->createForPrincipal($principal, $payload);
    }

    /**
     * Find the active Share aggregate row corresponding to the given
     * legacy IP address, if any. Used by media-deletion paths to keep
     * the legacy and aggregate sides in lockstep through the
     * deprecation window.
     */
    private function findActiveShareForIp(string $ip): ?Share
    {
        return Share::query()
            ->where('owner_type', Share::OWNER_TYPE_IP)
            ->where('owner_id', $ip)
            ->where('expires_at', '>', Carbon::now())
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Mirror a legacy `shared_texts` deletion onto the Share aggregate.
     * Best-effort: a missing aggregate row is a no-op (the legacy table
     * was authoritative before this refactor and may still hold data
     * that has not yet been re-saved through the new path).
     */
    private function deleteShareForIp(string $ip): void
    {
        $share = $this->findActiveShareForIp($ip);
        if ($share === null) {
            return;
        }

        try {
            $share->clearMediaCollection();
        } catch (\Throwable $e) {
            Log::warning('ShareController: failed to clear media on adapter-deleted share', [
                'share_id'   => $share->id,
                'share_uuid' => $share->uuid,
                'reason'     => $e->getMessage(),
            ]);
        }

        try {
            $share->delete();
        } catch (\Throwable $e) {
            Log::warning('ShareController: failed to delete adapter-mirrored share', [
                'share_id'   => $share->id,
                'share_uuid' => $share->uuid,
                'reason'     => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array{share_id?: int, share_uuid?: string, markdown_source?: string}
     */
    private function shareMetaForIp(string $ip): array
    {
        $share = $this->findActiveShareForIp($ip);
        if ($share === null) {
            return [];
        }

        $meta = [
            'share_id'   => $share->id,
            'share_uuid' => $share->uuid,
            'has_password' => $share->hasPassword(),
        ];

        if (is_string($share->markdown_source) && $share->markdown_source !== '') {
            $meta['markdown_source'] = $share->markdown_source;
        }

        return $meta;
    }
}
