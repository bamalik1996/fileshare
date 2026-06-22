@extends('layouts.app')

@section('title', 'FAQ - Frequently Asked Questions | AirToShare Help Center')
@section('breadcrumb_label', 'FAQ')
@section('description',
    'Find answers to common questions about AirToShare file sharing. Learn about security, file
    limits, compatibility, and troubleshooting tips.')
@section('keywords',
    'AirToShare FAQ, file sharing help, share text online, share text online free, text share online, online text share, share text file online, online share text, how to share large text files online, how to share text online')

@section('schema')
    <script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "FAQPage",
  "mainEntity": [
    {
      "@@type": "Question",
      "name": "Is AirToShare safe to use?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "Yes, AirToShare is completely safe. Your files never leave your local Wi-Fi network and are not uploaded to any external servers. All sharing happens directly between devices on the same network."
      }
    },
    {
      "@@type": "Question",
      "name": "What file types does AirToShare support?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "AirToShare supports images (JPEG, PNG, GIF, WebP, SVG), documents (PDF, DOC, DOCX), text files, and archives (ZIP, RAR). Each file can be up to 10MB in size."
      }
    },
    {
      "@@type": "Question",
      "name": "How many files can I share at once?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "You can share up to 20 files per session, with each file being up to 10MB in size. This limit helps ensure optimal performance for all users."
      }
    },
    {
      "@@type": "Question",
      "name": "How long do shared files stay available?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "Shared files automatically expire after 6 hours or after 1 hour of inactivity, whichever comes first. This ensures your privacy and helps manage storage space."
      }
    },
    {
      "@@type": "Question",
      "name": "Do I need to create an account for AirToShare?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "No account is required! AirToShare works instantly without any registration. Just connect to the same Wi-Fi network and start sharing."
      }
    },
    {
      "@@type": "Question",
      "name": "How to share text online free?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "You can easily share text online free with AirToShare. Simply paste your text into the text sharing tool and a secure session will be created instantly without any registration."
      }
    },
    {
      "@@type": "Question",
      "name": "How to share large text files online?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "When you need to know how to share large text files online, AirToShare offers a simple solution. You can upload and share text file online up to 10MB per file securely between devices."
      }
    },
    {
      "@@type": "Question",
      "name": "What is the best method to share text online?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "To quickly online share text, use our text sharing feature. It's the fastest way to online text share between your devices. Just paste the text and it's securely shared."
      }
    },
    {
      "@@type": "Question",
      "name": "Do I need an account to use AirToShare?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "No — guest sharing works instantly without registration. Optional free accounts unlock My Shares, 100 files, 1 GB storage, favourites, and 30-day expiry options."
      }
    },
    {
      "@@type": "Question",
      "name": "How does email verification work?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "After you register, AirToShare sends a signed verification link to your email. Click Verify Email Address to activate your account. Links expire after 60 minutes; use Resend on the verify page if needed."
      }
    },
    {
      "@@type": "Question",
      "name": "What are Rooms in AirToShare?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "Rooms let multiple people join the same session with a 6-character code. Create a room, share the code or /r/CODE link, and optionally protect it with a password."
      }
    },
    {
      "@@type": "Question",
      "name": "What is end-to-end encryption (E2EE)?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "E2EE encrypts text in your browser before it is saved. The decryption key is in the URL fragment (#k=...) and is never sent to the server. Share the full URL including the fragment with recipients."
      }
    }
  ]
}
</script>
@endsection

@section('content')

<div class="faq-page">

    <header class="faq-page-hero">
        <span class="faq-page-badge"><i class="fas fa-circle-question" aria-hidden="true"></i> Help center</span>
        <h1 class="faq-page-title">Frequently asked questions</h1>
        <p class="faq-page-lead">Quick answers about sharing, accounts, security, and troubleshooting.</p>
    </header>

    <div class="faq-page-toolbar">
        <label class="faq-page-search" for="faqSearchInput">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="search" id="faqSearchInput" class="faq-page-search-input"
                placeholder="Search questions…" autocomplete="off" spellcheck="false">
        </label>

        <div class="faq-page-filters" role="tablist" aria-label="Filter by topic">
            <button type="button" class="faq-page-filter is-active" data-category="all" role="tab" aria-selected="true">
                <i class="fas fa-list" aria-hidden="true"></i>
                <span>All</span>
                <span class="faq-page-filter-count" data-count-for="all">0</span>
            </button>
            <button type="button" class="faq-page-filter" data-category="accounts" role="tab" aria-selected="false">
                <i class="fas fa-user-circle" aria-hidden="true"></i>
                <span>Accounts</span>
                <span class="faq-page-filter-count" data-count-for="accounts">0</span>
            </button>
            <button type="button" class="faq-page-filter" data-category="security" role="tab" aria-selected="false">
                <i class="fas fa-shield-alt" aria-hidden="true"></i>
                <span>Security</span>
                <span class="faq-page-filter-count" data-count-for="security">0</span>
            </button>
            <button type="button" class="faq-page-filter" data-category="usage" role="tab" aria-selected="false">
                <i class="fas fa-sliders" aria-hidden="true"></i>
                <span>Features</span>
                <span class="faq-page-filter-count" data-count-for="usage">0</span>
            </button>
            <button type="button" class="faq-page-filter" data-category="technical" role="tab" aria-selected="false">
                <i class="fas fa-wrench" aria-hidden="true"></i>
                <span>Technical</span>
                <span class="faq-page-filter-count" data-count-for="technical">0</span>
            </button>
        </div>

        <p class="faq-page-meta" id="faqResultsMeta" aria-live="polite"></p>
    </div>

    <div class="faq-page-card">
        <div class="faq-page-list" id="faqList">
            <!-- Accounts -->
            <div class="faq-item" data-category="accounts">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">Do I need an account to use AirToShare?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p><strong>No</strong> — guest sharing works instantly with no sign-up. Create a <strong>free account</strong> if you want My Shares, higher limits (100 files / 1 GB), favourites, and longer expiry options.</p>
                </div>
            </div>

            <div class="faq-item" data-category="accounts">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">How do I register and verify my email?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>Click <strong>Register</strong>, enter your email and password, then check your inbox for a <strong>Verify Email Address</strong> link. Click it within 60 minutes. If it expires, log in and use <strong>Resend verification email</strong>.</p>
                </div>
            </div>

            <div class="faq-item" data-category="accounts">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">What is My Shares?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p><strong>My Shares</strong> is your account dashboard. It lists shares you created while logged in — with expiry dates, file counts, public links, and quick open actions. Find it in the header after you log in.</p>
                </div>
            </div>

            <div class="faq-item" data-category="accounts">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">Why is My Shares empty after I uploaded files?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>Shares only appear when you upload or save text <strong>while logged in</strong> on the <strong>same site URL</strong> you used to register (e.g. always use <code>dev.fileshare.test</code>, not a different localhost port). Guest uploads before login are linked to your account when you log in on the same browser.</p>
                </div>
            </div>

            <div class="faq-item" data-category="accounts">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">How do favourites work?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>On My Shares, click the <strong>star icon</strong> on any share card to favourite it (max <strong>50</strong>). Favourited shares show a badge for quick access. Click the star again to remove.</p>
                </div>
            </div>

            <div class="faq-item" data-category="accounts">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">What are the limits for guest vs account users?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <ul>
                        <li><strong>Guests:</strong> 50 active files, 25 MB per file, default 24h expiry</li>
                        <li><strong>Accounts:</strong> 100 active files, 1 GB total storage, up to 30-day expiry</li>
                        <li><strong>Large files:</strong> chunked upload supports up to 500 MB per file (over 5 MB)</li>
                    </ul>
                </div>
            </div>

            <!-- Security & Privacy Questions -->
            <div class="faq-item" data-category="security">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">Is AirToShare safe to use?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>Yes, AirToShare is completely safe to use. Your files never leave your local Wi-Fi network and are
                        not uploaded to any external servers. All sharing happens directly between devices on the same
                        network.</p>
                    <p>Key security features:</p>
                    <ul>
                        <li>Files stay on your local network only</li>
                        <li>No external server uploads</li>
                        <li>Automatic content expiration</li>
                        <li>Minimal, anonymous usage analytics</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item" data-category="security">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">Can other people on the same Wi-Fi see my files?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>Files are associated with your specific IP address. Only devices using the same IP address can access
                        the shared content. However, if multiple devices share the same public IP (common in home networks),
                        they may be able to access each other's content.</p>
                    <p>For maximum privacy, use AirToShare on trusted networks only.</p>
                </div>
            </div>

            <div class="faq-item" data-category="security">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">How long do my files stay on the server?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>Files automatically expire and are deleted under these conditions:</p>
                    <ul>
                        <li>After the chosen expiry (default <strong>24 hours</strong> for guests; up to <strong>30 days</strong> for accounts)</li>
                        <li>When expired shares are read or cleaned up by the hourly job</li>
                        <li>When you manually delete them</li>
                    </ul>
                    <p>This automatic cleanup ensures your privacy and helps manage storage space.</p>
                </div>
            </div>

            <div class="faq-item" data-category="security">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">Do you collect any personal information?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>We collect minimal usage data to improve the app. Optional accounts store your email and password hash — never the plain password. Guest sessions use your IP only to associate files; account sessions tie shares to your account ID.</p>
                    <p>Enable <strong>E2EE</strong> on text shares when you want encryption keys to stay only in the browser (URL fragment), not on the server.</p>
                </div>
            </div>

            <div class="faq-item" data-category="security">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">What is end-to-end encryption (E2EE)?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>When enabled, text is encrypted in your browser before saving. The key lives in the URL <strong>#k=...</strong> fragment and is never sent to our server. Share the <strong>complete URL</strong> with anyone who should read it.</p>
                    <p>E2EE requires HTTPS or <code>http://127.0.0.1</code> — on insecure local HTTP hostnames the option is disabled.</p>
                </div>
            </div>

            <div class="faq-item" data-category="security">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">Can I password-protect a share?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>Yes. Set a password (6–128 characters) when creating or updating a share or room. Viewers must enter it before seeing content. Wrong passwords show a generic error; repeated failures are rate-limited per IP.</p>
                </div>
            </div>

            <div class="faq-item" data-category="security">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">Are uploaded files scanned for viruses?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>Uploads are queued for virus scanning. Downloads may return <strong>425</strong> while a scan is pending. Infected files are blocked (<strong>451</strong>) and removed. E2EE files skip server-side scanning by design.</p>
                </div>
            </div>

            <!-- Usage & Features Questions -->
            <div class="faq-item" data-category="usage">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">What file types does AirToShare support?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>AirToShare supports a wide range of file types:</p>
                    <ul>
                        <li><strong>Images:</strong> JPEG, PNG, GIF, WebP, SVG</li>
                        <li><strong>Video & audio:</strong> common browser-playable formats</li>
                        <li><strong>Documents:</strong> PDF, DOC, DOCX, plain text</li>
                        <li><strong>Archives:</strong> ZIP, RAR</li>
                    </ul>
                    <p>Standard uploads: up to <strong>25 MB</strong> per file. Chunked uploads: up to <strong>500 MB</strong>. Guests: 50 files; accounts: 100 files and 1 GB total.</p>
                </div>
            </div>

            <div class="faq-item" data-category="usage">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">How many files can I share at once?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>Guest users: up to <strong>50 active files</strong>. Registered accounts: up to <strong>100 files</strong> and <strong>1 GB</strong> total storage. Each standard upload can be up to <strong>25 MB</strong>; larger files use chunked upload (up to 500 MB).</p>
                </div>
            </div>

            <div class="faq-item" data-category="usage">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">Can I download multiple files at once?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>Yes! You can select multiple files using the checkboxes and download them as a ZIP archive. This
                        feature makes it easy to download several files in one go.</p>
                    <p>To download multiple files:</p>
                    <ul>
                        <li>Check the boxes next to files you want</li>
                        <li>Click the "Download" button</li>
                        <li>A ZIP file will be created and downloaded automatically</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item" data-category="usage">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">Can I send files via email using AirToShare?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>Yes, you can email files directly from AirToShare. Select the files you want to send, click the
                        "Email" button, and fill in the recipient's email address along with a custom message.</p>
                    <p>The files will be sent as email attachments, making it easy to share with people not on your network.
                    </p>
                </div>
            </div>

            <div class="faq-item" data-category="usage">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">Do I need to create an account for AirToShare?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>No — start sharing immediately as a guest. <strong>Register free</strong> for My Shares, favourites, higher limits, public gallery controls, and 30-day expiry.</p>
                </div>
            </div>

            <div class="faq-item" data-category="usage">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">What are Rooms and how do I join one?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>Rooms let others join your session with a <strong>6-character code</strong>. Click <strong>Join Room</strong>, enter the code, and press Enter. Create a room from the home page and share <code>/r/YOURCODE</code> or the code itself.</p>
                </div>
            </div>

            <div class="faq-item" data-category="usage">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">How do public gallery links work?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>From <strong>My Shares</strong>, click <strong>Enable public</strong> on a share you own. You get a link like <code>/p/abc123xyz</code> that anyone can open read-only. Disable public anytime to revoke the link.</p>
                </div>
            </div>

            <div class="faq-item" data-category="usage">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">Can I preview files before downloading?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>Yes. Images, PDFs, and videos show inline previews. Click a file to open the <strong>fullscreen preview modal</strong> — navigate between files, download, or share from there.</p>
                </div>
            </div>

            <div class="faq-item" data-category="usage">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">Can I share text as well as files with AirToShare?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>Absolutely! AirToShare has a dedicated text sharing feature. You can paste up to 50,000 characters of
                        text, and it will automatically detect and make URLs clickable.</p>
                    <p>Text features include:</p>
                    <ul>
                        <li>Character counter</li>
                        <li>Automatic link detection</li>
                        <li>Copy to clipboard functionality</li>
                        <li>Same expiration rules as files</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item" data-category="usage">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">How do I share text online free?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>You can seamlessly <strong>share text online free</strong> with AirToShare. Simply paste your text into our dedicated text sharing tab to <strong>share text online</strong> across your local network without any hidden fees or registration requirements. It's an instant and secure solution.</p>
                </div>
            </div>

            <div class="faq-item" data-category="usage">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">How to share large text files online?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>If you are wondering <strong>how to share large text files online</strong>, AirToShare makes it incredibly easy. You can securely <strong>share text file online</strong> with file sizes up to 10MB per file directly between devices. Whether it's code snippets, log files, or extensive notes, simply upload them to our platform.</p>
                </div>
            </div>

            <div class="faq-item" data-category="usage">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">What is the best way for online text share?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>For a quick <strong>online text share</strong>, AirToShare provides an instant text sharing feature. It's the most efficient way to <strong>text share online</strong> and <strong>online share text</strong> between your phone, tablet, and computer. Just copy the text, paste it into our tool, and it will be available instantly to all connected devices.</p>
                </div>
            </div>
            
            <!-- Technical Issues Questions -->
            <div class="faq-item" data-category="technical">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">What devices and browsers does AirToShare support?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>AirToShare works on any device with a modern web browser, including:</p>
                    <ul>
                        <li><strong>Desktop:</strong> Windows, Mac, Linux</li>
                        <li><strong>Mobile:</strong> iOS (Safari), Android (Chrome)</li>
                        <li><strong>Browsers:</strong> Chrome, Firefox, Safari, Edge</li>
                    </ul>
                    <p>No apps or software installation required - just open your web browser!</p>
                </div>
            </div>

            <div class="faq-item" data-category="technical">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">Why can't I see files from another device?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>Make sure both devices are:</p>
                    <ul>
                        <li>Connected to the same Wi-Fi network</li>
                        <li>Using the same IP address (common in home networks)</li>
                        <li>Accessing the same AirToShare URL</li>
                    </ul>
                    <p>If you're still having issues, try refreshing the page or checking your network connection.</p>
                </div>
            </div>

            <div class="faq-item" data-category="technical">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">Upload failed - what should I do?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>If your upload fails, check these common issues:</p>
                    <ul>
                        <li>File exceeds 25 MB (use chunked path for larger files up to 500 MB)</li>
                        <li>Unsupported file type</li>
                        <li>Active file or storage limit reached (register for higher account limits)</li>
                        <li>Poor network connection</li>
                    </ul>
                    <p>Try refreshing the page and uploading again. If problems persist, try a different browser.</p>
                </div>
            </div>

            <div class="faq-item" data-category="technical">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">The page is loading slowly - why?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>Slow loading can be caused by:</p>
                    <ul>
                        <li>Weak Wi-Fi signal</li>
                        <li>Network congestion</li>
                        <li>Large files being processed</li>
                        <li>Browser cache issues</li>
                    </ul>
                    <p>Try moving closer to your Wi-Fi router, clearing browser cache, or refreshing the page.</p>
                </div>
            </div>

            <div class="faq-item" data-category="technical">
                <button type="button" class="faq-question" aria-expanded="false">
                    <span class="faq-question-text">Can I use AirToShare on mobile data?</span>
                    <i class="fas fa-chevron-down faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer" hidden>
                    <p>While AirToShare works on mobile data, it's designed for local Wi-Fi networks. Using mobile data
                        means:</p>
                    <ul>
                        <li>Files are only accessible from that specific device</li>
                        <li>No cross-device sharing capability</li>
                        <li>Uses your mobile data allowance</li>
                    </ul>
                    <p>For the best experience and true cross-device sharing, use Wi-Fi.</p>
                </div>
            </div>
        </div>

        <div class="faq-page-empty hidden" id="faqEmptyState">
            <i class="fas fa-search" aria-hidden="true"></i>
            <p><strong>No matching questions</strong></p>
            <p>Try a different search term or pick another category.</p>
        </div>
    </div>

    <aside class="faq-page-contact">
        <div class="faq-page-contact-inner">
            <div class="faq-page-contact-text">
                <h2><i class="fas fa-headset" aria-hidden="true"></i> Still need help?</h2>
                <p>Can't find your answer? Send us a message and we'll get back to you.</p>
            </div>
            <a href="{{ url('/feedback') }}" class="modern-btn faq-page-contact-btn">
                <i class="fas fa-envelope" aria-hidden="true"></i>
                Contact support
            </a>
        </div>
    </aside>

</div>{{-- .faq-page --}}

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            initFaqPage();
        });

        function initFaqPage() {
            var page = document.querySelector('.faq-page');
            if (!page) return;

            var items = Array.from(page.querySelectorAll('.faq-item'));
            var filters = page.querySelectorAll('.faq-page-filter');
            var searchInput = document.getElementById('faqSearchInput');
            var meta = document.getElementById('faqResultsMeta');
            var empty = document.getElementById('faqEmptyState');
            var activeCategory = 'all';
            var searchTerm = '';

            updateFilterCounts(items, filters);
            applyFaqFilters();

            page.querySelectorAll('.faq-question').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var item = btn.closest('.faq-item');
                    var answer = item.querySelector('.faq-answer');
                    var isOpen = item.classList.contains('is-open');

                    closeAllFaqItems(page);

                    if (!isOpen) {
                        item.classList.add('is-open');
                        btn.setAttribute('aria-expanded', 'true');
                        answer.hidden = false;
                    }
                });
            });

            filters.forEach(function (pill) {
                pill.addEventListener('click', function () {
                    activeCategory = pill.getAttribute('data-category') || 'all';
                    filters.forEach(function (p) {
                        var on = p === pill;
                        p.classList.toggle('is-active', on);
                        p.setAttribute('aria-selected', on ? 'true' : 'false');
                    });
                    if (searchInput) searchInput.value = '';
                    searchTerm = '';
                    closeAllFaqItems(page);
                    applyFaqFilters();
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    searchTerm = (searchInput.value || '').trim().toLowerCase();
                    if (searchTerm !== '') {
                        activeCategory = 'all';
                        filters.forEach(function (p) {
                            var all = p.getAttribute('data-category') === 'all';
                            p.classList.toggle('is-active', all);
                            p.setAttribute('aria-selected', all ? 'true' : 'false');
                        });
                    }
                    closeAllFaqItems(page);
                    applyFaqFilters();
                });
            }

            function applyFaqFilters() {
                var visible = 0;

                items.forEach(function (item) {
                    var cat = item.getAttribute('data-category') || '';
                    var q = (item.querySelector('.faq-question-text') || {}).textContent || '';
                    var a = (item.querySelector('.faq-answer') || {}).textContent || '';
                    var matchCat = activeCategory === 'all' || cat === activeCategory;
                    var matchSearch = !searchTerm || q.toLowerCase().indexOf(searchTerm) !== -1
                        || a.toLowerCase().indexOf(searchTerm) !== -1;
                    var show = matchCat && matchSearch;

                    item.classList.toggle('is-hidden', !show);
                    if (show) visible++;
                });

                if (meta) {
                    meta.textContent = visible === 1
                        ? 'Showing 1 question'
                        : 'Showing ' + visible + ' questions';
                }
                if (empty) {
                    empty.classList.toggle('hidden', visible > 0);
                }
            }

            function closeAllFaqItems(root) {
                root.querySelectorAll('.faq-item.is-open').forEach(function (item) {
                    item.classList.remove('is-open');
                    var btn = item.querySelector('.faq-question');
                    var answer = item.querySelector('.faq-answer');
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                    if (answer) answer.hidden = true;
                });
            }

            function updateFilterCounts(allItems, allFilters) {
                allFilters.forEach(function (pill) {
                    var cat = pill.getAttribute('data-category');
                    var countEl = pill.querySelector('.faq-page-filter-count');
                    if (!countEl) return;
                    var n = cat === 'all'
                        ? allItems.length
                        : allItems.filter(function (i) { return i.getAttribute('data-category') === cat; }).length;
                    countEl.textContent = String(n);
                });
            }
        }
    </script>
@endsection
