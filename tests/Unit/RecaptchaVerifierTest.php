<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\RecaptchaVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecaptchaVerifierTest extends TestCase
{
    public function test_bypasses_validation_in_testing_environment(): void
    {
        $verifier = new RecaptchaVerifier();

        $request = Request::create('/login', 'POST', [
            'email'    => 'user@example.com',
            'password' => 'secret',
        ]);

        $verifier->validateRequest($request);

        $this->assertFalse($verifier->isEnabled());
    }

    public function test_rejects_missing_captcha_when_enabled(): void
    {
        config([
            'app.recpatcha.RECAPTCHA_SITE_KEY'   => 'site-key',
            'app.recpatcha.RECAPTCHA_SECRET_KEY' => 'secret-key',
        ]);

        app()->detectEnvironment(fn () => 'production');

        $verifier = new RecaptchaVerifier();

        $request = Request::create('/login', 'POST', [
            'email'    => 'user@example.com',
            'password' => 'secret',
        ]);

        try {
            $verifier->validateRequest($request);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Please complete the captcha.',
                $exception->errors()['g-recaptcha-response'][0]
            );
        }
    }

    public function test_verify_calls_google_and_returns_success(): void
    {
        config([
            'app.recpatcha.RECAPTCHA_SECRET_KEY' => 'secret-key',
        ]);

        app()->detectEnvironment(fn () => 'production');

        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $verifier = new RecaptchaVerifier();

        $this->assertTrue($verifier->verify('token-value', '127.0.0.1'));
    }
}
