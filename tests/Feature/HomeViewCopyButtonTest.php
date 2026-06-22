<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Share;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Feature tests for the server-rendered "Copy" button in the home view
 * (task 9.2, Requirement 5.1).
 *
 * Acceptance criterion 5.1 is unambiguous: the Copy button must be
 * rendered next to the text panel iff the active Share's text content
 * is at least one character long. The decision is therefore made on
 * the server using the resolved $share aggregate; this test pins that
 * contract by inspecting the rendered HTML directly.
 *
 * The suite runs against an in-memory SQLite schema mirroring
 * QrCodeControllerTest so the home controller's principal-keyed lookup
 * touches a real Share row without depending on the production
 * database.
 */
class HomeViewCopyButtonTest extends TestCase
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

    public function test_copy_button_is_absent_when_no_share_exists_for_principal(): void
    {
        // Guest visit, no share row at all → the conditional must
        // evaluate false and the button must not appear in the DOM.
        $response = $this->get('/');

        $response->assertStatus(200);
        $body = (string) $response->getContent();
        $this->assertStringNotContainsString('id="copyTextBtn"', $body);
        $this->assertStringNotContainsString('data-copy="#textInput"', $body);
    }

    public function test_copy_button_is_absent_when_share_text_content_is_null(): void
    {
        // A share exists for the principal but has no text yet. Per
        // Requirement 5.1 the button must remain hidden.
        $this->makeShare(['text_content' => null]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])->get('/');

        $response->assertStatus(200);
        $body = (string) $response->getContent();
        $this->assertStringNotContainsString('id="copyTextBtn"', $body);
    }

    public function test_copy_button_is_absent_when_share_text_content_is_empty_string(): void
    {
        // Empty string is "0 characters" — strictly less than the
        // 1-character threshold mandated by Requirement 5.1.
        $this->makeShare(['text_content' => '']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])->get('/');

        $response->assertStatus(200);
        $body = (string) $response->getContent();
        $this->assertStringNotContainsString('id="copyTextBtn"', $body);
    }

    public function test_copy_button_is_present_for_single_character_share_text(): void
    {
        // The boundary case: exactly one character is enough to flip
        // the conditional to true (Requirement 5.1 reads "at least 1
        // character").
        $this->makeShare(['text_content' => 'x']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])->get('/');

        $response->assertStatus(200);
        $body = (string) $response->getContent();
        $this->assertStringContainsString('id="copyTextBtn"', $body);
        // The button must be wired to the clipboard module via the
        // data-copy hook (no per-page JS handler is added by 9.2).
        $this->assertStringContainsString('data-copy="#textInput"', $body);
    }

    public function test_copy_button_is_present_for_multi_character_share_text(): void
    {
        $this->makeShare(['text_content' => "hello world\nwith newline"]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])->get('/');

        $response->assertStatus(200);
        $body = (string) $response->getContent();
        $this->assertStringContainsString('id="copyTextBtn"', $body);
        $this->assertStringContainsString('data-copy="#textInput"', $body);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeShare(array $attributes = []): Share
    {
        return Share::create(array_merge([
            'owner_type' => 'ip',
            // Match the loopback IP that PHPUnit's test request reports
            // for `request->ip()` so HomeController's principal-keyed
            // lookup finds this row.
            'owner_id'   => '127.0.0.1',
            'expires_at' => Carbon::now()->addDay(),
        ], $attributes));
    }
}
