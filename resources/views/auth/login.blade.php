@extends('layouts.app')

@section('title', 'Login – AirToShare')
@section('description', 'Log in to your AirToShare account to access favourites, higher limits, and saved shares.')

@section('content')
    @include('auth.partials.shell-start', [
        'title' => 'Welcome back',
        'subtitle' => 'Access your shares, favourites, and higher limits.',
        'icon' => 'fas fa-sign-in-alt',
    ])

    <form method="POST" action="{{ route('auth.login') }}" class="auth-form-fields">
        @csrf
        <div class="form-group">
            <label class="form-label" for="email">Email address</label>
            <input class="form-input" type="email" id="email" name="email"
                value="{{ old('email') }}" required autofocus autocomplete="email"
                placeholder="you@example.com">
        </div>

        <div class="form-group">
            <div class="auth-label-row">
                <label class="form-label" for="password">Password</label>
                <a class="auth-inline-link" href="{{ route('auth.forgot') }}">Forgot password?</a>
            </div>
            <input class="form-input" type="password" id="password" name="password"
                required autocomplete="current-password" placeholder="Your password">
        </div>

        <button type="submit" class="form-button" style="width: 100%;">
            <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
            Log in
        </button>
    </form>

    <p class="auth-switch">
        Don&rsquo;t have an account?
        <a href="{{ route('auth.register') }}">Create one free</a>
    </p>

    @include('auth.partials.shell-end')
@endsection
