<!DOCTYPE html>
<html lang="en-us">

<head>


    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'AirToShare - Instant File Sharing Across Devices')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (isset($share) && $share)
        <meta name="airtoshare-share-id" content="{{ $share->id }}">
    @endif
    <meta name="theme-color" content="#1A73E8">

    {{--
        PDF.js viewer URL (Requirement 6.2). Pinned via config so the
        Preview_Renderer can display application/pdf attachments using a
        known viewer build. Read by /assets/js/preview-renderer.js; if
        the tag is missing the renderer falls back to the bundled
        /assets/pdfjs/web/viewer.html path.
    --}}
    <meta name="airtoshare-pdfjs-viewer" content="{{ config('airtoshare.pdfjs_viewer_url') }}">

    {{-- Laravel Reverb / Echo client configuration (Requirement 14.1) --}}
    <meta name="airtoshare-reverb-key" content="{{ env('REVERB_APP_KEY') }}">
    <meta name="airtoshare-reverb-host" content="{{ env('REVERB_FRONTEND_HOST', env('REVERB_HOST', 'localhost')) }}">
    <meta name="airtoshare-reverb-port" content="{{ env('REVERB_PORT', 6001) }}">
    <meta name="airtoshare-reverb-scheme" content="{{ env('REVERB_SCHEME', 'http') }}">
    <meta name="airtoshare-owner-ip" content="{{ request()->ip() }}">

    {{--
        Theme bootstrap (Requirements 4.3, 4.4, 4.5, 4.6, 4.7, 4.8).
        Runs synchronously before any stylesheet or script so the resolved theme
        is applied to <html data-theme="..."> before first paint, preventing FOUC.
        - Reads localStorage["airtoshare_theme"] when set to "light" or "dark" (4.5, 4.6)
        - Otherwise uses prefers-color-scheme: dark (4.3)
        - Otherwise defaults to "light" (4.4)
        - Self-heals an invalid stored value by overwriting it with the resolved theme (4.8)
        - Any read/write error is swallowed and the page falls back to light without blocking render (4.7)
    --}}
    <script>
        (function () {
            try {
                var k = 'airtoshare_theme';
                var stored = localStorage.getItem(k);
                var theme;
                if (stored === 'light' || stored === 'dark') {
                    theme = stored;
                } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    theme = 'dark';
                } else {
                    theme = 'light';
                }
                document.documentElement.dataset.theme = theme;
                if (stored !== null && stored !== theme) {
                    localStorage.setItem(k, theme);
                }
            } catch (e) {
                document.documentElement.dataset.theme = 'light';
            }
        })();
    </script>

    <!-- SEO Meta Tags -->
    <meta name="description" content="@yield('description', 'AirToShare - Share files and text instantly across devices on the same network. Simple, fast, and secure file sharing without accounts or external servers.')">
    <meta name="keywords" content="@yield('keywords', 'file sharing, instant sharing, local network, secure sharing, cross-device, no account required')">

    <meta name="author" content="AirToShare">
    @php
        $robotsMeta = trim(View::getSection('robots') ?? '');
        if ($robotsMeta === '') {
            $robotsMeta = ($seoNoindex ?? false) ? 'noindex, nofollow' : 'index, follow';
        }
        $isNoindexPage = str_contains(strtolower($robotsMeta), 'noindex');
    @endphp
    <meta name="robots" content="{{ $robotsMeta }}">
    @unless($isNoindexPage)
        <link rel="canonical" href="{{ url()->current() }}">
    @endunless

    <!-- Additional SEO Meta Tags -->
    <meta name="language" content="English">
    <meta name="revisit-after" content="7 days">
    <meta name="distribution" content="global">
    <meta name="rating" content="general">
    <meta name="copyright" content="AirToShare {{ date('Y') }}">

    <!-- Favicon and Icons -->

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="{{ route('manifest.show') }}">
    <!-- Sitemap Reference -->
    <link rel="sitemap" type="application/xml" title="Sitemap" href="{{ url('/sitemap.xml') }}">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="@yield('og_title', \Illuminate\Support\Facades\View::getSection('title', 'AirToShare - Instant File Sharing'))">
    <meta property="og:description" content="@yield('og_description', \Illuminate\Support\Facades\View::getSection('description', 'Share files and text instantly across devices on the same network. Simple, fast, and secure.'))">
    <meta property="og:type" content="@yield('og_type', 'website')">
    @hasSection('og_published_time')
        <meta property="article:published_time" content="@yield('og_published_time')">
    @endif
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="AirToShare">
    <meta property="og:locale" content="en_US">
    <meta property="og:image" content="@yield('og_image', url('/logo.svg'))">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="AirToShare - Instant File Sharing">
    <meta property="fb:app_id" content="YOUR_FACEBOOK_APP_ID">
    <meta property="fb:pages" content="airtoshare">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter_title', \Illuminate\Support\Facades\View::getSection('title', 'AirToShare - Instant File Sharing'))">
    <meta name="twitter:description" content="@yield('twitter_description', \Illuminate\Support\Facades\View::getSection('description', 'Share files and text instantly across devices on the same network.'))">
    <meta name="twitter:image" content="@yield('twitter_image', url('/logo.svg'))">
    <meta name="twitter:site" content="@AirToShare">
    <meta name="twitter:creator" content="@AirToShare">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ asset('assets/css/custom.css') }}?v=18" rel="stylesheet" />

    <!-- Schema.org JSON-LD -->
    @yield('schema')

    <!-- Additional Schema for Organization -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "Organization",
      "name": "AirToShare",
      "url": "{{ url('/') }}",
      "logo": "{{ url('/logo.svg') }}",
      "description": "Instant file sharing across devices on the same network",
      "sameAs": [
        "https://web.facebook.com/airtoshare/",
        "https://github.com/airtoshare",
        "https://twitter.com/airtoshare",
        "https://www.linkedin.com/company/airtoshare"
      ],
      "contactPoint": {
        "@@type": "ContactPoint",
        "contactType": "Customer Support",
        "availableLanguage": "English",
        "url": "{{ url('/feedback') }}"
      }
    }
    </script>

    <!-- Breadcrumb Schema -->
    @if (request()->path() !== '/' && ! $isNoindexPage)
        @php
            $breadcrumbItems = [
                ['name' => 'Home', 'item' => url('/')],
            ];
            if (trim(View::getSection('breadcrumb_parent_url') ?? '') !== '') {
                $breadcrumbItems[] = [
                    'name' => trim(View::getSection('breadcrumb_parent_name') ?? 'Section'),
                    'item' => trim(View::getSection('breadcrumb_parent_url')),
                ];
            }
            $breadcrumbLabel = trim(View::getSection('breadcrumb_label') ?? '');
            if ($breadcrumbLabel === '') {
                $breadcrumbLabel = trim(View::getSection('title') ?? 'Page');
            }
            $breadcrumbItems[] = [
                'name' => $breadcrumbLabel,
                'item' => url()->current(),
            ];
            $breadcrumbSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => array_map(
                    static fn (array $item, int $index): array => [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => $item['name'],
                        'item' => $item['item'],
                    ],
                    $breadcrumbItems,
                    array_keys($breadcrumbItems),
                ),
            ];
        @endphp
        <script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif



    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <!-- Font Awesome (Deferred) -->
    <link rel="preload" href="{{ asset('assets/font-awesome/css/all.min.css') }}" as="style"
        onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="{{ asset('assets/font-awesome/css/all.min.css') }}">
    </noscript>
    <!-- Bulma CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/bulma.min.css') }}">
    <script src="{{ asset('assets/js/jquery.min.js') }}" defer></script>
    <script defer>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.jQuery) {
                jQuery.ajaxSetup({
                    xhrFields: { withCredentials: true },
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
            }
        });
    </script>
    <!-- QR Code Library -->
    <script src="{{ asset('assets/js/qrcode.min.js') }}" defer></script>
    <!-- Theme Manager (Requirement 4: toggle, persistence, runtime contrast self-check) -->
    <script src="{{ asset('assets/js/theme-manager.js') }}?v=1" defer></script>
    <!-- Clipboard Component (Requirement 5: tri-strategy copy with disabled-while-in-flight,
         confirm indicator, and persistent error banner). Data-attribute driven; binds to
         any [data-copy] / [data-copy-text] element in the page. -->
    <script src="{{ asset('assets/js/clipboard.js') }}?v=1" defer></script>
    <!-- Preview Renderer (Requirement 6: classifier, lazy-load via IntersectionObserver,
         5s out-of-view release, 10s load-error retry control). Data-attribute driven;
         binds to any .preview-row element in the page. -->
    <script src="{{ asset('assets/js/preview-renderer.js') }}?v=2" defer></script>
    {{-- Rich text editor removed — plain textarea for quick text sharing --}}
    <script src="{{ asset('assets/js/upload-manager.js') }}?v=2" defer></script>
    <script src="{{ asset('assets/js/encryption-module.js') }}?v=2" defer></script>
    <script src="{{ asset('assets/js/pwa-manager.js') }}?v=1" defer></script>
    {{-- Realtime + clipboard sync (Requirements 10, 14) --}}
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.19.0/dist/echo.iife.js" defer></script>
    <script src="{{ asset('assets/js/realtime.js') }}?v=2" defer></script>
    <script src="{{ asset('assets/js/clipboard-sync.js') }}?v=1" defer></script>



    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const burger = document.querySelector('.navbar-burger');
            const menu = document.getElementById('siteNavMenu');

            function setNavOpen(open) {
                if (!burger || !menu) return;
                menu.classList.toggle('is-active', open);
                burger.classList.toggle('is-active', open);
                burger.setAttribute('aria-expanded', open ? 'true' : 'false');
                document.body.classList.toggle('nav-open', open);
            }

            if (burger && menu) {
                burger.addEventListener('click', () => {
                    setNavOpen(!menu.classList.contains('is-active'));
                });

                menu.querySelectorAll('.navbar-pill, .navbar-auth-btn').forEach((link) => {
                    link.addEventListener('click', () => setNavOpen(false));
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        setNavOpen(false);
                    }
                });

                document.addEventListener('click', (event) => {
                    if (!menu.classList.contains('is-active')) return;
                    if (menu.contains(event.target) || burger.contains(event.target)) return;
                    setNavOpen(false);
                });
            }
        });

        // Toast Notification System
        window.showToast = function(type, title, message, duration = 4000) {
            const toastContainer = $('.toast-container');
            if (toastContainer.length === 0) {
                $('body').append('<div class="toast-container"></div>');
            }

            const iconMap = {
                success: 'fas fa-check-circle',
                error: 'fas fa-exclamation-circle',
                info: 'fas fa-info-circle',
                warning: 'fas fa-exclamation-triangle'
            };

            const toast = $(`
                <div class="toast ${type}">
                    <div class="toast-icon">
                        <i class="${iconMap[type] || iconMap.info}"></i>
                    </div>
                    <div class="toast-content">
                        <div class="toast-title">${title}</div>
                        <div class="toast-message">${message}</div>
                    </div>
                    <button class="toast-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `);

            $('.toast-container').append(toast);

            // Show toast
            setTimeout(() => toast.addClass('show'), 100);

            // Auto remove
            setTimeout(() => {
                toast.removeClass('show');
                setTimeout(() => toast.remove(), 300);
            }, duration);

            // Manual close
            toast.find('.toast-close').click(function() {
                toast.removeClass('show');
                setTimeout(() => toast.remove(), 300);
            });
        };
    </script>


    <!-- Google Analytics (Add your tracking ID) -->
    @if (config('app.env') === 'production')
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-0228GR7HD3"></script>
        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }
            gtag('js', new Date());

            gtag('config', 'G-0228GR7HD3');
        </script>


<!-- Privacy-friendly analytics by Plausible -->
<script async src="https://plausible.io/js/pa-8cdA0Luu4zuIH4mrkaAlK.js"></script>
<script>
  window.plausible=window.plausible||function(){(plausible.q=plausible.q||[]).push(arguments)},plausible.init=plausible.init||function(i){plausible.o=i||{}};
  plausible.init()
</script>

    @endif

    <script>
        window.safeGtag = function() {
            if (typeof gtag === 'function') {
                gtag(...arguments);
            } else {
                console.log('[Analytics-Dev]', ...arguments);
            }
        };
    </script>
</head>

<body>
    <!-- Toast Container -->
    <div class="toast-container"></div>

    <!-- Site header -->
    <nav class="navbar modern-navbar site-header" role="navigation" aria-label="Main navigation">
        <div class="container navbar-inner">
            <div class="navbar-brand">
                <a class="navbar-logo-link" href="{{ url('/') }}" aria-label="AirToShare home">
                    <img src="/logo.svg" alt="AirToShare" width="125" height="50" />
                </a>

                <button type="button" class="navbar-burger" aria-label="Open menu" aria-controls="siteNavMenu" aria-expanded="false">
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                </button>
            </div>

            <div class="navbar-menu" id="siteNavMenu">
                <div class="navbar-start">
                    <div class="navbar-pills" role="list">
                        <a class="navbar-pill @if(request()->is('/')) is-current @endif" href="{{ url('/') }}" role="listitem">
                            <i class="fas fa-home" aria-hidden="true"></i>
                            <span>Home</span>
                        </a>
                        <a class="navbar-pill @if(request()->is('how-it-works')) is-current @endif" href="{{ url('/how-it-works') }}" role="listitem">
                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                            <span>How It Works</span>
                        </a>
                        <a class="navbar-pill @if(request()->is('faq')) is-current @endif" href="{{ url('/faq') }}" role="listitem">
                            <i class="fas fa-question-circle" aria-hidden="true"></i>
                            <span>FAQ</span>
                        </a>
                        <a class="navbar-pill @if(request()->is('blog') || request()->is('blog/*')) is-current @endif" href="{{ route('blog.index') }}" role="listitem">
                            <i class="fas fa-newspaper" aria-hidden="true"></i>
                            <span>Blog</span>
                        </a>
                        <a class="navbar-pill @if(request()->is('feedback')) is-current @endif" href="{{ url('/feedback') }}" role="listitem">
                            <i class="fas fa-comment" aria-hidden="true"></i>
                            <span>Feedback</span>
                        </a>
                        @if(Route::has('docs.api'))
                            <a class="navbar-pill @if(request()->is('docs*')) is-current @endif" href="{{ route('docs.api') }}" role="listitem">
                                <i class="fas fa-code" aria-hidden="true"></i>
                                <span>API Docs</span>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="navbar-end">
                    <div class="navbar-actions">
                        @auth('account')
                            @php($navAccount = auth('account')->user())
                            <span class="navbar-user-chip" title="{{ $navAccount->email }}">
                                <i class="fas fa-user-circle" aria-hidden="true"></i>
                                {{ \Illuminate\Support\Str::before($navAccount->email, '@') }}
                            </span>
                            <a class="modern-btn secondary navbar-auth-btn @if(request()->routeIs('account.shares')) is-current @endif" href="{{ route('account.shares') }}">
                                <i class="fas fa-folder-open" aria-hidden="true"></i>
                                My Shares
                            </a>
                            <form method="POST" action="{{ route('auth.logout') }}" class="navbar-logout-form">
                                @csrf
                                <button type="submit" class="modern-btn secondary navbar-auth-btn navbar-logout-btn">
                                    <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                                    Log out
                                </button>
                            </form>
                        @else
                            <a class="modern-btn secondary navbar-auth-btn" href="{{ route('auth.login') }}">
                                <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                                Log in
                            </a>
                            <a class="modern-btn navbar-auth-btn" href="{{ route('auth.register') }}">
                                <i class="fas fa-user-plus" aria-hidden="true"></i>
                                Register
                            </a>
                        @endauth

                        @unless(request()->is('/'))
                            <a class="modern-btn navbar-auth-btn navbar-cta-btn" href="{{ url('/') }}">
                                <i class="fas fa-share-alt" aria-hidden="true"></i>
                                Start Sharing
                            </a>
                        @endunless

                        <button class="theme-toggle theme-toggle--header" id="themeToggle" type="button" title="Toggle Dark Mode" aria-label="Toggle dark mode">
                            <i class="fas fa-moon" id="themeIcon"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="modern-container">
        @yield('content')
    </main>

    <!-- Modern Footer -->
    <footer class="modern-footer">
        <div class="container">
            <div class="columns">
                <div class="column">
                    <p>
                        <strong class="gradient-text">AirToShare</strong> &copy; {{ date('Y') }} -
                        Instant file sharing made simple and secure
                    </p>
                </div>
                <div class="column is-narrow">
                    <div class="footer-links">
                        <a href="{{ url('/') }}">Home</a>
                        <a href="{{ url('/how-it-works') }}">How It Works</a>
                        <a href="{{ url('/faq') }}">FAQ</a>
                        <a href="{{ route('blog.index') }}">Blog</a>
                        <a href="{{ url('/feedback') }}">Contact</a>
                        <a href="{{ url('/coming-soon') }}">Coming Soon</a>
                        <a href="{{ url('/sitemap.xml') }}">Sitemap</a>
                    </div>
                    <div class="social-links">
                        <a href="https://web.facebook.com/airtoshare/" target="_blank" rel="noopener noreferrer"
                            title="Follow us on Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/airtoshare" target="_blank" rel="noopener noreferrer"
                            title="Follow us on Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://github.com/airtoshare" target="_blank" rel="noopener noreferrer"
                            title="View on GitHub">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="https://www.linkedin.com/company/airtoshare" target="_blank"
                            rel="noopener noreferrer" title="Connect on LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <style>
        .footer-links {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.875rem;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: var(--primary-color);
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            justify-content: center;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--bg-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: var(--transition);
            font-size: 1.1rem;
        }

        .social-links a:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        @media (max-width: 768px) {
            .footer-links {
                justify-content: center;
                margin-top: 1rem;
            }

            .social-links {
                justify-content: center;
                margin-top: 1.5rem;
            }
        }
    </style>

    <!-- PWA Install Banner -->
    <div class="pwa-install-banner" id="pwaInstallBanner">
        <div class="pwa-install-content">
            <i class="fas fa-mobile-alt"></i>
            <div class="pwa-install-text">
                <strong>Install AirToShare</strong>
                <span>Add to home screen for quick access</span>
            </div>
        </div>
        <div class="pwa-install-actions">
            <button id="pwaInstallBtn" class="modern-btn">Install</button>
            <button id="pwaDismissBtn" class="pwa-dismiss-btn">&times;</button>
        </div>
    </div>

    <script>
        // Dark Mode toggle is implemented in /assets/js/theme-manager.js
        // (loaded with `defer` below). The pre-paint bootstrap in <head>
        // already resolved the active theme using the same snake-case
        // localStorage key (`airtoshare_theme`) and the `data-theme`
        // attribute on <html>, so nothing needs to run inline here.

        // PWA Install Prompt
        let deferredPrompt;
        const pwaInstallBanner = document.getElementById('pwaInstallBanner');
        const pwaInstallBtn = document.getElementById('pwaInstallBtn');
        const pwaDismissBtn = document.getElementById('pwaDismissBtn');

        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('beforeinstallprompt Event fired');
            e.preventDefault();
            deferredPrompt = e;

            if (!localStorage.getItem('pwaInstallDismissed')) {
                pwaInstallBanner.classList.add('show');
            } else {
                console.log('PWA banner suppressed: previously dismissed by user');
            }
        });

        if (pwaInstallBtn) {
            pwaInstallBtn.addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();

                    const {
                        outcome
                    } = await deferredPrompt.userChoice;
                    console.log('User choice outcome:', outcome);

                    if (outcome === 'accepted') {
                        console.log('User added to home screen');
                        showToast('success', 'Installed!', 'AirToShare added to your home screen');
                    } else {
                        console.log('User cancelled home screen install');
                    }

                    deferredPrompt = null;
                    pwaInstallBanner.classList.remove('show');
                }
            });
        }

        if (pwaDismissBtn) {
            pwaDismissBtn.addEventListener('click', () => {
                localStorage.setItem('pwaInstallDismissed', 'true');
                pwaInstallBanner.classList.remove('show');
            });
        }

        // Register Service Worker for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').then(registration => {
                    console.log('ServiceWorker registration successful');
                }, err => {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>
</body>

</html>
