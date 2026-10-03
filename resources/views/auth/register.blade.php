@extends('layouts.app')

@section('title', 'Register – AirToShare')
@section('description', 'Create a free AirToShare account for analytics, revoke, file requests, branding, 500 files, 10 GB storage, and 30-day expiry — no credit card.')

@section('content')
    @include('auth.partials.shell-start', [
        'title' => 'Create your free account',
        'subtitle' => 'Analytics, revoke & limits, file requests, branding, and higher ceilings. No credit card.',
        'icon' => 'fas fa-user-plus',
    ])

    <div class="auth-benefits">
        <div class="auth-benefit">
            <i class="fas fa-folder-open" aria-hidden="true"></i>
            <span>500 files</span>
        </div>
        <div class="auth-benefit">
            <i class="fas fa-database" aria-hidden="true"></i>
            <span>10 GB storage</span>
        </div>
        <div class="auth-benefit">
            <i class="fas fa-chart-line" aria-hidden="true"></i>
            <span>Analytics</span>
        </div>
        <div class="auth-benefit">
            <i class="fas fa-inbox" aria-hidden="true"></i>
            <span>File requests</span>
        </div>
    </div>

    <form method="POST" action="{{ route('auth.register') }}" class="auth-form-fields">
        @csrf
        <div class="form-group">
            <label class="form-label" for="email">Email address</label>
            <input class="form-input" type="email" id="email" name="email"
                value="{{ old('email') }}" required autofocus autocomplete="email"
                placeholder="you@example.com">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input class="form-input" type="password" id="password" name="password"
                required autocomplete="new-password" minlength="8" maxlength="128"
                placeholder="At least 8 characters">
            <p class="auth-help">8–128 characters</p>
        </div>

        <div class="form-group">
            <label class="form-label" for="password_confirmation">Confirm password</label>
            <input class="form-input" type="password" id="password_confirmation"
                name="password_confirmation" required autocomplete="new-password"
                placeholder="Repeat your password">
        </div>

        @include('auth.partials.recaptcha')

        <button type="submit" class="form-button" style="width: 100%;">
            <i class="fas fa-user-plus" aria-hidden="true"></i>
            Create account
        </button>
    </form>

    <p class="auth-switch">
        Already have an account?
        <a href="{{ route('auth.login') }}">Log in</a>
    </p>

    @include('auth.partials.shell-end')
    @include('auth.partials.recaptcha-script')
@endsection
