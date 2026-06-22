<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        $account = $request->user('account');

        if ($account === null) {
            return redirect()->route('auth.login');
        }

        if ($account->hasVerifiedEmail()) {
            return redirect()->route('account.shares');
        }

        return view('auth.verify-email', [
            'email' => $account->email,
        ]);
    }

    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This verification link is invalid or has expired.');
        }

        $account = Account::query()->findOrFail($id);

        if (! hash_equals($hash, sha1($account->getEmailForVerification()))) {
            abort(403, 'This verification link is invalid.');
        }

        if (! $account->hasVerifiedEmail()) {
            $account->markEmailAsVerified();
            event(new Verified($account));
        }

        if (auth('account')->check() && (int) auth('account')->id() === (int) $account->id) {
            return redirect()->route('account.shares')
                ->with('status', 'Your email address has been verified.');
        }

        return redirect()->route('auth.login')
            ->with('status', 'Your email address has been verified. You can now log in.');
    }

    public function send(Request $request): RedirectResponse
    {
        $account = $request->user('account');

        if ($account === null) {
            return redirect()->route('auth.login');
        }

        if ($account->hasVerifiedEmail()) {
            return redirect()->route('account.shares');
        }

        $account->sendEmailVerificationNotification();

        return back()->with('status', 'A new verification link has been sent to your email address.');
    }
}
