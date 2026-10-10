@extends('layouts.app')

@section('title', 'AirDrop for PC & Windows – Instant Browser File Sharing | AirToShare')
@section('breadcrumb_label', 'AirDrop for PC')
@section('description', 'Windows has no AirDrop — but AirToShare does the same thing in any browser. Send files and text between your phone and PC instantly. No app, no signup.')
@section('keywords', 'airdrop for pc, airdrop for windows, airdrop to pc, airdrop to windows, airdrop windows, airdrop alternative windows, send files iphone to pc')

@section('schema')
    <script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "WebApplication",
  "name": "AirToShare – AirDrop for PC",
  "description": "Browser-based AirDrop alternative for Windows PCs",
  "url": "{{ url()->current() }}",
  "applicationCategory": "UtilitiesApplication",
  "operatingSystem": "All",
  "image": "{{ url('/logo.svg') }}",
  "offers": { "@@type": "Offer", "price": "0", "priceCurrency": "USD" },
  "browserRequirements": "Requires JavaScript. Requires HTML5."
}
    </script>
@endsection

@section('content')
<div class="hiw-page">

    <header class="hiw-page-hero">
        <span class="hiw-page-badge"><i class="fas fa-bolt" aria-hidden="true"></i> AirDrop for Windows &amp; PC</span>
        <h1 class="hiw-page-title">AirDrop for PC &amp; Windows</h1>
        <p class="hiw-page-lead"><strong>Windows has no AirDrop — AirToShare does the same thing in any browser.</strong> Send files and text between your phone, Mac and Windows PC instantly, with no app to install and no account to create.</p>
    </header>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-circle-info" aria-hidden="true"></i> Why there is no AirDrop on Windows</h2>
        <div class="hiw-card" style="padding:1.35rem 1.5rem;">
            <p class="hiw-step-text">AirDrop is Apple-only — it works between iPhone, iPad and Mac, but Windows has no built-in equivalent. That is why "AirDrop for PC" is one of the most searched file-sharing questions. AirToShare fills that gap: it runs in any modern browser on Windows, macOS, Android and iOS, so every device can share with every other device over the same link or Wi‑Fi.</p>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-star" aria-hidden="true"></i> What you get</h2>
        <div class="hiw-card" style="padding:1.35rem 1.5rem;">
            <ul class="hiw-step-text">
                <li><strong>No install</strong> — works in Chrome, Edge, Firefox or Safari.</li>
                <li><strong>Same‑Wi‑Fi auto‑sync</strong> between your devices, or a share link / QR code for anywhere.</li>
                <li><strong>Files and text</strong> — send documents, photos, videos, or paste a block of text.</li>
                <li><strong>Large files</strong> up to 500 MB via resumable chunked upload.</li>
                <li><strong>Private</strong> — optional password, expiry and end‑to‑end encryption.</li>
            </ul>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-list-ol" aria-hidden="true"></i> Step by step</h2>
        <div class="hiw-card">
            <ol class="hiw-steps-list">
                <li class="hiw-step"><div class="hiw-step-marker" aria-hidden="true">1</div><div class="hiw-step-body"><h3 class="hiw-step-title">Open AirToShare on both devices</h3><p class="hiw-step-text">Visit the site in any browser on your iPhone or Android phone and on your Windows PC. Nothing to download.</p></div></li>
                <li class="hiw-step"><div class="hiw-step-marker" aria-hidden="true">2</div><div class="hiw-step-body"><h3 class="hiw-step-title">Add your files or text</h3><p class="hiw-step-text">On the phone, drop in files or paste text. Switch between the Text and Files tabs as needed.</p></div></li>
                <li class="hiw-step"><div class="hiw-step-marker" aria-hidden="true">3</div><div class="hiw-step-body"><h3 class="hiw-step-title">Send to your PC</h3><p class="hiw-step-text">On the same Wi‑Fi the PC syncs automatically — or copy the share link / scan the QR code and open it on your PC to download.</p></div></li>
            </ol>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-circle-question" aria-hidden="true"></i> FAQ</h2>
        <div class="hiw-card" style="padding:1.35rem 1.5rem;">
            <h3 class="hiw-step-title">Is there a real AirDrop for Windows?</h3>
            <p class="hiw-step-text">No. AirDrop is Apple-only. AirToShare is the closest thing — the same instant, no-setup sharing, but in a browser so it works on Windows too.</p>
            <h3 class="hiw-step-title" style="margin-top:1rem;">Do I need to install anything?</h3>
            <p class="hiw-step-text">No app and no signup. It runs entirely in your browser.</p>
            <h3 class="hiw-step-title" style="margin-top:1rem;">Can I send from iPhone to a Windows PC?</h3>
            <p class="hiw-step-text">Yes — add the file on your iPhone, open the share link on the PC, and download. It works in both directions.</p>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-link" aria-hidden="true"></i> Related guides</h2>
        <div class="hiw-card" style="padding:1.35rem 1.5rem;"><ul class="hiw-step-text">
            <li><a href="{{ url('/airdrop-for-android') }}">AirDrop for Android</a></li>
            <li><a href="{{ url('/send-files-between-devices') }}">Send files between devices</a></li>
            <li><a href="{{ url('/online-clipboard') }}">Online clipboard – share text between devices</a></li>
        </ul></div>
    </section>

    <section class="hiw-section">
        <div class="hiw-card" style="text-align:center;padding:1.75rem 1.5rem;">
            <h2 class="hiw-section-heading" style="justify-content:center;"><i class="fas fa-bolt" aria-hidden="true"></i> Ready to share?</h2>
            <p class="hiw-step-text" style="max-width:54ch;margin:0 auto 1.25rem;">No app, no signup. Open AirToShare in your browser and start sending files and text between your devices in seconds.</p>
            <a href="{{ url('/') }}" class="modern-btn primary"><i class="fas fa-paper-plane" aria-hidden="true"></i> Start sharing free</a>
        </div>
    </section>

</div>
@endsection
