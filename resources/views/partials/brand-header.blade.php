@php
    $branding = $branding ?? null;
    $hasBrand = is_array($branding) && (
        ! empty($branding['logo_url'])
        || ! empty($branding['message'])
        || ! empty($branding['color'])
    );
    $brandColor = (! empty($branding['color']) && preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $branding['color']))
        ? $branding['color']
        : null;
@endphp

@if ($hasBrand)
    <div class="brand-hero"
        @if ($brandColor) style="--brand-accent: {{ $brandColor }};" @endif>
        <div class="brand-hero-panel">
            @if (! empty($branding['logo_url']))
                <img class="brand-hero-logo"
                    src="{{ $branding['logo_url'] }}"
                    alt="{{ ! empty($branding['message']) ? $branding['message'] : 'Brand logo' }}"
                    loading="lazy"
                    width="240"
                    height="80">
            @else
                <div class="brand-hero-mark" aria-hidden="true">
                    <i class="fas fa-share-alt"></i>
                </div>
            @endif

            @if (! empty($branding['message']))
                <p class="brand-hero-message">{{ $branding['message'] }}</p>
            @endif
        </div>
    </div>
@endif
