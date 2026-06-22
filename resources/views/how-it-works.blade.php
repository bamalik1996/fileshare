@extends('layouts.app')

@section('title', 'How AirToShare Works - Step by Step Guide | File Sharing Tutorial')
@section('breadcrumb_label', 'How It Works')
@section('description',
    'Learn how to use AirToShare for instant file and text sharing. Step-by-step guide for Wi-Fi sync, share links, rooms, passwords, and optional encryption.')
@section('keywords',
    'file sharing app, transfer files via Wi-Fi, local network transfer, cross-device file share, share link, password protected share')

@section('schema')
    <script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "HowTo",
  "name": "How to Share Files with AirToShare",
  "description": "Step-by-step guide to share files and text using AirToShare on the same network or via a share link",
  "image": "{{ url('/logo.svg') }}",
  "totalTime": "PT3M",
  "estimatedCost": {
    "@@type": "MonetaryAmount",
    "currency": "USD",
    "value": "0"
  },
  "step": [
    {
      "@@type": "HowToStep",
      "name": "Open AirToShare",
      "text": "Open AirToShare in any web browser on the devices you want to use. No app install required."
    },
    {
      "@@type": "HowToStep",
      "name": "Add content",
      "text": "Upload files or paste text. Optionally set a password, expiry, end-to-end encryption, or create a Room."
    },
    {
      "@@type": "HowToStep",
      "name": "Share access",
      "text": "Devices on the same Wi-Fi sync automatically, or copy your share link / QR code. Others can join a Room with a short code."
    }
  ]
}
</script>
@endsection

@section('content')

<div class="hiw-page">

    <header class="hiw-page-hero">
        <span class="hiw-page-badge"><i class="fas fa-route" aria-hidden="true"></i> Step-by-step guide</span>
        <h1 class="hiw-page-title">How AirToShare works</h1>
        <p class="hiw-page-lead">
            Share files and text in seconds — on the same Wi‑Fi, via a link, or in a collaborative Room.
            No complicated setup required.
        </p>
    </header>

    <nav class="hiw-page-nav" aria-label="Page sections">
        <a class="hiw-page-nav-link is-active" href="#hiw-steps"><i class="fas fa-list-ol" aria-hidden="true"></i> Steps</a>
        <a class="hiw-page-nav-link" href="#hiw-ways"><i class="fas fa-share-nodes" aria-hidden="true"></i> Ways to share</a>
        <a class="hiw-page-nav-link" href="#hiw-features"><i class="fas fa-star" aria-hidden="true"></i> Features</a>
        <a class="hiw-page-nav-link" href="#hiw-security"><i class="fas fa-shield-alt" aria-hidden="true"></i> Security</a>
    </nav>

    <section class="hiw-section" id="hiw-steps" aria-labelledby="hiw-steps-heading">
        <h2 class="hiw-section-heading" id="hiw-steps-heading">
            <i class="fas fa-play-circle" aria-hidden="true"></i>
            Three steps to start
        </h2>

        <div class="hiw-card">
            <ol class="hiw-steps-list">
                <li class="hiw-step">
                    <div class="hiw-step-marker" aria-hidden="true">1</div>
                    <div class="hiw-step-body">
                        <div class="hiw-step-icon"><i class="fas fa-globe" aria-hidden="true"></i></div>
                        <h3 class="hiw-step-title">Open AirToShare</h3>
                        <p class="hiw-step-text">
                            Visit the site in any modern browser — phone, tablet, or desktop. No download or sign-up needed.
                            Create a <strong>free account</strong> if you want My Shares, higher limits, and longer expiry options.
                        </p>
                    </div>
                </li>
                <li class="hiw-step">
                    <div class="hiw-step-marker" aria-hidden="true">2</div>
                    <div class="hiw-step-body">
                        <div class="hiw-step-icon"><i class="fas fa-cloud-upload-alt" aria-hidden="true"></i></div>
                        <h3 class="hiw-step-title">Add your content</h3>
                        <p class="hiw-step-text">
                            Drag and drop files or paste text. Switch between <strong>Text</strong> and <strong>Files</strong> tabs.
                            Optionally add a password, set expiry, enable end-to-end encryption, or create a <strong>Room</strong> for group sharing.
                        </p>
                    </div>
                </li>
                <li class="hiw-step">
                    <div class="hiw-step-marker" aria-hidden="true">3</div>
                    <div class="hiw-step-body">
                        <div class="hiw-step-icon"><i class="fas fa-qrcode" aria-hidden="true"></i></div>
                        <h3 class="hiw-step-title">Share access</h3>
                        <p class="hiw-step-text">
                            On the same Wi‑Fi, content syncs automatically between devices. Copy your <strong>share link</strong>
                            (<code>/s/…</code>) or show a QR code for remote access. Teammates can join a Room with a 6-character code.
                        </p>
                    </div>
                </li>
            </ol>
        </div>
    </section>

    <section class="hiw-section" id="hiw-ways" aria-labelledby="hiw-ways-heading">
        <h2 class="hiw-section-heading" id="hiw-ways-heading">
            <i class="fas fa-share-nodes" aria-hidden="true"></i>
            Ways to share
        </h2>

        <div class="hiw-ways-grid">
            <article class="hiw-way-card">
                <div class="hiw-way-icon"><i class="fas fa-wifi" aria-hidden="true"></i></div>
                <h3 class="hiw-way-title">Same Wi‑Fi</h3>
                <p class="hiw-way-text">Devices on your local network see the same session instantly — ideal for quick transfers between your own devices.</p>
            </article>
            <article class="hiw-way-card">
                <div class="hiw-way-icon"><i class="fas fa-link" aria-hidden="true"></i></div>
                <h3 class="hiw-way-title">Share link &amp; QR</h3>
                <p class="hiw-way-text">Copy your unique <code>/s/{uuid}</code> link or scan a QR code so anyone with the URL can open your share.</p>
            </article>
            <article class="hiw-way-card">
                <div class="hiw-way-icon"><i class="fas fa-users" aria-hidden="true"></i></div>
                <h3 class="hiw-way-title">Rooms</h3>
                <p class="hiw-way-text">Create a Room, share the 6-character code or <code>/r/CODE</code> link, and collaborate in real time with clipboard sync.</p>
            </article>
            <article class="hiw-way-card">
                <div class="hiw-way-icon"><i class="fas fa-lock" aria-hidden="true"></i></div>
                <h3 class="hiw-way-title">Password &amp; E2EE</h3>
                <p class="hiw-way-text">Lock a share or Room with a password, or encrypt text in your browser — the decryption key stays in the URL fragment.</p>
            </article>
        </div>
    </section>

    <section class="hiw-section" id="hiw-features" aria-labelledby="hiw-features-heading">
        <h2 class="hiw-section-heading" id="hiw-features-heading">
            <i class="fas fa-star" aria-hidden="true"></i>
            Built for everyday sharing
        </h2>

        <div class="hiw-features-grid">
            <article class="hiw-feature">
                <div class="hiw-feature-icon"><i class="fas fa-bolt" aria-hidden="true"></i></div>
                <h3 class="hiw-feature-title">Fast transfers</h3>
                <p class="hiw-feature-text">Local network sync is instant. Large files use resumable chunked uploads (up to 500&nbsp;MB per file).</p>
            </article>
            <article class="hiw-feature">
                <div class="hiw-feature-icon"><i class="fas fa-mobile-alt" aria-hidden="true"></i></div>
                <h3 class="hiw-feature-title">Works everywhere</h3>
                <p class="hiw-feature-text">Any device with a browser — Windows, macOS, Linux, iOS, or Android. Install as a PWA for quick access.</p>
            </article>
            <article class="hiw-feature">
                <div class="hiw-feature-icon"><i class="fas fa-folder-open" aria-hidden="true"></i></div>
                <h3 class="hiw-feature-title">My Shares</h3>
                <p class="hiw-feature-text">Free accounts get a dashboard to manage shares, favourites, public gallery links, and expiry settings.</p>
            </article>
            <article class="hiw-feature">
                <div class="hiw-feature-icon"><i class="fas fa-file-archive" aria-hidden="true"></i></div>
                <h3 class="hiw-feature-title">Download options</h3>
                <p class="hiw-feature-text">Download files individually, grab everything as a ZIP, or email attachments directly from the share view.</p>
            </article>
            <article class="hiw-feature">
                <div class="hiw-feature-icon"><i class="fas fa-eye" aria-hidden="true"></i></div>
                <h3 class="hiw-feature-title">Rich previews</h3>
                <p class="hiw-feature-text">Preview images, PDFs, and text inline. URLs in shared text are automatically linked for easy clicking.</p>
            </article>
            <article class="hiw-feature">
                <div class="hiw-feature-icon"><i class="fas fa-clock" aria-hidden="true"></i></div>
                <h3 class="hiw-feature-title">Auto expiry</h3>
                <p class="hiw-feature-text">Shares clean up automatically after expiry or inactivity — guests get shorter windows; accounts can choose longer options.</p>
            </article>
        </div>
    </section>

    <section class="hiw-section" id="hiw-security" aria-labelledby="hiw-security-heading">
        <h2 class="hiw-section-heading" id="hiw-security-heading">
            <i class="fas fa-shield-alt" aria-hidden="true"></i>
            Security &amp; privacy
        </h2>

        <div class="hiw-card hiw-security-card">
            <ul class="hiw-security-list">
                <li class="hiw-security-item">
                    <span class="hiw-security-icon"><i class="fas fa-network-wired" aria-hidden="true"></i></span>
                    <div>
                        <strong>Local-first option</strong>
                        <p>Same-network sharing keeps traffic on your Wi‑Fi when you use IP-based sessions.</p>
                    </div>
                </li>
                <li class="hiw-security-item">
                    <span class="hiw-security-icon"><i class="fas fa-key" aria-hidden="true"></i></span>
                    <div>
                        <strong>Password-protected shares</strong>
                        <p>Optional bcrypt password gate with rate limiting on failed attempts.</p>
                    </div>
                </li>
                <li class="hiw-security-item">
                    <span class="hiw-security-icon"><i class="fas fa-user-secret" aria-hidden="true"></i></span>
                    <div>
                        <strong>Client-side E2EE</strong>
                        <p>Encrypted text keys live in the URL fragment and are never sent to the server.</p>
                    </div>
                </li>
                <li class="hiw-security-item">
                    <span class="hiw-security-icon"><i class="fas fa-trash-alt" aria-hidden="true"></i></span>
                    <div>
                        <strong>Automatic cleanup</strong>
                        <p>Expired and inactive shares are removed on a schedule so data does not linger.</p>
                    </div>
                </li>
            </ul>
        </div>
    </section>

    <section class="hiw-page-cta" aria-labelledby="hiw-cta-heading">
        <div class="hiw-page-cta-inner">
            <div class="hiw-page-cta-text">
                <h2 id="hiw-cta-heading"><i class="fas fa-rocket" aria-hidden="true"></i> Ready to start?</h2>
                <p>Open AirToShare and share your first file or text snippet in under a minute.</p>
            </div>
            <div class="hiw-page-cta-actions">
                <a href="{{ url('/') }}" class="modern-btn hiw-page-cta-btn">
                    <i class="fas fa-share-alt" aria-hidden="true"></i>
                    Start sharing
                </a>
                <a href="{{ url('/faq') }}" class="modern-btn secondary hiw-page-cta-btn">
                    <i class="fas fa-circle-question" aria-hidden="true"></i>
                    Read FAQ
                </a>
            </div>
        </div>
    </section>

</div>

<script>
(function () {
    'use strict';

    var navLinks = document.querySelectorAll('.hiw-page-nav-link');
    var sections = [];

    navLinks.forEach(function (link) {
        var id = (link.getAttribute('href') || '').replace('#', '');
        if (id) {
            var el = document.getElementById(id);
            if (el) sections.push({ id: id, el: el });
        }

        link.addEventListener('click', function (event) {
            var targetId = (link.getAttribute('href') || '').replace('#', '');
            var target = targetId ? document.getElementById(targetId) : null;
            if (!target) return;
            event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            navLinks.forEach(function (l) { l.classList.remove('is-active'); });
            link.classList.add('is-active');
        });
    });

    if (!sections.length || !('IntersectionObserver' in window)) return;

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            navLinks.forEach(function (link) {
                link.classList.toggle('is-active', link.getAttribute('href') === '#' + entry.target.id);
            });
        });
    }, { rootMargin: '-40% 0px -50% 0px', threshold: 0 });

    sections.forEach(function (s) { observer.observe(s.el); });
})();
</script>

@endsection
