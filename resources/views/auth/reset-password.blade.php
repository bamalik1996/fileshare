@extends('layouts.app')

@section('title', 'Reset Password – AirToShare')
@section('robots', 'noindex, nofollow')
@section('description', 'Choose a new password for your AirToShare account.')

@section('content')
    @include('auth.partials.shell-start', [
        'title' => 'Choose a new password',
        'subtitle' => 'Enter your email and a new password below.',
        'icon' => 'fas fa-lock',
    ])

    <form method="POST" action="{{ route('password.update') }}" class="auth-form-fields">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="form-group">
            <label class="form-label" for="email">Email address</label>
            <input class="form-input" type="email" id="email" name="email"
                value="{{ old('email', $email) }}" required autofocus autocomplete="email"
                placeholder="you@example.com">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">New password</label>
            <input class="form-input" type="password" id="password" name="password"
                required autocomplete="new-password" placeholder="At least 8 characters">
        </div>

        <div class="form-group">
            <label class="form-label" for="password_confirmation">Confirm password</label>
            <input class="form-input" type="password" id="password_confirmation"
                name="password_confirmation" required autocomplete="new-password"
                placeholder="Repeat new password">
        </div>

        @include('auth.partials.recaptcha')

        <button type="submit" class="form-button" style="width: 100%;">
            <i class="fas fa-check" aria-hidden="true"></i>
            Update password
        </button>
    </form>

    <p class="auth-switch">
        <a href="{{ route('auth.login') }}">Back to log in</a>
    </p>

    @include('auth.partials.shell-end')
    @include('auth.partials.recaptcha-script')
@endsection
