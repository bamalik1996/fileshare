@extends('layouts.app')

@section('title', 'Register – AirToShare')
@section('description', 'Create a free AirToShare account for favourites, 100 files, 1 GB storage, and 30-day expiry options.')

@section('content')
    @include('auth.partials.shell-start', [
        'title' => 'Create your account',
        'subtitle' => 'Save favourites, get higher limits, and keep shares longer.',
        'icon' => 'fas fa-user-plus',
    ])

    <div class="auth-benefits">
        <div class="auth-benefit">
            <i class="fas fa-folder-open" aria-hidden="true"></i>
            <span>100 files</span>
        </div>
        <div class="auth-benefit">
            <i class="fas fa-database" aria-hidden="true"></i>
            <span>1 GB storage</span>
        </div>
        <div class="auth-benefit">
            <i class="fas fa-star" aria-hidden="true"></i>
            <span>Favourites</span>
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
