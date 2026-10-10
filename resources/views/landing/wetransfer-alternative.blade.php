@extends('layouts.app')

@section('title', 'Free WeTransfer Alternative – No Signup, No Email | AirToShare')
@section('breadcrumb_label', 'WeTransfer Alternative')
@section('description', 'A free WeTransfer alternative with no signup and no email required. Send files and text between devices instantly in your browser, with QR codes and live Rooms.')
@section('keywords', 'wetransfer alternative, free file sharing no signup, send files without email, wetransfer free alternative, file sharing no account')

@section('schema')
    <script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "WebApplication",
  "name": "AirToShare – WeTransfer Alternative",
  "description": "A free, no-signup alternative to WeTransfer for sending files",
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
        <span class="hiw-page-badge"><i class="fas fa-bolt" aria-hidden="true"></i> WeTransfer Alternative</span>
        <h1 class="hiw-page-title">A free WeTransfer alternative — no signup</h1>
        <p class="hiw-page-lead"><strong>Send files without an email address or an account.</strong> AirToShare shares files and text straight between devices in the browser, with share links, QR codes and live Rooms — free.</p>
    </header>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-scale-balanced" aria-hidden="true"></i> How AirToShare compares</h2>
        <div class="hiw-card" style="padding:1.35rem 1.5rem;">
            <p class="hiw-step-text">WeTransfer is great for emailing large files to someone else. AirToShare is built for moving things between <em>your own</em> devices and small groups — instantly, with no email step. Here is the honest comparison:</p>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-table" aria-hidden="true"></i> AirToShare vs WeTransfer</h2>
        <div class="hiw-card" style="overflow-x:auto;padding:1.35rem 1.5rem;">
            <table style="width:100%;border-collapse:collapse;">
                <thead><tr><th style="text-align:left;padding:.5rem;">Feature</th><th style="text-align:left;padding:.5rem;">AirToShare</th><th style="text-align:left;padding:.5rem;">WeTransfer (free)</th></tr></thead>
                <tbody>
                    <tr><td style="padding:.5rem;">Signup / email</td><td style="padding:.5rem;">Not required</td><td style="padding:.5rem;">Email usually required</td></tr>
                    <tr><td style="padding:.5rem;">Phone to PC / cross-device</td><td style="padding:.5rem;">Yes, instant in browser</td><td style="padding:.5rem;">Via email link</td></tr>
                    <tr><td style="padding:.5rem;">Live text / clipboard sync</td><td style="padding:.5rem;">Yes (Rooms)</td><td style="padding:.5rem;">No</td></tr>
                    <tr><td style="padding:.5rem;">QR code sharing</td><td style="padding:.5rem;">Yes</td><td style="padding:.5rem;">No</td></tr>
                    <tr><td style="padding:.5rem;">Max file size</td><td style="padding:.5rem;">Up to 500 MB (chunked)</td><td style="padding:.5rem;">2 GB free</td></tr>
                    <tr><td style="padding:.5rem;">End-to-end encryption</td><td style="padding:.5rem;">Optional</td><td style="padding:.5rem;">No</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-circle-question" aria-hidden="true"></i> FAQ</h2>
        <div class="hiw-card" style="padding:1.35rem 1.5rem;">
            <h3 class="hiw-step-title">Is AirToShare really free with no signup?</h3>
            <p class="hiw-step-text">Yes — guest sharing is instant and free. An optional free account adds longer expiry and history.</p>
            <h3 class="hiw-step-title" style="margin-top:1rem;">Can it send very large files like WeTransfer?</h3>
            <p class="hiw-step-text">Up to 500 MB per file. For a single huge file WeTransfer allows more; for fast device-to-device sharing AirToShare is quicker.</p>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-link" aria-hidden="true"></i> Related guides</h2>
        <div class="hiw-card" style="padding:1.35rem 1.5rem;"><ul class="hiw-step-text">
            <li><a href="{{ url('/send-files-between-devices') }}">Send files between devices</a></li>
            <li><a href="{{ url('/snapdrop-alternative') }}">Snapdrop alternative</a></li>
            <li><a href="{{ url('/airdrop-for-pc') }}">AirDrop for PC</a></li>
        </ul></div>
    </section>

    <section class="hiw-section">
        <div class="hiw-card" style="text-align:center;padding:1.75rem 1.5rem;">
            <h2 class="hiw-section-heading" style="justify-content:center;"><i class="fas fa-bolt" aria-hidden="true"></i> Ready to share?</h2>
            <p class="hiw-step-text" style="max-width:54ch;margin:0 auto 1.25rem;">No signup, no email. Open AirToShare in your browser and send files in seconds.</p>
            <a href="{{ url('/') }}" class="modern-btn primary"><i class="fas fa-paper-plane" aria-hidden="true"></i> Start sharing free</a>
        </div>
    </section>

</div>
@endsection
