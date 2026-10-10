@extends('layouts.app')

@section('title', 'Snapdrop / PairDrop Alternative – With Links & Rooms | AirToShare')
@section('breadcrumb_label', 'Snapdrop Alternative')
@section('description', 'A Snapdrop and PairDrop alternative that adds share links, QR codes, Rooms, passwords and accounts — not just local Wi-Fi. Free, no install, no signup.')
@section('keywords', 'snapdrop alternative, pairdrop alternative, airdrop alternative, local file sharing browser, share files same wifi')

@section('schema')
    <script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "WebApplication",
  "name": "AirToShare – Snapdrop Alternative",
  "description": "A browser file-sharing alternative to Snapdrop and PairDrop",
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
        <span class="hiw-page-badge"><i class="fas fa-bolt" aria-hidden="true"></i> Snapdrop / PairDrop Alternative</span>
        <h1 class="hiw-page-title">A Snapdrop &amp; PairDrop alternative</h1>
        <p class="hiw-page-lead"><strong>Everything Snapdrop does on local Wi‑Fi — plus share links, QR codes, Rooms, passwords and optional accounts.</strong> Free, in the browser, nothing to install.</p>
    </header>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-scale-balanced" aria-hidden="true"></i> Why switch</h2>
        <div class="hiw-card" style="padding:1.35rem 1.5rem;">
            <p class="hiw-step-text">Snapdrop and PairDrop are great for quick same‑network transfers, but they stop there — no links to share beyond the network, no text clipboard, no history. AirToShare keeps the instant local transfer and adds the pieces people keep asking for.</p>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-table" aria-hidden="true"></i> AirToShare vs Snapdrop / PairDrop</h2>
        <div class="hiw-card" style="overflow-x:auto;padding:1.35rem 1.5rem;">
            <table style="width:100%;border-collapse:collapse;">
                <thead><tr><th style="text-align:left;padding:.5rem;">Feature</th><th style="text-align:left;padding:.5rem;">AirToShare</th><th style="text-align:left;padding:.5rem;">Snapdrop / PairDrop</th></tr></thead>
                <tbody>
                    <tr><td style="padding:.5rem;">Same-Wi-Fi transfer</td><td style="padding:.5rem;">Yes</td><td style="padding:.5rem;">Yes</td></tr>
                    <tr><td style="padding:.5rem;">Share link beyond the network</td><td style="padding:.5rem;">Yes (/s/ link)</td><td style="padding:.5rem;">No</td></tr>
                    <tr><td style="padding:.5rem;">Live text / clipboard Rooms</td><td style="padding:.5rem;">Yes</td><td style="padding:.5rem;">Limited</td></tr>
                    <tr><td style="padding:.5rem;">QR code</td><td style="padding:.5rem;">Yes</td><td style="padding:.5rem;">Pairing only</td></tr>
                    <tr><td style="padding:.5rem;">Password &amp; expiry</td><td style="padding:.5rem;">Yes</td><td style="padding:.5rem;">No</td></tr>
                    <tr><td style="padding:.5rem;">Optional account &amp; history</td><td style="padding:.5rem;">Yes</td><td style="padding:.5rem;">No</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-circle-question" aria-hidden="true"></i> FAQ</h2>
        <div class="hiw-card" style="padding:1.35rem 1.5rem;">
            <h3 class="hiw-step-title">Is it open in the browser like Snapdrop?</h3>
            <p class="hiw-step-text">Yes — no install, works in any modern browser.</p>
            <h3 class="hiw-step-title" style="margin-top:1rem;">Does it work outside my local network?</h3>
            <p class="hiw-step-text">Yes — unlike Snapdrop, you get a share link and QR code that work anywhere.</p>
        </div>
    </section>

    <section class="hiw-section">
        <h2 class="hiw-section-heading"><i class="fas fa-link" aria-hidden="true"></i> Related guides</h2>
        <div class="hiw-card" style="padding:1.35rem 1.5rem;"><ul class="hiw-step-text">
            <li><a href="{{ url('/wetransfer-alternative') }}">WeTransfer alternative</a></li>
            <li><a href="{{ url('/airdrop-for-pc') }}">AirDrop for PC</a></li>
            <li><a href="{{ url('/online-clipboard') }}">Online clipboard</a></li>
        </ul></div>
    </section>

    <section class="hiw-section">
        <div class="hiw-card" style="text-align:center;padding:1.75rem 1.5rem;">
            <h2 class="hiw-section-heading" style="justify-content:center;"><i class="fas fa-bolt" aria-hidden="true"></i> Ready to share?</h2>
            <p class="hiw-step-text" style="max-width:54ch;margin:0 auto 1.25rem;">Keep the instant local transfer, gain links, Rooms and more. Open AirToShare free in your browser.</p>
            <a href="{{ url('/') }}" class="modern-btn primary"><i class="fas fa-paper-plane" aria-hidden="true"></i> Start sharing free</a>
        </div>
    </section>

</div>
@endsection
