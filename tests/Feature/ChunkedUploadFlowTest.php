<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\AssembleChunkedUpload;
use App\Models\UploadSession;
use App\Services\ChunkedUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChunkedUploadFlowTest extends TestCase
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
    }

    public function test_chunked_upload_start_chunk_complete_for_allowed_file(): void
    {
        Queue::fake();

        $payload = 'hello chunked upload test payload';
        $sha = hash('sha256', $payload);

        $start = $this->postJson('/api/v1/chunked-upload/start', [
            'total_chunks' => 1,
            'total_bytes' => strlen($payload),
            'filename' => 'notes.txt',
            'mime' => 'text/plain',
        ]);

        $start->assertOk()->assertJsonPath('status', 'success');
        $sessionId = $start->json('session_id');
        $this->assertNotEmpty($sessionId);

        $chunkFile = UploadedFile::fake()->createWithContent('chunk-0.bin', $payload);

        $this->post('/api/v1/chunked-upload/chunk', [
            'session_id' => $sessionId,
            'chunk_index' => 0,
            'total_chunks' => 1,
            'sha256' => $sha,
            'chunk' => $chunkFile,
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->getJson('/api/v1/chunked-upload/status/'.$sessionId)
            ->assertOk()
            ->assertJsonPath('received_indexes', [0]);

        $complete = $this->postJson('/api/v1/chunked-upload/complete', [
            'session_id' => $sessionId,
        ]);

        $complete->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('session_id', $sessionId);

        Queue::assertPushed(AssembleChunkedUpload::class);

        $session = UploadSession::query()->where('uuid', $sessionId)->first();
        $this->assertNotNull($session);
        $this->assertNotNull($session->completed_at);

        // Run assembly synchronously the same way the job would.
        $media = app(ChunkedUploadService::class)->performAssembly($session->fresh());
        $this->assertNotNull($media);
        $this->assertSame('notes.txt', $media->file_name);
        $this->assertSame(strlen($payload), (int) $media->size);
    }

    public function test_chunked_upload_rejects_executable_type(): void
    {
        $this->postJson('/api/v1/chunked-upload/start', [
            'total_chunks' => 1,
            'total_bytes' => 12,
            'filename' => 'setup.exe',
            'mime' => 'application/octet-stream',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'File type not allowed.');
    }

    public function test_chunked_upload_allows_xlsx_by_extension(): void
    {
        $this->postJson('/api/v1/chunked-upload/start', [
            'total_chunks' => 1,
            'total_bytes' => 20,
            'filename' => 'report.xlsx',
            'mime' => 'application/octet-stream',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');
    }
}
