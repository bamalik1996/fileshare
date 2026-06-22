<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\UploadSession;
use App\Services\ChunkedUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Background assembly of a completed chunked upload (task 14.3).
 *
 * Concatenates persisted chunk files in ascending index order and
 * registers the result with Spatie Media Library on the target Share.
 */
class AssembleChunkedUpload implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public UploadSession $session,
    ) {
    }

    public function handle(ChunkedUploadService $chunkedUploadService): void
    {
        // Reload in case the row was updated since dispatch.
        $session = UploadSession::query()->find($this->session->id);

        if ($session === null || $session->share_id === null) {
            Log::warning('AssembleChunkedUpload: session missing or unbound', [
                'session_id' => $this->session->id,
            ]);

            return;
        }

        try {
            $chunkedUploadService->performAssembly($session);
        } catch (\Throwable $e) {
            Log::error('AssembleChunkedUpload: assembly failed', [
                'session_uuid' => $session->uuid,
                'error'        => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
