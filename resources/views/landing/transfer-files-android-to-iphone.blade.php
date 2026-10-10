@extends('layouts.app')

@section('title', 'How to Transfer Files From Android to iPhone (No App) | AirToShare')
@section('breadcrumb_label', 'Android to iPhone')
@section('description', 'Transfer files from Android to iPhone without installing an app. Open AirToShare in the browser, add files on Android, and open the link on the iPhone. Free.')
@section('keywords', 'transfer files android to iphone, send file android to iphone, file transfer android to iphone, share files android to iphone, android to ios transfer')

@section('schema')
    <script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "HowTo",
  "name": "How to Transfer Files From Android to iPhone",
  "description": "Send files from an Android phone to an iPhone using a browser, with no app",
  "image": "{{ url('/logo.svg') }}",
  "totalTime": "PT2M",
  "estimatedCost": { "@@type": "MonetaryAmount", "currency": "USD", "value": "0" },
  "step": [
    { "@@type": "HowToStep", "name": "Open AirToShare on both phones", "text": "Open AirToShare in the browser on the Android phone and on the iPhone." },
    { "@@type": "HowToStep", "name": "Add files on Android", "text": "Select the photos, videos or documents you want to send." },
    { "@@type": "HowToStep", "name": "Open the link on the iPhone", "text": "Copy the share link or scan the QR code and open it in Safari on the iPhone to download." }
  ]
}
    </script>
@endsection

@section('content')
<div class="hiw-page">

    <header class="hiw-page-hero">
        <span class="hiw-page-badge"><i class="fas fa-bolt" aria-hidden="true"></i> Android to iPhone</span>
        <h1 class="hiw-page-title">How to transfer files from Android to iPhone</h1>
        <p class="hiw-page-lead"><strong>No app required.</strong> AirDrop does not reach Android, so use AirToShare in the browser: add files on your Android phone and open the share link on the iPhone to download. Free and instant.</p>
    </header>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-list-ol" aria-hidden="true"></i> Step by step</h2>
        <div class="hiw-card">
            <ol class="hiw-steps-list">
                <li class="hiw-step"><div class="hiw-step-marker" aria-hidden="true">1</div><div class="hiw-step-body"><h3 class="hiw-step-title">Open AirToShare on both phones</h3><p class="hiw-step-text">Use Chrome on Android and Safari on the iPhone. No install, no signup.</p></div></li>
                <li class="hiw-step"><div class="hiw-step-marker" aria-hidden="true">2</div><div class="hiw-step-body"><h3 class="hiw-step-title">Add the files on your Android phone</h3><p class="hiw-step-text">Switch to the Files tab and choose photos, videos or documents.</p></div></li>
                <li class="hiw-step"><div class="hiw-step-marker" aria-hidden="true">3</div><div class="hiw-step-body"><h3 class="hiw-step-title">Open the link on the iPhone and download</h3><p class="hiw-step-text">Copy the share link or scan the QR code with the iPhone camera, then save the files.</p></div></li>
            </ol>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-circle-question" aria-hidden="true"></i> FAQ</h2>
        <div class="hiw-card">
            <h3 class="hiw-step-title">Why can I not just AirDrop from Android?</h3>
            <p class="hiw-step-text">AirDrop is Apple-only and does not work with Android. AirToShare bridges the two in the browser.</p>
            <h3 class="hiw-step-title" style="margin-top:1rem;">Will photos keep their quality?</h3>
            <p class="hiw-step-text">Yes — files transfer as-is, without the compression some chat apps apply.</p>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-link" aria-hidden="true"></i> Related guides</h2>
        <div class="hiw-card"><ul class="hiw-step-text">
            <li><a href="{{ url('/transfer-files-android-to-pc') }}">Android to PC</a></li>
            <li><a href="{{ url('/airdrop-for-android') }}">AirDrop for Android</a></li>
            <li><a href="{{ url('/send-files-between-devices') }}">Send files between devices</a></li>
        </ul></div>
    </section>

    <section class="hiw-section">
        <div class="hiw-card" style="text-align:center;">
            <h2 class="hiw-section-heading" style="justify-content:center;"><i class="fas fa-bolt" aria-hidden="true"></i> Ready to share?</h2>
            <p class="hiw-step-text" style="max-width:54ch;margin:0 auto 1.25rem;">No app, no signup. Open AirToShare and move files from Android to iPhone in seconds.</p>
            <a href="{{ url('/') }}" class="modern-btn primary"><i class="fas fa-paper-plane" aria-hidden="true"></i> Start sharing free</a>
        </div>
    </section>

</div>
@endsection
