<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SendCollectFileReceived;
use App\Mail\CollectFileReceived;
use App\Models\Account;
use App\Models\Share;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CollectFileReceivedNotifyTest extends TestCase
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

        Storage::fake('public');
        config()->set('airtoshare.scan_backend', 'null');
        config()->set('airtoshare.active_files_limit_account', 500);
        config()->set('airtoshare.account_storage_limit_bytes', 10 * 1024 * 1024 * 1024);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_collect_upload_queues_owner_email_job(): void
    {
        Queue::fake();

        $account = Account::query()->create([
            'email' => 'owner@example.com',
            'password_hash' => 'secret-password',
            'email_verified_at' => Carbon::now(),
        ]);

        $share = Share::query()->create([
            'owner_type' => Share::OWNER_TYPE_ACCOUNT,
            'owner_id' => (string) $account->id,
            'expires_at' => Carbon::now()->addDays(30),
            'is_collect' => true,
            'collect_slug' => 'inboxnotify01',
            'collect_title' => 'Client files',
        ]);

        $response = $this->post('/collect/inboxnotify01', [
            'file' => UploadedFile::fake()->createWithContent('notes.txt', 'hello collect notify'),
        ], ['Accept' => 'application/json']);

        $response->assertOk()->assertJsonPath('status', 'success');
        $this->assertSame('notes.txt', $response->json('file.name'));
        $expectedSize = (int) $response->json('file.size');

        Queue::assertPushed(SendCollectFileReceived::class, function (SendCollectFileReceived $job) use ($share, $expectedSize) {
            return (int) $job->share->id === (int) $share->id
                && $job->fileName === 'notes.txt'
                && $job->fileSize === $expectedSize;
        });
    }

    public function test_send_collect_file_received_mails_owner(): void
    {
        Mail::fake();

        $account = Account::query()->create([
            'email' => 'owner@example.com',
            'password_hash' => 'secret-password',
            'email_verified_at' => Carbon::now(),
        ]);

        $share = Share::query()->create([
            'owner_type' => Share::OWNER_TYPE_ACCOUNT,
            'owner_id' => (string) $account->id,
            'expires_at' => Carbon::now()->addDays(30),
            'is_collect' => true,
            'collect_slug' => 'inboxnotify02',
            'collect_title' => 'Client files',
        ]);

        $job = new SendCollectFileReceived($share, 'report.pdf', 2048);
        $job->handle(app(NotificationService::class));

        Mail::assertSent(CollectFileReceived::class, function (CollectFileReceived $mail) use ($share) {
            return $mail->hasTo('owner@example.com')
                && $mail->share->is($share)
                && $mail->fileName === 'report.pdf'
                && $mail->fileSize === 2048;
        });
    }
}
