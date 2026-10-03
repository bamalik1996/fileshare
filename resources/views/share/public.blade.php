@extends('layouts.app')

@section('title', 'Shared files – AirToShare')
@section('robots', 'noindex, nofollow')

@section('content')
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

    <div class="branded-request-page @if ($hasBrand) is-branded @endif"
        id="airtoshare-public-view"
        @if ($share) data-airtoshare-share-id="{{ $share->id }}" @endif
        @if ($brandColor) style="--brand-accent: {{ $brandColor }};" @endif>

        @include('partials.brand-header', ['branding' => $branding])

        <div class="branded-request-shell">
            <header class="branded-request-header">
                <span class="branded-request-kicker">
                    <i class="fas fa-link" aria-hidden="true"></i>
                    Shared with you
                </span>
                <h1 class="branded-request-title">Shared content</h1>
                <p class="branded-request-subtitle">
                    This is a private link. It is not indexed by search engines.
                </p>
            </header>

            <div class="modern-card branded-request-card">
                @if ($share->text_content)
                    <div class="text-container" style="margin-bottom: 1.5rem;">
                        <div class="rich-preview-panel">{!! $share->text_content !!}</div>
                    </div>
                @endif

                @if ($media->isNotEmpty())
                    <h2 class="branded-request-files-heading">Files</h2>
                    <div class="file-grid">
                        @foreach ($media as $file)
                            <div class="column is-12 preview-row file-item"
                                 data-preview-uuid="{{ $file['uuid'] }}"
                                 data-preview-mime="{{ $file['mime_type'] }}"
                                 data-preview-size="{{ $file['size'] }}"
                                 data-preview-url="{{ $file['original_url'] }}"
                                 data-preview-name="{{ $file['name'] }}">
                                <div class="file-info">
                                    <div class="file-name">{{ $file['name'] }}</div>
                                    <div class="file-size">{{ number_format($file['size'] / 1024, 1) }} KB</div>
                                </div>
                                <a class="modern-btn is-small preview-download branded-accent-btn"
                                   href="{{ $file['original_url'] }}" download>Download</a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="branded-request-empty">No files in this share.</p>
                @endif
            </div>

            <footer class="branded-request-trust">
                <i class="fas fa-shield-alt" aria-hidden="true"></i>
                <span>Delivered securely via <strong>AirToShare</strong>.</span>
            </footer>
        </div>
    </div>
@endsection
