@extends('layouts.app')

@section('title', 'Password required – AirToShare')
@section('robots', 'noindex, nofollow')
@section('description', 'This share is password protected. Enter the password to continue.')

@section('content')
    <div class="password-gate-page">
        <div class="modern-card password-gate-card">
            <div class="password-gate-icon" aria-hidden="true">
                <i class="fas fa-lock"></i>
            </div>
            <h1 class="password-gate-title">Password required</h1>
            <p class="password-gate-subtitle">
                This {{ ($type ?? 'share') === 'room' ? 'room' : 'share' }} is protected.
                Enter the password shared with you to view the content.
            </p>

            <div class="message error password-gate-error hidden" id="passwordGateError" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <span id="passwordGateErrorText">Password required</span>
            </div>

            <form id="passwordGateForm" class="password-gate-form" novalidate
                data-verify-url="{{ ($type ?? 'share') === 'room'
                    ? url('/api/v1/rooms/' . urlencode($identifier) . '/verify-password')
                    : url('/api/v1/shares/' . urlencode($identifier) . '/verify-password') }}"
                data-return-url="{{ $returnUrl ?? url('/') }}">
                @csrf
                <label class="form-label" for="sharePasswordInput">Password</label>
                <input class="form-input" type="password" id="sharePasswordInput" name="password"
                    required autocomplete="current-password" minlength="6" maxlength="128"
                    placeholder="Enter password" autofocus>
                <p class="password-gate-hint">6–128 characters. Wrong passwords show a generic error.</p>
                <button type="submit" class="modern-btn password-gate-submit" id="passwordGateSubmit">
                    <i class="fas fa-unlock" aria-hidden="true"></i>
                    Continue
                </button>
            </form>

            <p class="password-gate-footer">
                <a href="{{ url('/') }}">&larr; Back to AirToShare</a>
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('passwordGateForm');
            if (!form) return;

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var btn = document.getElementById('passwordGateSubmit');
                var err = document.getElementById('passwordGateError');
                var errText = document.getElementById('passwordGateErrorText');
                var password = document.getElementById('sharePasswordInput').value;

                if (password.length < 6) {
                    errText.textContent = 'Password must be at least 6 characters.';
                    err.classList.remove('hidden');
                    return;
                }

                btn.disabled = true;
                err.classList.add('hidden');

                fetch(form.getAttribute('data-verify-url'), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ password: password }),
                })
                    .then(function (res) {
                        if (res.ok) {
                            window.location.href = form.getAttribute('data-return-url');
                            return;
                        }
                        return res.json().catch(function () { return {}; }).then(function (data) {
                            errText.textContent = data.message || 'Password required';
                            err.classList.remove('hidden');
                            btn.disabled = false;
                        });
                    })
                    .catch(function () {
                        errText.textContent = 'Could not verify password. Please try again.';
                        err.classList.remove('hidden');
                        btn.disabled = false;
                    });
            });
        });
    </script>
@endsection
