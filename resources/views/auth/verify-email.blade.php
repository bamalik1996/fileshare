@extends('layouts.app')

@section('title', 'Verify Email – AirToShare')
@section('description', 'Verify your AirToShare account email address.')

@section('content')
    @include('auth.partials.shell-start', [
        'title' => 'Verify your email',
        'subtitle' => 'We sent a verification link to your inbox. Click it to activate your account.',
        'icon' => 'fas fa-envelope-circle-check',
    ])

    <div class="auth-verify-panel">
        <p class="auth-help">
            Sent to <strong>{{ $email }}</strong>. Check spam or junk if you do not see it within a few minutes.
        </p>

        <form method="POST" action="{{ route('verification.send') }}" class="auth-form-fields">
            @csrf
            <button type="submit" class="form-button" style="width: 100%;">
                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                Resend verification email
            </button>
        </form>

        <form method="POST" action="{{ route('auth.logout') }}" class="auth-form-fields" style="margin-top: 0.75rem;">
            @csrf
            <button type="submit" class="modern-btn secondary" style="width: 100%; justify-content: center;">
                <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                Log out
            </button>
        </form>
    </div>

    @include('auth.partials.shell-end')
@endsection
