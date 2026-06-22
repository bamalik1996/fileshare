<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\SharePasswordGate;
use App\Models\Share;
use App\Services\PasswordManager;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class SharePasswordVerifyTest extends TestCase
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
            $table->boolean('notify_browser')->default(false);
            $table->string('notify_email')->nullable();
            $table->timestamps();
        });
    }

    public function test_verify_password_grants_access_via_session(): void
    {
        $passwordManager = app(PasswordManager::class);
        $plain = 'secret99';

        $share = Share::query()->create([
            'uuid'          => (string) Str::uuid(),
            'owner_type'    => Share::OWNER_TYPE_IP,
            'owner_id'      => '10.0.0.99',
            'text_content'  => 'Protected content',
            'password_hash' => $passwordManager->hash($plain),
            'expires_at'    => Carbon::now()->addDay(),
        ]);

        $this->postJson(route('shares.verify-password', ['share' => $share->uuid]), [
            'password' => $plain,
        ])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $map = session(SharePasswordGate::SESSION_KEY, []);
        $this->assertIsArray($map);
        $this->assertTrue($map[$share->id] ?? false);
    }

    public function test_wrong_password_returns_generic_error(): void
    {
        $passwordManager = app(PasswordManager::class);

        $share = Share::query()->create([
            'uuid'          => (string) Str::uuid(),
            'owner_type'    => Share::OWNER_TYPE_IP,
            'owner_id'      => '10.0.0.1',
            'text_content'  => 'Hidden',
            'password_hash' => $passwordManager->hash('correct1'),
            'expires_at'    => Carbon::now()->addDay(),
        ]);

        $this->postJson(route('shares.verify-password', ['share' => $share->uuid]), [
            'password' => 'wrong12',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'status'  => 'error',
                'message' => SharePasswordGate::ERROR_MESSAGE,
            ]);
    }

    public function test_owner_can_update_share_password(): void
    {
        Share::query()->create([
            'uuid'         => (string) Str::uuid(),
            'owner_type'   => Share::OWNER_TYPE_IP,
            'owner_id'     => '127.0.0.1',
            'text_content' => 'Mine',
            'expires_at'   => Carbon::now()->addDay(),
        ]);

        $this->postJson(route('share.password.update'), [
            'password' => 'newpass9',
        ])
            ->assertOk()
            ->assertJson([
                'status'       => 'success',
                'has_password' => true,
            ]);

        $share = Share::query()->where('owner_id', '127.0.0.1')->first();
        $this->assertNotNull($share);
        $this->assertTrue($share->hasPassword());
        $this->assertTrue(app(PasswordManager::class)->verify('newpass9', (string) $share->password_hash));
    }
}
