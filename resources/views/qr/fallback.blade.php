{{--
    QR generation fallback view (design.md > Components and Interfaces > 1).

    Rendered by QrCodeController::show() when QrGenerator::generateOrFail()
    raises QrGenerationException. Acceptance criterion 1.5 mandates that
    a generation failure surface:

      - the Share URL as fallback text, so the recipient can still copy
        and paste the link manually; and
      - an error banner explaining that the QR image could not be
        produced; and

    crucially MUST NOT offer the QR PNG as a download. This view is
    deliberately self-contained (no <a download> anchor, no <img> tag)
    so a partially-rendered failure cannot accidentally reintroduce a
    broken download affordance.

    The view extends the application layout so a user landing here from
    direct navigation gets the same chrome (header, footer, theme) as
    the rest of the site; it is also safe to render inline as the
    `error` handler of an <img src="/qr/{slug}"> tag (design.md > frontend
    wiring), in which case the surrounding share view simply replaces the
    failed image with this URL text plus banner.
--}}
@extends('layouts.app')

@section('title', 'QR code unavailable | AirToShare')
@section('robots', 'noindex, nofollow')
@section('description', 'The QR code for this share could not be generated. Use the displayed link to open the share.')

@section('content')
    <div class="container" style="padding: 2rem 1rem;">
        <div
            class="notification is-warning"
            role="alert"
            aria-live="polite"
            data-qr-fallback-banner
        >
            <strong>QR code unavailable.</strong>
            <span>We could not generate a QR image for this share right now. Use the link below to open it.</span>
        </div>

        <div class="box" data-qr-fallback>
            <p class="has-text-weight-semibold" style="margin-bottom: 0.5rem;">Share URL</p>
            <p class="is-size-6 has-text-grey-dark" data-qr-fallback-url style="word-break: break-all;">
                {{ $shareUrl }}
            </p>
        </div>
    </div>
@endsection
