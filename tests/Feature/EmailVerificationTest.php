<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
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

    public function test_signed_verification_link_marks_email_verified(): void
    {
        $account = Account::query()->create([
            'email'         => 'verified@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id'   => $account->id,
                'hash' => sha1($account->getEmailForVerification()),
            ]
        );

        $this->get($url)
            ->assertRedirect(route('auth.login'))
            ->assertSessionHas('status');

        $account->refresh();
        $this->assertNotNull($account->email_verified_at);
    }

    public function test_unverified_account_cannot_access_my_shares(): void
    {
        $account = Account::query()->create([
            'email'         => 'pending@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $this->actingAs($account, 'account')
            ->get(route('account.shares'))
            ->assertRedirect(route('verification.notice'));
    }
}
