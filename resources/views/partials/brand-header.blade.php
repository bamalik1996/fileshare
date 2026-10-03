@php($branding = $branding ?? null)
@if ($branding && ($branding['logo_url'] || $branding['message']))
    <div class="brand-header"
        @if (! empty($branding['color'])) style="--brand-accent: {{ $branding['color'] }};" @endif>
        @if (! empty($branding['logo_url']))
            <img class="brand-header-logo" src="{{ $branding['logo_url'] }}" alt="Logo" loading="lazy">
        @endif
        @if (! empty($branding['message']))
            <p class="brand-header-message">{{ $branding['message'] }}</p>
        @endif
    </div>

    <style>
        .brand-header { display: flex; flex-direction: column; align-items: center; gap: .6rem; text-align: center; margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 2px solid var(--brand-accent, rgba(128,128,128,.25)); }
        .brand-header-logo { max-height: 72px; max-width: 220px; object-fit: contain; }
        .brand-header-message { margin: 0; font-size: 1rem; opacity: .85; }
        @if (! empty($branding['color']))
            #airtoshare-public-view .modern-btn,
            .collect-dropzone.is-dragover { border-color: var(--brand-accent); }
            .collect-item-fill { background: var(--brand-accent) !important; }
        @endif
    </style>
@endif
