<div class="auth-page">
    <div class="auth-hero">
        <h1 class="auth-title">
            @if (!empty($icon))
                <i class="{{ $icon }}" aria-hidden="true"></i>
            @endif
            {{ $title }}
        </h1>
        @if (!empty($subtitle))
            <p class="auth-subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    <div class="modern-card auth-card">
        <div class="auth-form">
            @if (session('status'))
                <div class="auth-alert auth-alert-success" role="status">
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="auth-alert auth-alert-error" role="alert">
                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif
