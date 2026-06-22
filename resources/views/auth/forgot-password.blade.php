@extends('layouts.app')

@section('title', 'Forgot Password – AirToShare')
@section('description', 'Reset your AirToShare account password.')

@section('content')
    @include('auth.partials.shell-start', [
        'title' => 'Reset your password',
        'subtitle' => 'Enter the email on your account and we will send reset instructions.',
        'icon' => 'fas fa-key',
    ])

    <form method="POST" action="{{ route('auth.forgot') }}" class="auth-form-fields">
        @csrf
        <div class="form-group">
            <label class="form-label" for="email">Email address</label>
            <input class="form-input" type="email" id="email" name="email"
                value="{{ old('email') }}" required autofocus autocomplete="email"
                placeholder="you@example.com">
        </div>

        <button type="submit" class="form-button" style="width: 100%;">
            <i class="fas fa-paper-plane" aria-hidden="true"></i>
            Send reset link
        </button>
    </form>

    <p class="auth-switch">
        Remember your password?
        <a href="{{ route('auth.login') }}">Back to log in</a>
    </p>

    @include('auth.partials.shell-end', [
        'footerLinks' => [
            ['href' => route('auth.register'), 'label' => 'Create a new account'],
        ],
    ])
@endsection
