@extends('layouts.app')

@section('title', 'Send Files Between Devices – Phone to PC, Any OS | AirToShare')
@section('breadcrumb_label', 'Send Files Between Devices')
@section('description', 'Send files between any devices — phone to PC, Android to iPhone, laptop to tablet — instantly in your browser. No app, no signup, no cables.')
@section('keywords', 'send files between devices, send files from phone to pc, transfer files between devices, share files between devices, file transfer no app')

@section('schema')
    <script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "SoftwareApplication",
  "name": "AirToShare – Send Files Between Devices",
  "description": "Send files between any devices in the browser",
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
        <span class="hiw-page-badge"><i class="fas fa-bolt" aria-hidden="true"></i> Send Files Between Devices</span>
        <h1 class="hiw-page-title">Send files between any devices</h1>
        <p class="hiw-page-lead"><strong>Move files between your phone, PC, tablet and others — instantly, in the browser.</strong> No app, no signup, no cables. Works across Windows, Mac, Android and iOS.</p>
    </header>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-circle-info" aria-hidden="true"></i> One tool for every direction</h2>
        <div class="hiw-card" style="padding:1.35rem 1.5rem;">
            <p class="hiw-step-text">Phone to PC, Android to iPhone, laptop to tablet — AirToShare handles them all the same way. Devices on the same Wi‑Fi sync automatically; for anywhere else, you get a share link and a QR code. Large files up to 500 MB transfer via resumable chunked upload, so a dropped connection just resumes.</p>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-list-ol" aria-hidden="true"></i> Step by step</h2>
        <div class="hiw-card">
            <ol class="hiw-steps-list">
                <li class="hiw-step"><div class="hiw-step-marker" aria-hidden="true">1</div><div class="hiw-step-body"><h3 class="hiw-step-title">Open AirToShare on the sending device</h3><p class="hiw-step-text">Any browser, any OS. Nothing to install.</p></div></li>
                <li class="hiw-step"><div class="hiw-step-marker" aria-hidden="true">2</div><div class="hiw-step-body"><h3 class="hiw-step-title">Add your files</h3><p class="hiw-step-text">Drag and drop, or tap to pick files. Add a password or expiry if you want.</p></div></li>
                <li class="hiw-step"><div class="hiw-step-marker" aria-hidden="true">3</div><div class="hiw-step-body"><h3 class="hiw-step-title">Receive on the other device</h3><p class="hiw-step-text">Same Wi‑Fi auto-syncs, or open the share link / QR code on the other device to download.</p></div></li>
            </ol>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-circle-question" aria-hidden="true"></i> FAQ</h2>
        <div class="hiw-card" style="padding:1.35rem 1.5rem;">
            <h3 class="hiw-step-title">What is the file size limit?</h3>
            <p class="hiw-step-text">Up to 25 MB per file on the standard path, and up to 500 MB per file with chunked upload.</p>
            <h3 class="hiw-step-title" style="margin-top:1rem;">Does it work between different operating systems?</h3>
            <p class="hiw-step-text">Yes — it is browser-based, so Windows, Mac, Android and iOS all work together.</p>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-link" aria-hidden="true"></i> Related guides</h2>
        <div class="hiw-card" style="padding:1.35rem 1.5rem;"><ul class="hiw-step-text">
            <li><a href="{{ url('/transfer-files-android-to-pc') }}">Android to PC</a></li>
            <li><a href="{{ url('/transfer-files-android-to-iphone') }}">Android to iPhone</a></li>
            <li><a href="{{ url('/airdrop-for-pc') }}">AirDrop for PC</a></li>
        </ul></div>
    </section>

    <section class="hiw-section">
        <div class="hiw-card" style="text-align:center;padding:1.75rem 1.5rem;">
            <h2 class="hiw-section-heading" style="justify-content:center;"><i class="fas fa-bolt" aria-hidden="true"></i> Ready to share?</h2>
            <p class="hiw-step-text" style="max-width:54ch;margin:0 auto 1.25rem;">No app, no signup. Open AirToShare in your browser and start sending files between your devices in seconds.</p>
            <a href="{{ url('/') }}" class="modern-btn primary"><i class="fas fa-paper-plane" aria-hidden="true"></i> Start sharing free</a>
        </div>
    </section>

</div>
@endsection
