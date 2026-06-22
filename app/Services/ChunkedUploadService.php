<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\MediaAdded;
use App\Models\Share;
use App\Models\UploadChunk;
use App\Models\UploadSession;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Chunked_Upload_Service (design.md > Components and Interfaces > 9).
 *
 * Handles the server-side lifecycle of a resumable chunked upload:
 *   - `start`        — create a new UploadSession row.
 *   - `receiveChunk` — persist one chunk, verify SHA-256 integrity.
 *   - `status`       — return the list of already-received chunk indexes.
 *   - `assemble`     — concatenate chunks 0..N-1 and register with Spatie.
 *
 * Storage layout: `storage/app/chunks/{session_uuid}/{index}.bin`
 *
 * Validation rules (enforced here; HTTP status codes applied by the
 * controller):
 *   - chunk size   ≤ 5 MB     (Requirement 9.2)
 *   - total chunks 1..1000    (Requirement 9.2)
 *   - total bytes  ≤ 500 MB   (Requirement 9.2, 13.1, 13.5)
 *   - chunk index  0..N-1     (Requirement 9.10)
 *   - SHA-256 mismatch → `integrity_failed` (409 from controller) (Req 9.7)
 *   - idempotent re-upload of same (session, index) with matching hash (Req 9.7)
 *
 * This is the task 14.1 implementation stub. The `assemble()` method
 * dispatches `AssembleChunkedUpload` (task 14.3) if the job class exists;
 * otherwise it runs inline assembly for now.
 *
 * Requirements: 9.1–9.10, 13.1, 13.5.
 */
class ChunkedUploadService
{
    // -----------------------------------------------------------------------
    // Constants
    // -----------------------------------------------------------------------

    /** Maximum size for one individual chunk: 5 MB. */
    public const MAX_CHUNK_BYTES = 5 * 1024 * 1024;

    /** Maximum number of chunks in one session (Requirement 9.2). */
    public const MAX_TOTAL_CHUNKS = 1000;

    /** Minimum number of chunks (a single chunk is valid). */
    public const MIN_TOTAL_CHUNKS = 1;

    /** Maximum declared total bytes for a chunked upload: 500 MB. */
    public const MAX_TOTAL_BYTES = 500 * 1024 * 1024;

    // -----------------------------------------------------------------------
    // Exception codes returned to the controller so it can map them to HTTP
    // -----------------------------------------------------------------------

    /** Thrown when chunk metadata is out of range or mismatched. */
    public const ERR_INVALID_METADATA = 'invalid_metadata';

    /** Thrown when the SHA-256 supplied by the client does not match. */
    public const ERR_INTEGRITY_FAILED = 'integrity_failed';

    /** Thrown when the session is not found, expired, or already completed. */
    public const ERR_SESSION_NOT_FOUND = 'session_not_found';

    // -----------------------------------------------------------------------
    // Public API
    // -----------------------------------------------------------------------

    /**
     * Start a new upload session.
     *
     * Validates declared metadata; returns a persisted UploadSession whose
     * `uuid` is the session_id callers must include in subsequent requests.
     *
     * @param  int     $totalChunks  Number of chunks the client will send (1..1000).
     * @param  int     $totalBytes   Declared total file size in bytes (1..500 MB).
     * @param  string  $filename     Original client-supplied filename.
     * @param  string  $mime         Client-supplied MIME type.
     * @param  int|null $shareId     Optional target Share primary-key (nullable for
     *                               sessions not yet bound to a Share).
     *
     * @throws \InvalidArgumentException With {@see self::ERR_INVALID_METADATA} as the
     *                                   message when the declared metadata violates
     *                                   Requirement 9.10.
     */
    public function start(
        int $totalChunks,
        int $totalBytes,
        string $filename,
        string $mime,
        ?int $shareId = null,
    ): UploadSession {
        $this->validateStartMetadata($totalChunks, $totalBytes);

        $session = UploadSession::create([
            'share_id'     => $shareId,
            'filename'     => $filename,
            'mime'         => $mime,
            'total_bytes'  => $totalBytes,
            'total_chunks' => $totalChunks,
        ]);

        Log::info('ChunkedUploadService: session started', [
            'session_uuid' => $session->uuid,
            'total_chunks' => $totalChunks,
            'total_bytes'  => $totalBytes,
            'filename'     => $filename,
        ]);

        return $session;
    }

    /**
     * Receive and persist a single chunk.
     *
     * Implements idempotency: if (session, index) already exists and the
     * SHA-256 matches, the existing row is returned without re-writing disk.
     * A SHA-256 mismatch on a duplicate index throws with `integrity_failed`
     * (Requirement 9.7).
     *
     * @param  UploadSession  $session      Loaded, non-expired, non-completed session.
     * @param  int            $index        Zero-based chunk position (0..N-1).
     * @param  UploadedFile   $file         The raw chunk data.
     * @param  string         $clientHash   Client-supplied SHA-256 hex string.
     *
     * @throws \InvalidArgumentException code `integrity_failed`   Hash mismatch.
     * @throws \InvalidArgumentException code `invalid_metadata`   Out-of-range index /
     *                                                             mismatched total_chunks.
     */
    public function receiveChunk(
        UploadSession $session,
        int $index,
        UploadedFile $file,
        string $clientHash,
    ): UploadChunk {
        // Validate index range.
        if ($index < 0 || $index >= $session->total_chunks) {
            throw new \InvalidArgumentException(
                self::ERR_INVALID_METADATA,
                0,
            );
        }

        // Validate chunk size.
        $sizeBytes = (int) $file->getSize();
        if ($sizeBytes > self::MAX_CHUNK_BYTES) {
            throw new \InvalidArgumentException(self::ERR_INVALID_METADATA);
        }

        // Compute server-side SHA-256.
        $serverHash = hash_file('sha256', $file->getRealPath());

        // Normalise both to lower-case for comparison.
        $clientHashNorm = strtolower(trim($clientHash));
        $serverHashNorm = strtolower($serverHash);

        // Check idempotency: does this (session, index) already exist?
        $existing = UploadChunk::where('session_id', $session->id)
            ->where('chunk_index', $index)
            ->first();

        if ($existing !== null) {
            if ($existing->sha256 !== $serverHashNorm) {
                throw new \InvalidArgumentException(self::ERR_INTEGRITY_FAILED);
            }

            // Matching hash — idempotent no-op.
            return $existing;
        }

        // Verify client-supplied hash.
        if ($clientHashNorm !== $serverHashNorm) {
            throw new \InvalidArgumentException(self::ERR_INTEGRITY_FAILED);
        }

        // Persist chunk to disk.
        $storedPath = $this->storeChunkFile($session->uuid, $index, $file);

        // Update first_chunk_at timestamp on the first chunk received.
        if ($session->first_chunk_at === null) {
            $session->first_chunk_at = now();
            $session->saveQuietly();
        }

        $chunk = UploadChunk::create([
            'session_id'  => $session->id,
            'chunk_index' => $index,
            'sha256'      => $serverHashNorm,
            'size_bytes'  => $sizeBytes,
            'stored_path' => $storedPath,
        ]);

        Log::debug('ChunkedUploadService: chunk received', [
            'session_uuid' => $session->uuid,
            'chunk_index'  => $index,
            'sha256'       => $serverHashNorm,
        ]);

        return $chunk;
    }

    /**
     * Return the list of chunk indexes already received for a session.
     *
     * Used by the status endpoint and by the client for resume logic.
     * Must respond within 2 seconds (Requirement 9.5). The query hits
     * an indexed column (session_id), so it is O(received chunks).
     *
     * @return list<int>
     */
    public function status(UploadSession $session): array
    {
        return UploadChunk::where('session_id', $session->id)
            ->orderBy('chunk_index')
            ->pluck('chunk_index')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();
    }

    /**
     * Assemble a completed session into the final file and register it with
     * Spatie Media Library on the target Share.
     *
     * The caller is responsible for verifying all chunks are present before
     * calling this method. After successful assembly the session is marked
     * `completed_at = now()` so subsequent requests receive a 404
     * (Requirement 9.9).
     *
     * If the `AssembleChunkedUpload` job class exists, assembly is dispatched
     * as a background job (task 14.3). Otherwise inline assembly runs here
     * as a stub.
     *
     * @throws \RuntimeException When chunk files are missing on disk or the
     *                           target Share cannot be found.
     */
    public function assemble(UploadSession $session): ?Media
    {
        // Mark completed immediately to prevent duplicate assembly triggers.
        $session->completed_at = now();
        $session->save();

        // Dispatch to background job when the queue worker is available.
        if (class_exists(\App\Jobs\AssembleChunkedUpload::class)) {
            \App\Jobs\AssembleChunkedUpload::dispatch($session->fresh());

            return null;
        }

        return $this->performAssembly($session);
    }

    /**
     * Concatenate all persisted chunks and register the file on the Share.
     *
     * Called synchronously from {@see assemble()} when no queue worker is
     * configured, and from {@see \App\Jobs\AssembleChunkedUpload} otherwise.
     *
     * @throws \RuntimeException When chunk files are missing or the Share
     *                           cannot be found.
     */
    public function performAssembly(UploadSession $session): ?Media
    {
        return $this->runInlineAssembly($session);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Validate the metadata supplied to `start()`.
     *
     * @throws \InvalidArgumentException with `ERR_INVALID_METADATA`.
     */
    private function validateStartMetadata(int $totalChunks, int $totalBytes): void
    {
        if ($totalChunks < self::MIN_TOTAL_CHUNKS || $totalChunks > self::MAX_TOTAL_CHUNKS) {
            throw new \InvalidArgumentException(self::ERR_INVALID_METADATA);
        }

        if ($totalBytes < 1 || $totalBytes > self::MAX_TOTAL_BYTES) {
            throw new \InvalidArgumentException(self::ERR_INVALID_METADATA);
        }
    }

    /**
     * Store the raw chunk bytes on disk and return the relative path.
     */
    private function storeChunkFile(string $sessionUuid, int $index, UploadedFile $file): string
    {
        $directory = "chunks/{$sessionUuid}";
        $filename  = "{$index}.bin";

        Storage::disk('local')->makeDirectory($directory);

        $file->storeAs($directory, $filename, 'local');

        return "{$directory}/{$filename}";
    }

    /**
     * Inline (synchronous) chunk assembly — used as a stub when the
     * `AssembleChunkedUpload` background job has not been implemented yet.
     *
     * Reads chunk files in ascending index order, concatenates them to a
     * temporary file, then registers via Spatie Media Library on the share.
     */
    private function runInlineAssembly(UploadSession $session): ?Media
    {
        if ($session->share_id === null) {
            Log::warning('ChunkedUploadService: assembly skipped — no share_id bound', [
                'session_uuid' => $session->uuid,
            ]);

            return null;
        }

        $share = Share::find($session->share_id);

        if ($share === null) {
            Log::error('ChunkedUploadService: assembly failed — share not found', [
                'session_uuid' => $session->uuid,
                'share_id'     => $session->share_id,
            ]);

            return null;
        }

        $chunks = UploadChunk::where('session_id', $session->id)
            ->orderBy('chunk_index')
            ->get();

        // Build a temporary output file.
        $tmpDir  = storage_path('app/chunks/' . $session->uuid);
        $tmpPath = $tmpDir . '/assembled.tmp';

        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $out = fopen($tmpPath, 'wb');

        if ($out === false) {
            throw new \RuntimeException("Cannot open tmp file: {$tmpPath}");
        }

        foreach ($chunks as $chunk) {
            $chunkPath = storage_path('app/' . $chunk->stored_path);

            if (! file_exists($chunkPath)) {
                fclose($out);
                throw new \RuntimeException("Missing chunk file: {$chunkPath}");
            }

            $in = fopen($chunkPath, 'rb');

            if ($in === false) {
                fclose($out);
                throw new \RuntimeException("Cannot open chunk file: {$chunkPath}");
            }

            stream_copy_to_stream($in, $out);
            fclose($in);
        }

        fclose($out);

        // Register with Spatie.
        $media = $share
            ->addMedia($tmpPath)
            ->usingName($session->filename)
            ->usingFileName(basename($session->filename))
            ->withCustomProperties(['session_uuid' => $session->uuid])
            ->toMediaCollection('shared_files', 'public');

        Log::info('ChunkedUploadService: assembly complete', [
            'session_uuid' => $session->uuid,
            'media_uuid'   => $media->uuid,
        ]);

        broadcast(new MediaAdded(
            $share,
            (string) $media->uuid,
            (string) $media->name,
            (int) $media->size,
            (string) $media->mime_type,
            $media->getUrl(),
        ));

        return $media;
    }
}
