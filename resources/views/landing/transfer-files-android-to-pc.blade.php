@extends('layouts.app')

@section('title', 'How to Transfer Files From Android to PC (No Cable) | AirToShare')
@section('breadcrumb_label', 'Android to PC')
@section('description', 'Transfer files from your Android phone to a PC without a USB cable. Open AirToShare in the browser, add files, and open the link on your PC. Free, no app.')
@section('keywords', 'transfer files from android to pc, android to pc file transfer, send files from phone to pc, share files android to pc without cable')

@section('schema')
    <script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "HowTo",
  "name": "How to Transfer Files From Android to PC",
  "description": "Send files from an Android phone to a Windows or Mac computer using a browser",
  "image": "{{ url('/logo.svg') }}",
  "totalTime": "PT2M",
  "estimatedCost": { "@@type": "MonetaryAmount", "currency": "USD", "value": "0" },
  "step": [
    { "@@type": "HowToStep", "name": "Open AirToShare on both devices", "text": "Open AirToShare in the browser on your Android phone and on your PC." },
    { "@@type": "HowToStep", "name": "Add files on your phone", "text": "Tap to select photos, documents or videos on the Android device." },
    { "@@type": "HowToStep", "name": "Open the link on your PC", "text": "Copy the share link or scan the QR code, open it on your PC, and download the files." }
  ]
}
    </script>
@endsection

@section('content')
<div class="hiw-page">

    <header class="hiw-page-hero">
        <span class="hiw-page-badge"><i class="fas fa-bolt" aria-hidden="true"></i> Android to PC</span>
        <h1 class="hiw-page-title">How to transfer files from Android to PC</h1>
        <p class="hiw-page-lead"><strong>No cable needed.</strong> Open AirToShare in the browser on both your Android phone and your PC, add the files, and download them on the computer. Free, no app, no signup.</p>
    </header>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-list-ol" aria-hidden="true"></i> Step by step</h2>
        <div class="hiw-card">
            <ol class="hiw-steps-list">
                <li class="hiw-step"><div class="hiw-step-marker" aria-hidden="true">1</div><div class="hiw-step-body"><h3 class="hiw-step-title">Open AirToShare on your Android phone and PC</h3><p class="hiw-step-text">Use any browser on both. On the same Wi‑Fi they will sync automatically.</p></div></li>
                <li class="hiw-step"><div class="hiw-step-marker" aria-hidden="true">2</div><div class="hiw-step-body"><h3 class="hiw-step-title">Add the files on your phone</h3><p class="hiw-step-text">Switch to the Files tab and pick the photos, documents or videos to send.</p></div></li>
                <li class="hiw-step"><div class="hiw-step-marker" aria-hidden="true">3</div><div class="hiw-step-body"><h3 class="hiw-step-title">Download on your PC</h3><p class="hiw-step-text">If auto-sync has not shown them, copy the share link or scan the QR code on the PC and download.</p></div></li>
            </ol>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-lightbulb" aria-hidden="true"></i> Tips</h2>
        <div class="hiw-card">
            <ul class="hiw-step-text">
                <li>For files over 5 MB, chunked upload resumes automatically if the connection drops (up to 500 MB).</li>
                <li>Set an expiry or password if the files are sensitive.</li>
                <li>Create a free account for a 30-day expiry and a My Shares history.</li>
            </ul>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-circle-question" aria-hidden="true"></i> FAQ</h2>
        <div class="hiw-card">
            <h3 class="hiw-step-title">Can I transfer without a USB cable?</h3>
            <p class="hiw-step-text">Yes — everything goes over Wi‑Fi or a share link, so no cable or drivers are needed.</p>
            <h3 class="hiw-step-title" style="margin-top:1rem;">Does this work on Mac too?</h3>
            <p class="hiw-step-text">Yes — the same steps work for any computer with a browser.</p>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-link" aria-hidden="true"></i> Related guides</h2>
        <div class="hiw-card"><ul class="hiw-step-text">
            <li><a href="{{ url('/transfer-files-android-to-iphone') }}">Android to iPhone</a></li>
            <li><a href="{{ url('/airdrop-for-pc') }}">AirDrop for PC</a></li>
            <li><a href="{{ url('/send-files-between-devices') }}">Send files between devices</a></li>
        </ul></div>
    </section>

    <section class="hiw-section">
        <div class="hiw-card" style="text-align:center;">
            <h2 class="hiw-section-heading" style="justify-content:center;"><i class="fas fa-bolt" aria-hidden="true"></i> Ready to share?</h2>
            <p class="hiw-step-text" style="max-width:54ch;margin:0 auto 1.25rem;">No app, no signup. Open AirToShare and move files from your Android phone to your PC in seconds.</p>
            <a href="{{ url('/') }}" class="modern-btn primary"><i class="fas fa-paper-plane" aria-hidden="true"></i> Start sharing free</a>
        </div>
    </section>

</div>
@endsection
