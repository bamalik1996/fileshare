<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private readonly AccountService $accounts)
    {
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed'],
        ]);

        $this->accounts->register($data['email'], $data['password']);
        $this->accounts->login($data['email'], $data['password']);

        return redirect()->route('verification.notice')
            ->with('status', 'Account created. Please check your email for a verification link.');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function forgotPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::broker('accounts')->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'If that email is registered, we sent password reset instructions.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token'                 => ['required', 'string'],
            'email'                 => ['required', 'string', 'email'],
            'password'              => ['required', 'string', 'min:8', 'max:128', 'confirmed'],
        ]);

        $status = Password::broker('accounts')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Account $account, string $password): void {
                $account->forceFill([
                    'password_hash' => $password,
                ])->setRememberToken(Str::random(60));

                $account->save();

                event(new PasswordReset($account));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('auth.login')
                ->with('status', 'Your password has been reset. You can log in now.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->accounts->login($data['email'], $data['password']);

        $account = $request->user('account');

        if ($account !== null && ! $account->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('status', 'Please verify your email address. We sent you a verification link.');
        }

        return redirect()->intended(route('account.shares'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->accounts->logout();

        return redirect('/')->with('status', 'Logged out.');
    }
}
