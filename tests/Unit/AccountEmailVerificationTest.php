<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Account;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountEmailVerificationTest extends TestCase
{
    public function test_registration_sends_verification_notification(): void
    {
        Notification::fake();

        $account = new Account([
            'email' => 'verify@example.com',
        ]);
        $account->id = 42;

        (new SendEmailVerificationNotification())->handle(new Registered($account));

        Notification::assertSentTo($account, VerifyEmail::class);
    }
}
