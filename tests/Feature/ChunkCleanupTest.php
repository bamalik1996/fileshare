<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\CleanupExpiredUploadSessions;
use App\Models\UploadSession;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Chunk files must not outlive their upload:
 *
 *  - a successful inline assembly deletes the session's chunk directory;
 *  - the hourly cleanup job clears directories left by completed uploads
 *    (after a 1 h grace period) and old directories with no session row,
 *    while leaving in-flight uploads alone.
 */
class ChunkCleanupTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';

        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        \DB::purge('sqlite');
        \DB::reconnect('sqlite');

        $this->artisan('migrate', ['--force' => true]);

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_successful_assembly_deletes_the_chunk_directory(): void
    {
        config()->set('airtoshare.chunked_assembly_queue', false);

        $payload = 'chunk cleanup payload';

        $sessionId = $this->postJson('/api/v1/chunked-upload/start', [
            'total_chunks' => 1,
            'total_bytes' => strlen($payload),
            'filename' => 'notes.txt',
            'mime' => 'text/plain',
        ])->assertOk()->json('session_id');

        $this->post('/api/v1/chunked-upload/chunk', [
            'session_id' => $sessionId,
            'chunk_index' => 0,
            'total_chunks' => 1,
            'sha256' => hash('sha256', $payload),
            'chunk' => UploadedFile::fake()->createWithContent('chunk-0.bin', $payload),
        ], ['Accept' => 'application/json'])->assertOk();

        Storage::disk('local')->assertExists("chunks/{$sessionId}/0.bin");

        $this->postJson('/api/v1/chunked-upload/complete', ['session_id' => $sessionId])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertNotNull(UploadSession::query()->where('uuid', $sessionId)->value('completed_at'));
        $this->assertFalse(
            Storage::disk('local')->directoryExists("chunks/{$sessionId}"),
            'chunk directory should be deleted once the file is assembled'
        );
    }

    public function test_cleanup_job_clears_leftovers_and_keeps_in_flight_uploads(): void
    {
        $disk = Storage::disk('local');

        $completedLongAgo = $this->makeSession(completedAt: now()->subHours(2));
        $completedJustNow = $this->makeSession(completedAt: now()->subMinutes(10));
        $inFlight = $this->makeSession(completedAt: null, firstChunkAt: now()->subMinutes(5));

        $oldOrphan = (string) Str::uuid();
        $newOrphan = (string) Str::uuid();

        foreach ([$completedLongAgo, $completedJustNow, $inFlight, $oldOrphan, $newOrphan] as $uuid) {
            $disk->put("chunks/{$uuid}/0.bin", 'x');
        }
        touch($disk->path("chunks/{$oldOrphan}"), now()->subDays(2)->getTimestamp());

        (new CleanupExpiredUploadSessions())->handle();

        $this->assertFalse($disk->directoryExists("chunks/{$completedLongAgo}"), 'completed > 1h ago: cleared');
        $this->assertFalse($disk->directoryExists("chunks/{$oldOrphan}"), 'orphan > 24h old: cleared');

        $this->assertTrue($disk->directoryExists("chunks/{$completedJustNow}"), 'completed < 1h ago: kept (queued assembly grace)');
        $this->assertTrue($disk->directoryExists("chunks/{$inFlight}"), 'in-flight upload: kept');
        $this->assertTrue($disk->directoryExists("chunks/{$newOrphan}"), 'fresh orphan: kept');

        // Completed sessions keep their row; only the chunk files go.
        $this->assertTrue(UploadSession::query()->where('uuid', $completedLongAgo)->exists());
    }

    private function makeSession(?\DateTimeInterface $completedAt, ?\DateTimeInterface $firstChunkAt = null): string
    {
        $session = UploadSession::query()->create([
            'filename' => 'file.bin',
            'mime' => 'application/octet-stream',
            'total_bytes' => 1,
            'total_chunks' => 1,
            'first_chunk_at' => $firstChunkAt ?? now()->subHours(3),
            'completed_at' => $completedAt,
        ]);

        return (string) $session->uuid;
    }
}
