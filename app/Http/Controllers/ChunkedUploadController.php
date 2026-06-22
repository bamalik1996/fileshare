<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Share;
use App\Models\UploadSession;
use App\Services\ChunkedUploadService;
use App\Services\ShareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

/**
 * Resumable chunked upload HTTP surface (Requirement 9).
 *
 * Routes (task 14.2):
 *   POST /api/v1/chunked-upload/start
 *   POST /api/v1/chunked-upload/chunk
 *   GET  /api/v1/chunked-upload/status/{sessionId}
 *   POST /api/v1/chunked-upload/complete
 *
 * Status-code contract:
 *   422 — missing / out-of-range metadata (Requirement 9.10)
 *   404 — session not found, expired, or already completed (Requirement 9.9)
 *   409 — SHA-256 integrity failure (Requirement 9.7)
 */
class ChunkedUploadController extends Controller
{
    /** Session id length bounds from Requirement 9.2. */
    private const MIN_SESSION_ID_LEN = 16;
    private const MAX_SESSION_ID_LEN = 64;

    public function __construct(
        private readonly ChunkedUploadService $chunkedUploadService,
        private readonly ShareService $shareService,
    ) {
    }

    /**
     * Start a new upload session.
     */
    public function start(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'total_chunks' => 'required|integer|min:1|max:' . ChunkedUploadService::MAX_TOTAL_CHUNKS,
            'total_bytes'  => 'required|integer|min:1|max:' . ChunkedUploadService::MAX_TOTAL_BYTES,
            'filename'     => 'required|string|max:255',
            'mime'         => 'required|string|max:127',
            'share_id'     => 'nullable|integer|exists:shares,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Invalid upload metadata.', 422, $validator->errors()->toArray());
        }

        $totalBytes = (int) $request->input('total_bytes');

        // Per-owner active-file / storage gate (Requirements 13.3, 13.4).
        $share = $this->resolveOrCreateShareForPrincipal($request);

        if (! $this->shareService->canAddFile($share, $totalBytes)) {
            return $this->error('Active files limit reached for this owner.', 422);
        }

        try {
            $session = $this->chunkedUploadService->start(
                (int) $request->input('total_chunks'),
                $totalBytes,
                (string) $request->input('filename'),
                (string) $request->input('mime'),
                $share->id,
            );
        } catch (InvalidArgumentException $e) {
            if ($e->getMessage() === ChunkedUploadService::ERR_INVALID_METADATA) {
                return $this->error('Invalid upload metadata.', 422);
            }

            throw $e;
        }

        return response()->json([
            'status'     => 'success',
            'session_id' => $session->uuid,
        ]);
    }

    /**
     * Receive one chunk for an existing session.
     */
    public function chunk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id'  => 'required|string|min:' . self::MIN_SESSION_ID_LEN . '|max:' . self::MAX_SESSION_ID_LEN,
            'chunk_index' => 'required|integer|min:0',
            'total_chunks' => 'required|integer|min:1|max:' . ChunkedUploadService::MAX_TOTAL_CHUNKS,
            'sha256'      => 'required|string|size:64',
            'chunk'       => 'required|file|max:' . (ChunkedUploadService::MAX_CHUNK_BYTES / 1024),
        ]);

        if ($validator->fails()) {
            return $this->error('Invalid chunk metadata.', 422, $validator->errors()->toArray());
        }

        $session = $this->resolveOpenSession((string) $request->input('session_id'));

        if ($session === null) {
            return $this->error('Upload session not found.', 404);
        }

        // total_chunks in the request must match the session record (Req 9.10).
        if ((int) $request->input('total_chunks') !== $session->total_chunks) {
            return $this->error('Invalid chunk metadata.', 422);
        }

        try {
            $this->chunkedUploadService->receiveChunk(
                $session,
                (int) $request->input('chunk_index'),
                $request->file('chunk'),
                (string) $request->input('sha256'),
            );
        } catch (InvalidArgumentException $e) {
            return match ($e->getMessage()) {
                ChunkedUploadService::ERR_INTEGRITY_FAILED => response()->json([
                    'status'  => 'error',
                    'message' => 'Chunk integrity check failed.',
                    'code'    => 'integrity_failed',
                ], 409),
                ChunkedUploadService::ERR_INVALID_METADATA => $this->error('Invalid chunk metadata.', 422),
                default => throw $e,
            };
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Return the list of chunk indexes already received (Requirement 9.5).
     */
    public function status(string $sessionId): JsonResponse
    {
        if (! $this->isValidSessionIdFormat($sessionId)) {
            return $this->error('Upload session not found.', 404);
        }

        $session = UploadSession::query()->where('uuid', $sessionId)->first();

        if ($session === null || $session->isCompleted() || $this->isSessionExpired($session)) {
            return $this->error('Upload session not found.', 404);
        }

        return response()->json([
            'status'            => 'success',
            'received_indexes'  => $this->chunkedUploadService->status($session),
            'total_chunks'      => $session->total_chunks,
        ]);
    }

    /**
     * Assemble all received chunks into the final media file.
     */
    public function complete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|min:' . self::MIN_SESSION_ID_LEN . '|max:' . self::MAX_SESSION_ID_LEN,
        ]);

        if ($validator->fails()) {
            return $this->error('Invalid session reference.', 422, $validator->errors()->toArray());
        }

        $session = $this->resolveOpenSession((string) $request->input('session_id'));

        if ($session === null) {
            return $this->error('Upload session not found.', 404);
        }

        $received = $this->chunkedUploadService->status($session);

        if (count($received) !== $session->total_chunks) {
            return $this->error('Not all chunks have been received.', 422);
        }

        $media = $this->chunkedUploadService->assemble($session);

        // When assembly is dispatched to a background job the response
        // carries no media UUID yet; the client polls or waits for realtime.
        if ($media === null) {
            return response()->json([
                'status'     => 'success',
                'message'    => 'Assembly queued.',
                'session_id' => $session->uuid,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'uuid'   => $media->uuid,
            'url'    => $media->getUrl(),
            'name'   => $media->name,
            'size'   => $media->size,
        ]);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function resolveOpenSession(string $sessionId): ?UploadSession
    {
        if (! $this->isValidSessionIdFormat($sessionId)) {
            return null;
        }

        $session = UploadSession::query()->where('uuid', $sessionId)->first();

        if ($session === null || $session->isCompleted() || $this->isSessionExpired($session)) {
            return null;
        }

        return $session;
    }

    private function isValidSessionIdFormat(string $sessionId): bool
    {
        $len = strlen($sessionId);

        return $len >= self::MIN_SESSION_ID_LEN && $len <= self::MAX_SESSION_ID_LEN;
    }

    /**
     * A session is expired when its first chunk arrived more than 24 hours
     * ago and assembly has not completed (Requirement 9.8).
     */
    private function isSessionExpired(UploadSession $session): bool
    {
        if ($session->first_chunk_at === null) {
            return false;
        }

        return $session->first_chunk_at->lte(now()->subHours(24));
    }

    /**
     * Find an active Share for the caller or create one so assembly has
     * a Spatie media target.
     */
    private function resolveOrCreateShareForPrincipal(Request $request): Share
    {
        if ($request->filled('share_id')) {
            return Share::query()->findOrFail((int) $request->input('share_id'));
        }

        return $this->shareService->findOrCreateActiveForPrincipal($request->principal());
    }

    private function error(string $message, int $status, array $errors = []): JsonResponse
    {
        $body = ['status' => 'error', 'message' => $message];

        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }
}
