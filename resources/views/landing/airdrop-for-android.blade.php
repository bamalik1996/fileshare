@extends('layouts.app')

@section('title', 'AirDrop for Android – Share Files With iPhone & PC | AirToShare')
@section('breadcrumb_label', 'AirDrop for Android')
@section('description', 'Android has no AirDrop with iPhone or Windows — AirToShare does it in any browser. Send files and text between Android, iPhone, Mac and PC instantly. No app, no signup.')
@section('keywords', 'airdrop for android, airdrop android, android to iphone sharing, send files android to iphone, share files android to pc, airdrop alternative android')

@section('schema')
    <script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "SoftwareApplication",
  "name": "AirToShare – AirDrop for Android",
  "description": "Browser-based AirDrop alternative for Android phones",
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
        <span class="hiw-page-badge"><i class="fas fa-bolt" aria-hidden="true"></i> AirDrop for Android</span>
        <h1 class="hiw-page-title">AirDrop for Android</h1>
        <p class="hiw-page-lead"><strong>Android cannot AirDrop to an iPhone or a PC — AirToShare can.</strong> Share files and text between Android, iPhone, Mac and Windows right in the browser, with nothing to install.</p>
    </header>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-circle-info" aria-hidden="true"></i> AirDrop vs Android</h2>
        <div class="hiw-card" style="padding:1.35rem 1.5rem;">
            <p class="hiw-step-text">AirDrop only works between Apple devices, and Android's own Quick Share does not reliably reach iPhones or Windows PCs. AirToShare is cross‑platform by design: open it in any browser and every device — Android, iOS, Windows, Mac — can send to every other device over a link or the same Wi‑Fi.</p>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-list-ol" aria-hidden="true"></i> Step by step</h2>
        <div class="hiw-card">
            <ol class="hiw-steps-list">
                <li class="hiw-step"><div class="hiw-step-marker" aria-hidden="true">1</div><div class="hiw-step-body"><h3 class="hiw-step-title">Open AirToShare on your Android phone</h3><p class="hiw-step-text">Visit the site in Chrome or any browser. No app, no signup.</p></div></li>
                <li class="hiw-step"><div class="hiw-step-marker" aria-hidden="true">2</div><div class="hiw-step-body"><h3 class="hiw-step-title">Add files or text</h3><p class="hiw-step-text">Drop in photos, documents or videos, or paste text in the Text tab.</p></div></li>
                <li class="hiw-step"><div class="hiw-step-marker" aria-hidden="true">3</div><div class="hiw-step-body"><h3 class="hiw-step-title">Open the link on the other device</h3><p class="hiw-step-text">Copy the share link or show the QR code, then open it on the iPhone, Mac or PC to download.</p></div></li>
            </ol>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-circle-question" aria-hidden="true"></i> FAQ</h2>
        <div class="hiw-card" style="padding:1.35rem 1.5rem;">
            <h3 class="hiw-step-title">Can Android AirDrop to iPhone?</h3>
            <p class="hiw-step-text">Not directly — AirDrop is Apple-only. AirToShare bridges Android and iPhone in the browser.</p>
            <h3 class="hiw-step-title" style="margin-top:1rem;">How do I send large files from Android?</h3>
            <p class="hiw-step-text">Files up to 500 MB are supported via chunked upload — no email size limits.</p>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-link" aria-hidden="true"></i> Related guides</h2>
        <div class="hiw-card" style="padding:1.35rem 1.5rem;"><ul class="hiw-step-text">
            <li><a href="{{ url('/airdrop-for-pc') }}">AirDrop for PC &amp; Windows</a></li>
            <li><a href="{{ url('/transfer-files-android-to-iphone') }}">Transfer files Android to iPhone</a></li>
            <li><a href="{{ url('/transfer-files-android-to-pc') }}">Transfer files Android to PC</a></li>
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
