<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\QrGenerationException;
use App\Http\Controllers\QrCodeController;
use App\Models\Share;
use App\Services\QrGenerator;
use App\Services\ShareService;
use BaconQrCode\Encoder\QrCode;
use BaconQrCode\Renderer\RendererInterface;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

/**
 * Feature tests for {@see QrCodeController} (task 7.2).
 *
 * Covers acceptance criteria:
 *   1.1 - QR PNG is produced for the resolved Share URL.
 *   1.4 - `?download=1` produces an attachment disposition with the
 *         `share-{slug}.png` filename; without it the response is
 *         inline.
 *   1.5 - generation failure renders the URL-text + error-banner
 *         fallback HTML and does NOT offer a PNG download.
 *   1.6 - generation failure logs `share_id` plus the underlying reason
 *         alongside the share uuid.
 *
 * The suite uses an in-memory SQLite schema (matching ShareServiceTest)
 * so route resolution touches a real Share row without leaning on the
 * production migrations or polluting the dev database.
 */
class QrCodeControllerTest extends TestCase
{
    protected function setUp(): void
    {
        // Pin the DB connection BEFORE the framework boots so the
        // application picks up the in-memory SQLite settings.
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_CONNECTION']    = 'sqlite';
        $_ENV['DB_DATABASE']      = ':memory:';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE']   = ':memory:';

        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver'                  => 'sqlite',
            'database'                => ':memory:',
            'prefix'                  => '',
            'foreign_key_constraints' => false,
        ]);
        \DB::purge('sqlite');

        // Pin every config key the gate consumes so tests do not depend
        // on default values drifting in config/airtoshare.php.
        config()->set('airtoshare.active_files_limit_ip', 50);
        config()->set('airtoshare.active_files_limit_account', 100);
        config()->set('airtoshare.account_storage_limit_bytes', 1024 * 1024 * 1024);
        config()->set('airtoshare.account_max_expiry_option', '30d');

        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->string('owner_type');
            $table->string('owner_id');
            $table->longText('text_content')->nullable();
            $table->longText('markdown_source')->nullable();
            $table->string('password_hash')->nullable();
            $table->timestamp('expires_at');
            $table->char('public_slug', 12)->nullable()->unique();
            $table->unsignedInteger('public_view_count')->default(0);
            $table->boolean('is_e2ee')->default(false);
            $table->boolean('is_favourite')->default(false);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_returns_inline_png_for_share_resolved_by_uuid(): void
    {
        $share = $this->makeShare();

        $response = $this->get('/qr/' . $share->uuid);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');

        // PNG signature: \x89 P N G \r \n \x1a \n
        $this->assertSame("\x89PNG\r\n\x1a\n", substr((string) $response->getContent(), 0, 8));

        // Acceptance criterion 1.4 (default branch): no `?download=1`
        // means the disposition is `inline`.
        $this->assertStringStartsWith(
            'inline;',
            (string) $response->headers->get('Content-Disposition')
        );
        $this->assertStringContainsString(
            'filename="share-' . $share->uuid . '.png"',
            (string) $response->headers->get('Content-Disposition')
        );
    }

    public function test_returns_attachment_png_when_download_query_is_one(): void
    {
        $share = $this->makeShare();

        $response = $this->get('/qr/' . $share->uuid . '?download=1');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');

        // Acceptance criterion 1.4: `?download=1` switches the
        // disposition to `attachment` with `share-{slug}.png` filename.
        $disposition = (string) $response->headers->get('Content-Disposition');
        $this->assertStringStartsWith('attachment;', $disposition);
        $this->assertStringContainsString(
            'filename="share-' . $share->uuid . '.png"',
            $disposition
        );
    }

    public function test_resolves_share_by_public_slug(): void
    {
        $share = $this->makeShare(['public_slug' => 'pubSlug12345']);

        $response = $this->get('/qr/pubSlug12345');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertStringContainsString(
            'filename="share-pubSlug12345.png"',
            (string) $response->headers->get('Content-Disposition')
        );
    }

    public function test_other_download_values_render_inline(): void
    {
        // Only `download=1` triggers the attachment disposition; any
        // other value (including absent, `0`, `true`, `yes`) leaves the
        // response inline so a casual `?download` toggle cannot be
        // smuggled in by a query-string-aware caller.
        $share = $this->makeShare();

        foreach (['0', 'true', 'yes', ''] as $value) {
            $response = $this->get('/qr/' . $share->uuid . '?download=' . $value);
            $response->assertStatus(200);
            $this->assertStringStartsWith(
                'inline;',
                (string) $response->headers->get('Content-Disposition'),
                "download={$value} should render inline"
            );
        }
    }

    public function test_returns_404_for_unknown_slug(): void
    {
        $response = $this->get('/qr/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404);
    }

    public function test_returns_404_for_expired_share(): void
    {
        // Acceptance criteria 3.4 + 3.8 (delegated through
        // ShareService::loadShare()): an expired share surfaces as 404
        // to the QR endpoint, identical to a never-existed share. The
        // delete-on-read side-effect itself is exercised exhaustively
        // by ShareServiceTest::test_load_share_deletes_expired_share_and_throws;
        // here we only need to confirm that the controller does not
        // intercept the ShareExpiredException raised by loadShare().
        Carbon::setTestNow('2030-01-01 12:00:00');
        $share = $this->makeShare(['expires_at' => Carbon::now()->subSecond()]);

        $response = $this->get('/qr/' . $share->uuid);

        $response->assertStatus(404);
    }

    public function test_renders_fallback_view_without_download_when_generation_fails(): void
    {
        // Swap in a renderer that always throws. QrGenerator::generateOrFail()
        // wraps the failure in QrGenerationException and the controller
        // renders the URL-text + error-banner fallback view.
        $this->bindFailingGenerator(new RuntimeException('GD allocation failed'));

        $share = $this->makeShare();

        Log::spy();

        $response = $this->get('/qr/' . $share->uuid);

        // Acceptance criterion 1.5: fallback uses HTML, not PNG bytes,
        // and explicitly indicates failure (HTTP 503 so the surrounding
        // <img onerror> wiring fires).
        $response->assertStatus(503);
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');

        $body = (string) $response->getContent();
        $this->assertStringContainsString('QR code unavailable', $body);

        // Acceptance criterion 1.5 (must NOT offer a download): the
        // fallback body must not contain a download anchor or a link
        // back to the QR endpoint with `?download=1`.
        $this->assertStringNotContainsString('?download=1', $body);
        $this->assertStringNotContainsString('<a download', $body);

        // The Share URL must appear so the user can still open the
        // share manually.
        $this->assertStringContainsString(url('/s/' . $share->uuid), $body);
    }

    public function test_logs_share_id_and_reason_when_generation_fails(): void
    {
        // Acceptance criterion 1.6: failure logs share id + reason.
        $this->bindFailingGenerator(new RuntimeException('boom-from-renderer'));

        $share = $this->makeShare();

        Log::spy();

        $this->get('/qr/' . $share->uuid)->assertStatus(503);

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $message, array $context) use ($share): bool {
                return str_contains($message, 'QR code generation failed')
                    && ($context['share_id'] ?? null) === $share->id
                    && ($context['share_uuid'] ?? null) === $share->uuid
                    && str_contains((string) ($context['reason'] ?? ''), 'boom-from-renderer');
            });
    }

    public function test_fallback_for_public_slug_links_to_public_route(): void
    {
        // When the share is resolved by its public slug, the fallback
        // text should display the public-gallery URL (the link the
        // recipient actually has) rather than the owner-facing /s/{uuid}.
        $this->bindFailingGenerator(new RuntimeException('boom'));

        $share = $this->makeShare(['public_slug' => 'pubSlug12345']);

        $response = $this->get('/qr/pubSlug12345');

        $response->assertStatus(503);
        $body = (string) $response->getContent();
        $this->assertStringContainsString(url('/p/pubSlug12345'), $body);
        $this->assertStringNotContainsString('/s/' . $share->uuid, $body);
    }

    /**
     * Replace the QrGenerator the controller resolves with a generator
     * whose underlying renderer always throws. Lets us drive the
     * failure branch without relying on stochastic GD failures.
     */
    private function bindFailingGenerator(\Throwable $error): void
    {
        $renderer = new class($error) implements RendererInterface
        {
            public function __construct(private \Throwable $error)
            {
            }

            public function render(QrCode $qrCode): string
            {
                throw $this->error;
            }
        };

        $this->app->bind(QrGenerator::class, function () use ($renderer) {
            return new QrGenerator(renderer: $renderer);
        });

        // Also rebind the controller so its constructor injection picks
        // up the failing generator (the controller is resolved fresh
        // per request in Laravel's HTTP kernel, so this is enough).
        $this->app->bind(QrCodeController::class, function ($app) use ($renderer) {
            return new QrCodeController(
                $app->make(ShareService::class),
                new QrGenerator(renderer: $renderer),
            );
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeShare(array $attributes = []): Share
    {
        return Share::create(array_merge([
            'owner_type' => 'ip',
            'owner_id'   => '203.0.113.1',
            'expires_at' => Carbon::now()->addDay(),
        ], $attributes));
    }
}
