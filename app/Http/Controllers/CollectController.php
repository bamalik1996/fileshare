<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\MediaAdded;
use App\Support\SafeBroadcast;
use App\Models\Account;
use App\Models\Share;
use App\Services\CollectService;
use App\Services\ShareService;
use App\Support\UploadTypePolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * File-request / "collect" flow (SaaS features, Phase 3).
 *
 *   POST /account/collect   (auth)   create a collect inbox for the Account.
 *   GET  /collect/{slug}     (public) upload page for recipients.
 *   POST /collect/{slug}     (public) accept one uploaded file.
 *
 * Recipient uploads reuse the same storage, virus-scan, and realtime
 * broadcast pipeline as owner uploads; the only difference is that the
 * uploader is an anonymous guest and the target Share is resolved by its
 * collect slug rather than by the request principal.
 */
class CollectController extends Controller
{
    private const MAX_FILE_SIZE = 25 * 1024 * 1024; // 25 MB (legacy single-request limit)

    public function __construct(
        private readonly CollectService $collect,
        private readonly ShareService $shareService,
        private readonly UploadTypePolicy $uploadTypes,
    ) {
    }

    /**
     * Create a collect inbox owned by the authenticated Account.
     */
    public function create(Request $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user('account');

        $validated = $request->validate([
            'title'        => ['nullable', 'string', 'max:120'],
            'instructions' => ['nullable', 'string', 'max:500'],
        ]);

        $share = $this->collect->createForAccount(
            $account,
            $validated['title'] ?? null,
            $validated['instructions'] ?? null,
        );

        return response()->json([
            'status' => 'success',
            'slug'   => $share->collect_slug,
            'url'    => url('/collect/' . $share->collect_slug),
        ]);
    }

    /**
     * Public upload page for a collect inbox.
     */
    public function show(Request $request, string $slug): View|Response
    {
        $share = $this->collect->findOpenBySlug($slug);

        if ($share === null) {
            return response()->view('errors.404', [], 404);
        }

        return view('collect.show', [
            'share'    => $share,
            'branding' => $share->brandingPayload(),
        ]);
    }

    /**
     * Accept one uploaded file into the collect inbox.
     */
    public function upload(Request $request, string $slug): JsonResponse
    {
        $share = $this->collect->findOpenBySlug($slug);

        if ($share === null) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This file request is no longer available.',
            ], 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'file' => 'required|file|max:' . (self::MAX_FILE_SIZE / 1024),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid file.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');

        if (! $this->uploadTypes->isAllowed($file->getMimeType(), $file->getClientOriginalName())) {
            return response()->json([
                'status'  => 'error',
                'message' => 'File type not allowed.',
            ], 422);
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return response()->json([
                'status'  => 'error',
                'message' => 'File size exceeds limit of 25 MB.',
            ], 422);
        }

        if (! $this->shareService->canAddFile($share, (int) $file->getSize())) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This file request has reached its storage limit.',
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
                throw new \RuntimeException('File was not properly saved to storage');
            }

            @chmod($media->getPath(), 0644);

            \App\Services\VirusScanner::make()->queueForMedia($media);
            \App\Jobs\ScanMediaForViruses::dispatch($media->uuid);

            SafeBroadcast::event(new MediaAdded(
                $share,
                (string) $media->uuid,
                (string) $media->name,
                (int) $media->size,
                (string) $media->mime_type,
                $media->getUrl(),
            ));
        } catch (\Throwable $e) {
            Log::error('Collect upload failed', [
                'collect_slug' => $slug,
                'error'        => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to save file. Please try again.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'file'   => [
                'uuid' => (string) $media->uuid,
                'name' => (string) $media->name,
                'size' => (int) $media->size,
            ],
        ]);
    }
}
