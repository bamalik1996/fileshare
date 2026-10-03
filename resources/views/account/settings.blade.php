@extends('layouts.app')

@section('title', 'Branding & settings – AirToShare')
@section('description', 'Customise the logo, colour, and message shown on your shared links.')

@section('content')
    @php
        $defaultColor = '#0ea5e9';
        $color = old('brand_color', $account->brand_color ?: $defaultColor);
        $message = old('brand_message', $account->brand_message ?? '');
        $logoUrl = ($branding && ! empty($branding['logo_url'])) ? $branding['logo_url'] : null;
    @endphp

    <div class="account-page">
        <div class="account-hero">
            <h1 class="account-title">
                <i class="fas fa-palette" aria-hidden="true"></i>
                Branding
            </h1>
            <p class="account-subtitle">
                Add your logo, colour, and a message to your public share and file-request pages.
            </p>
        </div>

        <div class="info-panel account-stats">
            <div class="info-item">
                <i class="fas fa-globe" aria-hidden="true"></i>
                <strong>Shows on:</strong> Public links &amp; collect pages
            </div>
            <div class="info-item">
                <i class="fas fa-user" aria-hidden="true"></i>
                <strong>Account:</strong> {{ $account->email }}
            </div>
            <div class="info-item">
                <i class="fas fa-eye" aria-hidden="true"></i>
                <strong>Preview:</strong> updates live as you edit
            </div>
        </div>

        <div class="account-toolbar">
            <a href="{{ route('account.shares') }}" class="modern-btn secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Back to shares
            </a>
            <a href="{{ url('/') }}" class="modern-btn secondary">
                <i class="fas fa-home" aria-hidden="true"></i>
                Home
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

        <div class="account-branding-layout">
            <form method="POST"
                action="{{ route('account.settings.update') }}"
                enctype="multipart/form-data"
                class="modern-card account-branding-card"
                id="brandingForm">
                @csrf

                <div class="account-branding-section">
                    <h2 class="account-branding-heading">
                        <i class="fas fa-image" aria-hidden="true"></i>
                        Logo
                    </h2>
                    <p class="account-branding-lead">Shown above your message on public and collect pages.</p>

                    <div class="branding-logo-dropzone @if ($logoUrl) has-logo @endif"
                        id="brandingLogoDropzone"
                        tabindex="0"
                        role="button"
                        aria-label="Choose a logo image">
                        <input type="file"
                            name="logo"
                            id="brandingLogoInput"
                            accept="image/png,image/jpeg,image/svg+xml,image/webp"
                            hidden>

                        <div class="branding-logo-preview" id="brandingLogoPreview">
                            @if ($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Current logo" id="brandingLogoImg">
                            @else
                                <div class="branding-logo-placeholder" id="brandingLogoPlaceholder">
                                    <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                                    <span>Drop logo here or <strong>click to choose</strong></span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <p class="form-hint">PNG, JPG, SVG, or WebP · Max 2 MB</p>

                    @if ($logoUrl)
                        <label class="branding-remove-logo">
                            <input type="checkbox" name="remove_logo" value="1" id="brandingRemoveLogo">
                            <span>Remove current logo</span>
                        </label>
                    @endif
                </div>

                <div class="account-branding-section">
                    <h2 class="account-branding-heading">
                        <i class="fas fa-fill-drip" aria-hidden="true"></i>
                        Accent colour
                    </h2>
                    <p class="account-branding-lead">Used for accents and buttons on your shared pages.</p>

                    <div class="branding-color-row">
                        <input type="color"
                            id="brand_color_picker"
                            value="{{ $color }}"
                            class="branding-color-swatch"
                            aria-label="Pick accent colour">
                        <input type="text"
                            id="brand_color"
                            name="brand_color"
                            value="{{ $color }}"
                            maxlength="7"
                            pattern="^#[0-9A-Fa-f]{6}$"
                            class="form-input branding-color-hex"
                            placeholder="{{ $defaultColor }}"
                            spellcheck="false"
                            autocomplete="off">
                    </div>
                </div>

                <div class="account-branding-section">
                    <div class="auth-label-row">
                        <h2 class="account-branding-heading" style="margin: 0;">
                            <i class="fas fa-comment-alt" aria-hidden="true"></i>
                            Message
                        </h2>
                        <span class="branding-char-count" id="brandingCharCount" aria-live="polite">
                            {{ strlen((string) $message) }}/160
                        </span>
                    </div>
                    <p class="account-branding-lead">A short line under your logo (optional).</p>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label visually-hidden" for="brand_message">Message</label>
                        <input type="text"
                            id="brand_message"
                            name="brand_message"
                            maxlength="160"
                            value="{{ $message }}"
                            placeholder="e.g. Thanks for working with Acme Studio!"
                            class="form-input">
                    </div>
                </div>

                <div class="account-share-actions">
                    <button type="submit" class="modern-btn">
                        <i class="fas fa-save" aria-hidden="true"></i>
                        Save branding
                    </button>
                    <a href="{{ route('account.shares') }}" class="modern-btn secondary">Cancel</a>
                </div>
            </form>

            <aside class="modern-card account-branding-preview-card" aria-label="Live preview">
                <div class="account-branding-preview-label">
                    <i class="fas fa-desktop" aria-hidden="true"></i>
                    Live preview
                </div>

                <div class="account-branding-preview-frame branded-request-page is-branded" id="brandingPreviewFrame"
                    style="--brand-accent: {{ $color }};">
                    <div class="brand-hero" id="brandingPreviewHeader">
                        <div class="brand-hero-panel">
                            <img class="brand-hero-logo"
                                id="brandingPreviewLogo"
                                src="{{ $logoUrl ?: '' }}"
                                alt="Logo preview"
                                @if (! $logoUrl) hidden @endif>
                            <div class="brand-hero-mark" id="brandingPreviewMark"
                                @if ($logoUrl) hidden @endif aria-hidden="true">
                                <i class="fas fa-share-alt"></i>
                            </div>
                            <p class="brand-hero-message"
                                id="brandingPreviewMessage"
                                @if (trim((string) $message) === '') hidden @endif>{{ $message }}</p>
                            <p class="branding-preview-empty" id="brandingPreviewEmpty"
                                @if ($logoUrl || trim((string) $message) !== '') hidden @endif>
                                Add a logo or message — colour already tints this page.
                            </p>
                        </div>
                    </div>

                    <div class="branding-preview-body">
                        <span class="branded-request-kicker">
                            <i class="fas fa-inbox" aria-hidden="true"></i>
                            File request
                        </span>
                        <div class="branding-preview-skeleton branding-preview-skeleton--title"></div>
                        <div class="branding-preview-skeleton"></div>
                        <div class="branding-preview-skeleton branding-preview-skeleton--short"></div>
                        <button type="button" class="modern-btn branding-preview-btn" tabindex="-1">
                            <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                            Upload files
                        </button>
                    </div>
                </div>

                <p class="form-hint branding-preview-hint">
                    Recipients see this treatment on <code>/p/…</code> and <code>/collect/…</code> pages.
                </p>
            </aside>
        </div>
    </div>

    <script>
        (function () {
            var dropzone = document.getElementById('brandingLogoDropzone');
            var input = document.getElementById('brandingLogoInput');
            var preview = document.getElementById('brandingLogoPreview');
            var removeCb = document.getElementById('brandingRemoveLogo');
            var colorPicker = document.getElementById('brand_color_picker');
            var colorHex = document.getElementById('brand_color');
            var messageInput = document.getElementById('brand_message');
            var charCount = document.getElementById('brandingCharCount');
            var previewFrame = document.getElementById('brandingPreviewFrame');
            var previewLogo = document.getElementById('brandingPreviewLogo');
            var previewMessage = document.getElementById('brandingPreviewMessage');
            var previewEmpty = document.getElementById('brandingPreviewEmpty');
            var objectUrl = null;

            var previewMark = document.getElementById('brandingPreviewMark');

            function syncPreviewVisibility() {
                var hasLogo = previewLogo && previewLogo.getAttribute('src') && !previewLogo.hidden;
                var hasMsg = previewMessage && previewMessage.textContent.trim().length > 0 && !previewMessage.hidden;
                if (previewEmpty) {
                    previewEmpty.hidden = !!(hasLogo || hasMsg);
                }
                if (previewMark) {
                    previewMark.hidden = !!hasLogo;
                }
            }

            function setLogoPreview(url) {
                if (!preview) return;
                preview.innerHTML = '';
                if (!url) {
                    preview.innerHTML =
                        '<div class="branding-logo-placeholder" id="brandingLogoPlaceholder">' +
                        '<i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>' +
                        '<span>Drop logo here or <strong>click to choose</strong></span></div>';
                    if (previewLogo) {
                        previewLogo.removeAttribute('src');
                        previewLogo.hidden = true;
                    }
                    if (dropzone) dropzone.classList.remove('has-logo');
                    syncPreviewVisibility();
                    return;
                }

                var img = document.createElement('img');
                img.src = url;
                img.alt = 'Logo preview';
                img.id = 'brandingLogoImg';
                preview.appendChild(img);
                if (previewLogo) {
                    previewLogo.src = url;
                    previewLogo.hidden = false;
                }
                if (dropzone) dropzone.classList.add('has-logo');
                syncPreviewVisibility();
            }

            function openFilePicker() {
                if (input) input.click();
            }

            if (dropzone && input) {
                dropzone.addEventListener('click', openFilePicker);
                dropzone.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        openFilePicker();
                    }
                });
                dropzone.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    dropzone.classList.add('is-dragover');
                });
                dropzone.addEventListener('dragleave', function () {
                    dropzone.classList.remove('is-dragover');
                });
                dropzone.addEventListener('drop', function (e) {
                    e.preventDefault();
                    dropzone.classList.remove('is-dragover');
                    if (!e.dataTransfer.files || !e.dataTransfer.files.length) return;
                    input.files = e.dataTransfer.files;
                    input.dispatchEvent(new Event('change'));
                });

                input.addEventListener('change', function () {
                    var file = input.files && input.files[0];
                    if (!file) return;
                    if (objectUrl) URL.revokeObjectURL(objectUrl);
                    objectUrl = URL.createObjectURL(file);
                    setLogoPreview(objectUrl);
                    if (removeCb) removeCb.checked = false;
                });
            }

            if (removeCb) {
                removeCb.addEventListener('change', function () {
                    if (removeCb.checked) {
                        if (objectUrl) {
                            URL.revokeObjectURL(objectUrl);
                            objectUrl = null;
                        }
                        if (input) input.value = '';
                        setLogoPreview(null);
                    }
                });
            }

            function applyColor(value) {
                var hex = (value || '').trim();
                if (!/^#[0-9A-Fa-f]{6}$/.test(hex)) return;
                if (colorPicker) colorPicker.value = hex;
                if (colorHex && colorHex.value !== hex) colorHex.value = hex;
                if (previewFrame) previewFrame.style.setProperty('--brand-accent', hex);
            }

            if (colorPicker) {
                colorPicker.addEventListener('input', function () {
                    applyColor(colorPicker.value);
                });
            }
            if (colorHex) {
                colorHex.addEventListener('input', function () {
                    var v = colorHex.value.trim();
                    if (v && v.charAt(0) !== '#') {
                        v = '#' + v;
                        colorHex.value = v;
                    }
                    applyColor(v);
                });
            }

            if (messageInput) {
                messageInput.addEventListener('input', function () {
                    var text = messageInput.value || '';
                    if (charCount) charCount.textContent = text.length + '/160';
                    if (previewMessage) {
                        previewMessage.textContent = text;
                        previewMessage.hidden = text.trim().length === 0;
                    }
                    syncPreviewVisibility();
                });
            }
        })();
    </script>
@endsection
