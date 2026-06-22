<?php

namespace App\Providers;

use App\Domain\Principal\IpPrincipal;
use App\Domain\Principal\Principal;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Event::listen(Registered::class, SendEmailVerificationNotification::class);

        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Verify your ' . config('app.name') . ' account')
                ->greeting('Hello!')
                ->line('Thanks for signing up. Please verify your email address by clicking the button below.')
                ->action('Verify Email Address', $url)
                ->line('This link expires in 60 minutes.')
                ->line('If you did not create an account, no further action is required.');
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            return url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Reset your ' . config('app.name') . ' password')
                ->greeting('Hello!')
                ->line('You are receiving this email because we received a password reset request for your account.')
                ->action('Reset Password', $url)
                ->line('This link expires in 60 minutes.')
                ->line('If you did not request a password reset, no further action is required.');
        });

        // Owner morph aliases for Share (design.md > Data Models > shares).
        // The `ip` token is intentionally absent from this map: IP principals
        // are pure value objects with no backing Eloquent model, so attempts
        // to eager-load `owner` for an IP-owned share resolve to null. The
        // `room` and `account` aliases are only registered if the supporting
        // models exist (task 2.4).
        $aliases = [];
        if (class_exists(\App\Models\Room::class)) {
            $aliases['room'] = \App\Models\Room::class;
        }
        if (class_exists(\App\Models\Account::class)) {
            $aliases['account'] = \App\Models\Account::class;
        }
        if ($aliases !== []) {
            Relation::morphMap($aliases);
        }

        // Register the `$request->principal()` macro so controllers,
        // services, and tests can read the request-scoped principal
        // without poking at Symfony's typed attribute bag directly.
        // The `ResolvePrincipal` middleware (bound globally in
        // bootstrap/app.php) is responsible for populating the
        // attribute on every request; if we are called outside the
        // middleware stack (e.g. during a console command or an early
        // test boot) we fall back to an IpPrincipal derived from the
        // request IP so the macro never returns null.
        Request::macro('principal', function (): Principal {
            /** @var \Illuminate\Http\Request $this */
            $resolved = $this->attributes->get('principal');

            if ($resolved instanceof Principal) {
                return $resolved;
            }

            return new IpPrincipal((string) $this->ip());
        });

        Response::macro('apiOk', function (mixed $data = null, int $status = 200) {
            return response()->json([
                'status' => 'success',
                'data'   => $data,
            ], $status);
        });

        Response::macro('apiError', function (string $message, array $errors = [], int $status = 400) {
            return response()->json([
                'status'  => 'error',
                'message' => $message,
                'errors'  => $errors,
            ], $status);
        });
    }
}
