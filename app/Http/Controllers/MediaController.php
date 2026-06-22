<?php

namespace App\Http\Controllers;

use App\Domain\Principal\IpPrincipal;
use App\Domain\Principal\Principal;
use App\Events\MediaAdded;
use App\Models\MediaFile;
use App\Models\MediaScan;
use App\Models\Share;
use App\Services\ShareService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use ZipArchive;

/**
 * Legacy `/api/v1/media` controller.
 *
 * Task 5.3 refactor:
 *   - Every add (upload) path consults {@see ShareService::canAddFile()}
 *     before any file is persisted. Rejection paths return HTTP 422
 *     (the status the legacy endpoint already used for limit/size
 *     violations) and do not modify the owner's existing files
 *     (Requirements 13.3, 13.4).
 *   - The single-file download path looks up the corresponding
 *     `media_scans` row by Spatie media UUID and applies the status
 *     mapping from design.md > Component 20:
 *
 *        missing row OR `pending` → 425 Too Early
 *        `clean`                  → serve (existing behaviour)
 *        `infected`               → 451 Unavailable For Legal Reasons
 *        `error`                  → 503 Service Unavailable
 *        `skipped_e2ee`           → serve (the unscanned-media notice
 *                                   is rendered by the surrounding
 *                                   share view, not here)
 *     (Requirements 20.2, 20.3, 20.4, 20.9.)
 *
 * Guest IP request/response surfaces (status codes, body shape, headers,
 * one-time-download behaviour) are otherwise preserved byte-for-byte
 * (Requirement 16.13). The new caps almost never trip for guest IP
 * because the legacy 20-files-per-IP rule below is stricter; the gate
 * matters most for Account / Room principals and for an eventual
 * /api/v2/* hand-off, but it is unconditional here so the contract
 * holds for every principal kind.
 */
class MediaController extends Controller
{
    private const MAX_FILE_SIZE = 25 * 1024 * 1024; // 25MB
    private const MAX_FILES_PER_IP = 20;
    private const ALLOWED_MIME_TYPES = [
        'image/*',
        'video/*',
        'audio/*',
        'application/pdf',
        'text/plain',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip',
        'application/x-rar-compressed'
    ];

    public function __construct(
        private readonly ShareService $shareService,
    ) {
    }

    /**
     * Store a media file with current user's IP and expiry of 6 hours
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:' . (self::MAX_FILE_SIZE / 1024)
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid file.',
                'errors' => $validator->errors()
            ], 422);
        }

        $ip = $request->ip();
        $file = $request->file('file');
        $principal = $request->principal();

        $isAllowed = false;
        foreach (self::ALLOWED_MIME_TYPES as $allowed) {
            if (Str::is($allowed, $file->getMimeType())) {
                $isAllowed = true;
                break;
            }
        }

        if (! $isAllowed) {
            return response()->json([
                'status' => 'error',
                'message' => 'File type not allowed.',
            ], 422);
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return response()->json([
                'status' => 'error',
                'message' => 'File size exceeds limit of ' . $this->formatFileSize(self::MAX_FILE_SIZE),
            ], 422);
        }

        if ($this->usesShareAggregate($principal)) {
            return $this->storeForShareOwner($request, $file, $principal);
        }

        return $this->storeForLegacyIp($request, $file, $ip);
    }

    /**
     * Persist a file on the Share aggregate for Account / Room owners.
     */
    private function storeForShareOwner(Request $request, $file, Principal $principal)
    {
        $share = $this->shareService->findOrCreateActiveForPrincipal($principal);

        if (! $this->shareService->canAddFile($share, (int) $file->getSize())) {
            return response()->json([
                'status' => 'error',
                'message' => 'Active files limit reached for this owner.',
            ], 422);
        }

        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;

        try {
            $media = $share->addMedia($file)
                ->usingName($originalName)
                ->usingFileName($safeName)
                ->toMediaCollection('shared_files', 'public');

            if (! $media || ! file_exists($media->getPath())) {
                throw new \Exception('File was not properly saved to storage');
            }

            chmod($media->getPath(), 0644);

            \App\Services\VirusScanner::make()->queueForMedia($media);
            \App\Jobs\ScanMediaForViruses::dispatch($media->uuid);

            $share = $this->shareService->update($share, ['expiry' => '24h']);

            broadcast(new MediaAdded(
                $share,
                (string) $media->uuid,
                (string) $media->name,
                (int) $media->size,
                (string) $media->mime_type,
                $media->getUrl(),
            ));
        } catch (\Exception $e) {
            Log::error('File upload failed for share owner', [
                'owner_type' => $principal->type(),
                'owner_id'   => $principal->identifier(),
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save file. Please try again.',
            ], 500);
        }

        Log::info('File uploaded for share owner', [
            'owner_type' => $principal->type(),
            'owner_id'   => $principal->identifier(),
            'share_id'   => $share->id,
            'file'       => $originalName,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Media uploaded successfully.',
            'uuid' => $media->uuid,
            'url' => $media->getUrl(),
            'name' => $media->name,
            'size' => $this->formatFileSize($media->size),
            'expires_at' => $share->expires_at->toDateTimeString(),
            'share_id' => $share->id,
            'share_uuid' => $share->uuid,
        ]);
    }

    /**
     * Legacy IP-only upload path (Requirement 16.13 backward compat).
     */
    private function storeForLegacyIp(Request $request, $file, string $ip)
    {
        $principal = $request->principal();
        $shareForGate = $this->ephemeralShareForPrincipal($principal);

        if (! $this->shareService->canAddFile($shareForGate, (int) $file->getSize())) {
            return response()->json([
                'status' => 'error',
                'message' => 'Active files limit reached for this owner.',
            ], 422);
        }

        $expiry = now()->addHours(24);

        $sharedMedia = MediaFile::firstOrNew(['ip_address' => $ip]);
        $sharedMedia->expires_at = $expiry;
        $sharedMedia->save();

        if ($sharedMedia->getMedia('shared_files')->count() >= self::MAX_FILES_PER_IP) {
            return response()->json([
                'status' => 'error',
                'message' => 'Maximum file limit reached (' . self::MAX_FILES_PER_IP . ' files per IP)',
            ], 422);
        }

        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;

        try {
            $media = $sharedMedia->addMedia($file)
                ->usingName($originalName)
                ->usingFileName($safeName)
                ->toMediaCollection('shared_files', 'public');

            if (! $media || ! file_exists($media->getPath())) {
                throw new \Exception('File was not properly saved to storage');
            }

            chmod($media->getPath(), 0644);

            \App\Services\VirusScanner::make()->queueForMedia($media);
            \App\Jobs\ScanMediaForViruses::dispatch($media->uuid);
        } catch (\Exception $e) {
            Log::error("File upload failed for IP: {$ip}, Error: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save file. Please try again.',
            ], 500);
        }

        Log::info("File uploaded for IP: {$ip}, File: {$originalName}");

        return response()->json([
            'status' => 'success',
            'message' => 'Media uploaded successfully.',
            'uuid' => $media->uuid,
            'url' => $media->getUrl(),
            'name' => $media->name,
            'size' => $this->formatFileSize($media->size),
            'expires_at' => $expiry->toDateTimeString(),
        ]);
    }



    /**
     * Fetch all non-expired media files uploaded from current IP
     */
    public function index(Request $request)
    {
        $principal = $request->principal();

        if ($this->usesShareAggregate($principal)) {
            $share = $this->activeShareForPrincipal($principal);

            if ($share === null) {
                return response()->json([
                    'files' => [],
                    'total_files' => 0,
                    'total_size' => $this->formatFileSize(0),
                ]);
            }

            return response()->json($this->filesPayloadFromShare($share));
        }

        $ip = $request->ip();

        $mediaFiles = MediaFile::active($ip)
            ->latest('created_at')
            ->first();

        if (! $mediaFiles) {
            return response()->json([
                'files' => [],
                'total_files' => 0,
                'total_size' => $this->formatFileSize(0),
            ]);
        }

        $payload = $this->filesPayloadFromShare($mediaFiles);
        $share = Share::query()
            ->where('owner_type', Share::OWNER_TYPE_IP)
            ->where('owner_id', $ip)
            ->where('expires_at', '>', Carbon::now())
            ->orderByDesc('id')
            ->first();

        if ($share !== null) {
            $payload['share_uuid'] = $share->uuid;
            $payload['has_password'] = $share->hasPassword();
        }

        return response()->json($payload);
    }


    /**
     * Delete a specific media file
     */
    public function destroy(Request $request, $uuid = null)
    {
        $uuid = $uuid ?? $request->input('uuid');

        if (! $uuid) {
            return response()->json(['success' => false, 'message' => 'UUID required'], 400);
        }

        $principal = $request->principal();

        if ($this->usesShareAggregate($principal)) {
            $mediaItem = Media::where('uuid', $uuid)->first();

            if ($mediaItem === null || ! $this->mediaBelongsToPrincipal($mediaItem, $principal)) {
                return response()->json(['success' => false, 'message' => 'Media not found'], 404);
            }

            $mediaItem->delete();

            Log::info('File deleted for share owner', [
                'owner_type' => $principal->type(),
                'owner_id'   => $principal->identifier(),
                'uuid'       => $uuid,
            ]);

            return response()->json(['success' => true, 'message' => 'File deleted successfully']);
        }

        $ip = $request->ip();

        // Find parent model by IP
        $mediaFile = MediaFile::where('ip_address', $ip)->first();

        if (!$mediaFile) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }

        // Find media item by UUID
        $mediaItem = $mediaFile->media()->where('uuid', $uuid)->first();

        if (!$mediaItem) {
            return response()->json(['success' => false, 'message' => 'Media not found'], 404);
        }

        $mediaItem->delete();

        Log::info("File deleted for IP: {$ip}, UUID: {$uuid}");

        // If no media left, delete parent
        if ($mediaFile->getMedia('shared_files')->count() === 0) {
            $mediaFile->delete();
        }

        return response()->json(['success' => true, 'message' => 'File deleted successfully']);
    }


    /**
     * Delete all media files for current IP
     */
    public function destroyAll(Request $request)
    {
        $principal = $request->principal();

        if ($this->usesShareAggregate($principal)) {
            $shares = Share::query()
                ->where('owner_type', $principal->type())
                ->where('owner_id', $principal->identifier())
                ->where('expires_at', '>', now())
                ->get();

            $deletedCount = 0;

            foreach ($shares as $share) {
                $deletedCount += $share->getMedia('shared_files')->count();
                $share->clearMediaCollection('shared_files');
            }

            if ($deletedCount === 0) {
                return response()->json(['success' => false, 'message' => 'No files found'], 404);
            }

            Log::info('All files deleted for share owner', [
                'owner_type' => $principal->type(),
                'owner_id'   => $principal->identifier(),
                'count'      => $deletedCount,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} files",
                'deleted_count' => $deletedCount,
            ]);
        }

        $ip = $request->ip();

        $mediaFiles = MediaFile::active($ip)->get();
        $deletedCount = $mediaFiles->count();

        if ($deletedCount === 0) {
            return response()->json(['success' => false, 'message' => 'No files found'], 404);
        }

        // Delete all files
        foreach ($mediaFiles as $file) {
            $file->delete();
        }

        Log::info("All files deleted for IP: {$ip}, Count: {$deletedCount}");

        return response()->json([
            'success' => true,
            'message' => "Successfully deleted {$deletedCount} files",
            'deleted_count' => $deletedCount
        ]);
    }

    public function getIpInfo(Request $request)
    {
        $ip = $request->ip();
        
        $mediaFile = MediaFile::where('ip_address', $ip)->first();
        $sharedText = \App\Models\SharedText::where('ip_address', $ip)->first();

        $expiresAt = null;
        $lastAccessed = null;
        $hasContent = false;

        // Check MediaFile expiration
        if ($mediaFile) {
            $expiresAt = $mediaFile->expires_at;
            $lastAccessed = $mediaFile->last_accessed;
            $hasContent = true;
        }

        // Check SharedText expiration and take the latest one
        if ($sharedText) {
            $hasContent = true;
            if (!$expiresAt || ($sharedText->expires_at && $sharedText->expires_at->gt($expiresAt))) {
                $expiresAt = $sharedText->expires_at;
            }
            if (!$lastAccessed || ($sharedText->last_accessed && $sharedText->last_accessed->gt($lastAccessed))) {
                $lastAccessed = $sharedText->last_accessed;
            }
        }

        $info = [
            'ip' => $ip,
            'has_content' => $hasContent,
            'files_count' => $mediaFile ? $mediaFile->getMedia('shared_files')->count() : 0,
            'max_files' => self::MAX_FILES_PER_IP,
            'max_file_size' => $this->formatFileSize(self::MAX_FILE_SIZE)
        ];

        if ($expiresAt) {
            $info['expires_at'] = $expiresAt;
            $info['last_accessed'] = $lastAccessed;
        }

        $principal = $request->principal();
        $share = Share::query()
            ->where('owner_type', $principal->type())
            ->where('owner_id', $principal->identifier())
            ->where('expires_at', '>', Carbon::now())
            ->orderByDesc('id')
            ->first();

        if ($share !== null) {
            $info['share_uuid'] = $share->uuid;
            $info['has_password'] = $share->hasPassword();
        }

        return response()->json($info);
    }

    /**
     * Format file size for display
     */
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

    public function downloadZip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uuids' => 'required|array',
            'uuids.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid request data.'
            ], 422);
        }

        $ip = $request->ip();
        $uuids = $request->input('uuids');

        // Get media files that belong to this IP
        $sharedText = MediaFile::active($ip)
            ->first();

        if (!$sharedText) {
            return response()->json([
                'status' => 'error',
                'message' => 'No files found.'
            ], 404);
        }

        $mediaFiles = $sharedText->getMedia('shared_files')->whereIn('uuid', $uuids);

        if ($mediaFiles->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No files found for download.'
            ], 404);
        }

        // Create temporary zip file
        $zipFileName = 'shared-files-' . time() . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        // Ensure temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            return response()->json([
                'status' => 'error',
                'message' => 'Could not create zip file.'
            ], 500);
        }

        foreach ($mediaFiles as $media) {
            $filePath = $media->getPath();
            if (file_exists($filePath)) {
                $zip->addFile($filePath, $media->name);
            }
        }

        $zip->close();

        Log::info("Zip file created for IP: {$ip}, Files: " . count($mediaFiles));

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Download a single file by UUID with one-time download support.
     *
     * Applies the virus-scan status gate from design.md > Component 20
     * before any byte of file content is served. The mapping is:
     *
     *   missing scan row OR `pending` → 425 Too Early
     *   `clean`                       → serve (existing behaviour)
     *   `infected`                    → 451 Unavailable For Legal Reasons
     *   `error`                       → 503 Service Unavailable
     *   `skipped_e2ee`                → serve (the unscanned-media notice
     *                                   is rendered by the surrounding
     *                                   share view, not by this endpoint)
     *
     * Validates: Requirements 20.2, 20.3, 20.4, 20.9.
     */
    public function download(Request $request, $uuid)
    {
        // Find media by UUID using Spatie's Media model
        $media = Media::where('uuid', $uuid)->first();

        if (!$media) {
            return response()->view('errors.404', [], 404);
        }

        // Virus-scan status gate (Requirement 20). Applied before
        // checking the on-disk file because a missing scan row is
        // semantically the same as `pending` per the design mapping
        // and must surface 425 regardless of disk state.
        $gate = $this->scanStatusGate($media->uuid);
        if ($gate !== null) {
            return $gate;
        }

        $filePath = $media->getPath();

        if (!file_exists($filePath)) {
            Log::warning("Download requested but file not found: {$filePath}");
            return response()->view('errors.404', [], 404);
        }

        // Check if this is a one-time download
        $oneTimeToken = $request->query('onetime');
        $isOneTime = !empty($oneTimeToken);

        // Log the download
        Log::info("File downloaded: {$media->name}", [
            'uuid' => $uuid,
            'one_time' => $isOneTime,
            'token' => $oneTimeToken
        ]);

        // Get file info before potential deletion
        $fileName = $media->file_name;
        $mimeType = $media->mime_type;

        if ($isOneTime) {
            // For one-time downloads, delete the media after sending
            // We need to copy the file temporarily first
            $tempPath = storage_path('app/temp/' . $fileName);
            
            // Ensure temp directory exists
            if (!is_dir(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            copy($filePath, $tempPath);
            
            // Delete the original media
            $media->delete();
            
            Log::info("One-time download: File deleted after download", ['uuid' => $uuid]);
            
            // Return the temp file and delete after send
            return response()->download($tempPath, $fileName, [
                'Content-Type' => $mimeType
            ])->deleteFileAfterSend(true);
        }

        // Regular download - just serve the file
        return response()->download($filePath, $fileName, [
            'Content-Type' => $mimeType
        ]);
    }

    // -- helpers ------------------------------------------------------------

    /**
     * Build an in-memory Share that carries only the principal-derived
     * owner pair. {@see ShareService::canAddFile()} consults
     * `owner_type`/`owner_id` only - it never touches the share's id or
     * persisted columns - so we never have to save this row just to
     * gate a single upload (avoiding a stray `shares` insert on the
     * rejection path that Requirement 13.4 forbids).
     */
    private function ephemeralShareForPrincipal(Principal $principal): Share
    {
        $share = new Share();
        $share->owner_type = $principal->type();
        $share->owner_id = $principal->identifier();

        return $share;
    }

    private function usesShareAggregate(Principal $principal): bool
    {
        return ! ($principal instanceof IpPrincipal);
    }

    private function activeShareForPrincipal(Principal $principal): ?Share
    {
        return Share::query()
            ->where('owner_type', $principal->type())
            ->where('owner_id', $principal->identifier())
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @param  \Spatie\MediaLibrary\HasMedia  $owner
     * @return array{files: \Illuminate\Support\Collection, total_files: int, total_size: string}
     */
    private function filesPayloadFromShare($owner): array
    {
        $files = $owner->getMedia('shared_files')
            ->map(function ($item) {
                $path = $item->getPath();

                if (! file_exists($path)) {
                    Log::warning("Missing file on disk: {$path}");

                    return null;
                }

                return [
                    'uuid' => $item->uuid,
                    'name' => $item->name,
                    'file_name' => $item->file_name,
                    'mime_type' => $item->mime_type,
                    'extension' => $item->extension,
                    'preview_url' => $item->getFullUrl(),
                    'original_url' => $item->getUrl(),
                    'order' => $item->order_column,
                    'custom_properties' => $item->custom_properties,
                    'size' => $this->formatFileSize($item->size),
                    'size_bytes' => $item->size,
                    'created_at' => $item->created_at->diffForHumans(),
                ];
            })
            ->filter()
            ->values();

        return [
            'files' => $files,
            'total_files' => $files->count(),
            'total_size' => $this->formatFileSize($files->sum('size_bytes')),
            ...($owner instanceof Share ? [
                'share_uuid' => $owner->uuid,
                'has_password' => $owner->hasPassword(),
            ] : []),
        ];
    }

    private function mediaBelongsToPrincipal(Media $media, Principal $principal): bool
    {
        if ($media->model_type !== (new Share())->getMorphClass()) {
            return false;
        }

        $share = Share::query()->find($media->model_id);

        return $share !== null
            && $share->owner_type === $principal->type()
            && $share->owner_id === $principal->identifier();
    }

    /**
     * Resolve the scan-status gate for a download. Returns:
     *   - null when the caller may proceed to serve the bytes
     *     (clean / skipped_e2ee), or
     *   - a fully-formed Response carrying the design-mapped status
     *     code (425 / 451 / 503) for the caller to return verbatim.
     *
     * Treating a missing scan row as `pending` (rather than as an
     * implicit `clean`) is required by Requirement 20.2 and the
     * design's "missing row OR `pending` → 425" rule: any media that
     * has not yet been queued (or whose row was lost) must not leak.
     */
    private function scanStatusGate(string $mediaUuid): ?Response
    {
        $scan = MediaScan::query()->where('media_uuid', $mediaUuid)->first();
        $status = $scan?->status ?? MediaScan::STATUS_PENDING;

        return match ($status) {
            MediaScan::STATUS_PENDING => response('Scan pending; please retry shortly.', 425)
                ->header('Content-Type', 'text/plain; charset=UTF-8'),
            MediaScan::STATUS_INFECTED => response('File flagged as infected and is unavailable.', 451)
                ->header('Content-Type', 'text/plain; charset=UTF-8'),
            MediaScan::STATUS_ERROR => response('Scan failed; awaiting manual review.', 503)
                ->header('Content-Type', 'text/plain; charset=UTF-8'),
            MediaScan::STATUS_CLEAN, MediaScan::STATUS_SKIPPED_E2EE => null,
            // Defensive default: any unknown status is treated as `error`
            // so a corrupt row can never silently serve infected bytes.
            default => response('Scan failed; awaiting manual review.', 503)
                ->header('Content-Type', 'text/plain; charset=UTF-8'),
        };
    }
}
