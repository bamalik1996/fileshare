@extends('layouts.app')

@section('title', 'Branding & settings – AirToShare')
@section('description', 'Customise the logo, colour, and message shown on your shared links.')

@section('content')
    <div class="account-page">
        <div class="account-hero">
            <h1 class="account-title">
                <i class="fas fa-palette" aria-hidden="true"></i>
                Branding
            </h1>
            <p class="account-subtitle">Add your logo, colour, and a message to your public share and file-request pages.</p>
        </div>

        <div class="account-toolbar">
            <a href="{{ route('account.shares') }}" class="modern-btn secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Back to shares
            </a>
        </div>

        @if (session('status'))
            <div class="auth-alert auth-alert-success account-alert" role="status">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="auth-alert auth-alert-error account-alert" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('account.settings.update') }}" enctype="multipart/form-data" class="modern-card settings-form">
            @csrf

            <div class="settings-field">
                <label class="settings-label">Logo</label>
                @if ($branding && $branding['logo_url'])
                    <div class="settings-logo-current">
                        <img src="{{ $branding['logo_url'] }}" alt="Current logo" class="settings-logo-img">
                        <label class="settings-remove">
                            <input type="checkbox" name="remove_logo" value="1"> Remove current logo
                        </label>
                    </div>
                @endif
                <input type="file" name="logo" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="settings-input">
                <p class="settings-hint">PNG, JPG, SVG, or WebP. Max 2 MB.</p>
            </div>

            <div class="settings-field">
                <label class="settings-label" for="brand_color">Accent colour</label>
                <input type="color" id="brand_color" name="brand_color"
                    value="{{ old('brand_color', $account->brand_color ?: '#6366f1') }}" class="settings-color">
                <p class="settings-hint">Used for buttons and accents on your shared pages.</p>
            </div>

            <div class="settings-field">
                <label class="settings-label" for="brand_message">Message</label>
                <input type="text" id="brand_message" name="brand_message" maxlength="160"
                    value="{{ old('brand_message', $account->brand_message) }}"
                    placeholder="e.g. Thanks for working with Acme Studio!" class="settings-input">
                <p class="settings-hint">Shown at the top of your public share and file-request pages (max 160 chars).</p>
            </div>

            <div class="account-share-actions">
                <button type="submit" class="modern-btn">
                    <i class="fas fa-save" aria-hidden="true"></i>
                    Save branding
                </button>
            </div>
        </form>
    </div>

    <style>
        .settings-form { display: flex; flex-direction: column; gap: 1.5rem; }
        .settings-field { display: flex; flex-direction: column; gap: .4rem; }
        .settings-label { font-weight: 600; }
        .settings-input { padding: .55rem .7rem; border-radius: 8px; border: 1px solid rgba(128,128,128,.4); background: transparent; color: inherit; }
        .settings-color { width: 64px; height: 40px; padding: 2px; border: 1px solid rgba(128,128,128,.4); border-radius: 8px; background: transparent; cursor: pointer; }
        .settings-hint { font-size: .8rem; opacity: .65; margin: 0; }
        .settings-logo-current { display: flex; align-items: center; gap: 1rem; margin-bottom: .5rem; }
        .settings-logo-img { max-height: 56px; max-width: 160px; object-fit: contain; background: rgba(128,128,128,.08); border-radius: 8px; padding: .25rem; }
        .settings-remove { font-size: .85rem; display: flex; align-items: center; gap: .35rem; }
    </style>
@endsection
