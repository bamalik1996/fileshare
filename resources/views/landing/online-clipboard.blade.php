@extends('layouts.app')

@section('title', 'Online Clipboard – Copy & Paste Between Devices | AirToShare')
@section('breadcrumb_label', 'Online Clipboard')
@section('description', 'A free online clipboard to share text and copy-paste between your phone, PC and other devices instantly. Live sync in a Room, no app and no signup.')
@section('keywords', 'online clipboard, share text between devices, copy paste between devices, share text online, clipboard sync, cross device clipboard')

@section('schema')
    <script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "WebApplication",
  "name": "AirToShare – Online Clipboard",
  "description": "Share text and sync your clipboard across devices in the browser",
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
        <span class="hiw-page-badge"><i class="fas fa-bolt" aria-hidden="true"></i> Online Clipboard</span>
        <h1 class="hiw-page-title">Online clipboard for every device</h1>
        <p class="hiw-page-lead"><strong>Paste text on one device and read it on another in seconds.</strong> AirToShare is a free online clipboard — share notes, links and codes between your phone and PC, or sync live with others in a Room. No app, no signup.</p>
    </header>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-star" aria-hidden="true"></i> What makes it different</h2>
        <div class="hiw-card" style="padding:1.35rem 1.5rem;">
            <p class="hiw-step-text">Most sharing tools only move files. AirToShare also gives you a real cross‑device clipboard: type or paste text and it is instantly available on any device with the link. Create a <strong>Room</strong> with a 6‑character code and everyone in it sees clipboard changes <strong>live</strong> — ideal for sending a Wi‑Fi password, a login code, or a long URL from phone to laptop.</p>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-list-ol" aria-hidden="true"></i> Step by step</h2>
        <div class="hiw-card">
            <ol class="hiw-steps-list">
                <li class="hiw-step"><div class="hiw-step-marker" aria-hidden="true">1</div><div class="hiw-step-body"><h3 class="hiw-step-title">Open AirToShare and pick the Text tab</h3><p class="hiw-step-text">No signup needed. Type or paste the text you want to move.</p></div></li>
                <li class="hiw-step"><div class="hiw-step-marker" aria-hidden="true">2</div><div class="hiw-step-body"><h3 class="hiw-step-title">Copy the link or start a Room</h3><p class="hiw-step-text">Copy the share link for one-off text, or create a Room code for live, two-way sync.</p></div></li>
                <li class="hiw-step"><div class="hiw-step-marker" aria-hidden="true">3</div><div class="hiw-step-body"><h3 class="hiw-step-title">Open on your other device</h3><p class="hiw-step-text">Paste the link (or join the Room code) on your phone or PC — the text appears instantly, ready to copy.</p></div></li>
            </ol>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-circle-question" aria-hidden="true"></i> FAQ</h2>
        <div class="hiw-card" style="padding:1.35rem 1.5rem;">
            <h3 class="hiw-step-title">Is the online clipboard free?</h3>
            <p class="hiw-step-text">Yes — free with no account. Create a free account only if you want longer expiry and history.</p>
            <h3 class="hiw-step-title" style="margin-top:1rem;">Does the text sync live?</h3>
            <p class="hiw-step-text">In a Room, yes — clipboard changes broadcast in real time to everyone in the Room.</p>
            <h3 class="hiw-step-title" style="margin-top:1rem;">Is my text private?</h3>
            <p class="hiw-step-text">You can add a password and expiry, and optional end-to-end encryption keeps the text unreadable to the server.</p>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-link" aria-hidden="true"></i> Related guides</h2>
        <div class="hiw-card" style="padding:1.35rem 1.5rem;"><ul class="hiw-step-text">
            <li><a href="{{ url('/send-files-between-devices') }}">Send files between devices</a></li>
            <li><a href="{{ url('/airdrop-for-pc') }}">AirDrop for PC &amp; Windows</a></li>
            <li><a href="{{ url('/airdrop-for-android') }}">AirDrop for Android</a></li>
        </ul></div>
    </section>

    <section class="hiw-section">
        <div class="hiw-card" style="text-align:center;padding:1.75rem 1.5rem;">
            <h2 class="hiw-section-heading" style="justify-content:center;"><i class="fas fa-bolt" aria-hidden="true"></i> Ready to share?</h2>
            <p class="hiw-step-text" style="max-width:54ch;margin:0 auto 1.25rem;">No app, no signup. Open AirToShare in your browser and start sharing text and files between your devices in seconds.</p>
            <a href="{{ url('/') }}" class="modern-btn primary"><i class="fas fa-paper-plane" aria-hidden="true"></i> Start sharing free</a>
        </div>
    </section>

</div>
@endsection
