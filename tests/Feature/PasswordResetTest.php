<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';

        parent::setUp();

        $this->artisan('migrate', ['--force' => true]);
    }

    public function test_forgot_password_sends_reset_notification_for_existing_account(): void
    {
        Notification::fake();

        Account::query()->create([
            'email'         => 'bilalmalik531996@gmail.com',
            'password_hash' => Hash::make('old-password-123'),
        ]);

        $this->post(route('auth.forgot'), [
            'email' => 'bilalmalik531996@gmail.com',
        ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $account = Account::query()->where('email', 'bilalmalik531996@gmail.com')->first();
        $this->assertNotNull($account);

        Notification::assertSentTo($account, ResetPassword::class);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $account = Account::query()->create([
            'email'         => 'reset@example.com',
            'password_hash' => Hash::make('old-password-123'),
        ]);

        $token = Password::broker('accounts')->createToken($account);

        $this->post(route('password.update'), [
            'token'                 => $token,
            'email'                 => 'reset@example.com',
            'password'              => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ])
            ->assertRedirect(route('auth.login'))
            ->assertSessionHas('status');

        $account->refresh();
        $this->assertTrue(Hash::check('new-password-456', $account->password_hash));
    }
}
