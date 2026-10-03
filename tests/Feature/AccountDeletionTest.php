<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
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

    private function makeVerifiedAccount(string $email = 'user@example.com'): Account
    {
        return Account::query()->create([
            'email' => $email,
            'password_hash' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
    }

    public function test_destroy_schedules_deletion_instead_of_immediate_purge(): void
    {
        $account = $this->makeVerifiedAccount();

        $this->actingAs($account, 'account')
            ->from(route('account.shares'))
            ->delete(route('account.destroy'), [
                'confirm_email' => $account->email,
            ])
            ->assertRedirect(route('account.shares'))
            ->assertSessionHas('status');

        $account->refresh();
        $this->assertNotNull($account->deletion_scheduled_at);
        $this->assertTrue(
            $account->deletion_scheduled_at->between(
                now()->addHours(AccountService::DELETION_GRACE_HOURS)->subMinute(),
                now()->addHours(AccountService::DELETION_GRACE_HOURS)->addMinute(),
            )
        );
        $this->assertDatabaseHas('accounts', ['email' => $account->email]);
    }

    public function test_destroy_rejects_mismatched_confirmation_email(): void
    {
        $account = $this->makeVerifiedAccount();

        $this->actingAs($account, 'account')
            ->from(route('account.shares'))
            ->delete(route('account.destroy'), [
                'confirm_email' => 'wrong@example.com',
            ])
            ->assertRedirect(route('account.shares'))
            ->assertSessionHasErrors('confirm_email');

        $account->refresh();
        $this->assertNull($account->deletion_scheduled_at);
    }

    public function test_cancel_deletion_clears_schedule(): void
    {
        $account = $this->makeVerifiedAccount();
        $account->deletion_scheduled_at = now()->addHours(48);
        $account->save();

        $this->actingAs($account, 'account')
            ->post(route('account.cancel-deletion'))
            ->assertRedirect(route('account.shares'));

        $account->refresh();
        $this->assertNull($account->deletion_scheduled_at);
    }

    public function test_purge_command_deletes_only_due_accounts(): void
    {
        $due = $this->makeVerifiedAccount('due@example.com');
        $due->deletion_scheduled_at = now()->subMinute();
        $due->save();

        $pending = $this->makeVerifiedAccount('pending@example.com');
        $pending->deletion_scheduled_at = now()->addDay();
        $pending->save();

        $safe = $this->makeVerifiedAccount('safe@example.com');

        $this->artisan('accounts:purge-scheduled-deletions')
            ->assertSuccessful();

        $this->assertDatabaseMissing('accounts', ['email' => 'due@example.com']);
        $this->assertDatabaseHas('accounts', ['email' => 'pending@example.com']);
        $this->assertDatabaseHas('accounts', ['email' => 'safe@example.com']);
    }
}
