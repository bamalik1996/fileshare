<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class RecaptchaVerifier
{
    public function isEnabled(): bool
    {
        if ($this->shouldBypass()) {
            return false;
        }

        return filled(config('app.recpatcha.RECAPTCHA_SITE_KEY'))
            && filled(config('app.recpatcha.RECAPTCHA_SECRET_KEY'));
    }

    /**
     * @throws ValidationException
     */
    public function validateRequest(Request $request): void
    {
        if ($this->shouldBypass()) {
            return;
        }

        $request->validate([
            'g-recaptcha-response' => ['required', 'string'],
        ], [
            'g-recaptcha-response.required' => 'Please complete the captcha.',
        ]);

        if (! $this->verify((string) $request->input('g-recaptcha-response'), $request->ip())) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => ['Captcha verification failed. Please try again.'],
            ]);
        }
    }

    public function verify(string $response, ?string $remoteIp = null): bool
    {
        if ($this->shouldBypass()) {
            return true;
        }

        $secret = config('app.recpatcha.RECAPTCHA_SECRET_KEY');

        if (! is_string($secret) || $secret === '') {
            return false;
        }

        if ($response === '') {
            return false;
        }

        $payload = [
            'secret'   => $secret,
            'response' => $response,
        ];

        if ($remoteIp !== null && $remoteIp !== '') {
            $payload['remoteip'] = $remoteIp;
        }

        try {
            $result = Http::asForm()
                ->timeout(10)
                ->post('https://www.google.com/recaptcha/api/siteverify', $payload)
                ->json();
        } catch (\Throwable) {
            return false;
        }

        return (bool) ($result['success'] ?? false);
    }

    private function shouldBypass(): bool
    {
        if (app()->environment('testing')) {
            return true;
        }

        $secret = config('app.recpatcha.RECAPTCHA_SECRET_KEY');

        return ! is_string($secret) || $secret === '';
    }
}
