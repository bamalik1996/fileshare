<!DOCTYPE html>
<html lang="en-us">

<head>


    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'AirForShare - Instant File Sharing Across Devices')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- SEO Meta Tags -->
    <meta name="description" content="@yield('description', 'AirToShare - Share files and text instantly across devices on the same network. Simple, fast, and secure file sharing without accounts or external servers.')">
    <meta name="keywords" content="@yield('keywords', 'file sharing, instant sharing, local network, secure sharing, cross-device, no account required')">
    <meta name="author" content="AirToShare">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

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
    <link rel="manifest" href="/site.webmanifest">
    <!-- Sitemap Reference -->
    <link rel="sitemap" type="application/xml" title="Sitemap" href="{{ url('/sitemap.xml') }}">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="@yield('og_title', 'AirToShare - Instant File Sharing')">
    <meta property="og:description" content="@yield('og_description', 'Share files and text instantly across devices on the same network. Simple, fast, and secure.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="AirToShare">
    <meta property="og:locale" content="en_US">
    <meta property="og:image" content="{{ url('/logo.svg') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="AirToShare - Instant File Sharing">
    <meta property="fb:app_id" content="YOUR_FACEBOOK_APP_ID">
    <meta property="fb:pages" content="airtoshare">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter_title', 'AirToShare - Instant File Sharing')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Share files and text instantly across devices on the same network.')">
    <meta name="twitter:image" content="{{ url('/logo.svg') }}">
    <meta name="twitter:site" content="@AirToShare">
    <meta name="twitter:creator" content="@AirToShare">
    <link href="/assets/css/custom.css" rel="stylesheet" />

    <!-- Schema.org JSON-LD -->
    @yield('schema')

    <!-- Additional Schema for Organization -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "Organization",
      "name": "AirToShare",
      "url": "{{ url('/') }}",
      "logo": "{{ url('/favicon.ico') }}",
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
    @if (request()->path() !== '/')
        <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "BreadcrumbList",
      "itemListElement": [
        {
          "@@type": "ListItem",
          "position": 1,
          "name": "Home",
          "item": "{{ url('/') }}"
        },
        {
          "@@type": "ListItem",
          "position": 2,
          "name": "@yield('title', 'Page')",
          "item": "{{ url()->current() }}"
        }
      ]
    }
    </script>
    @endif



    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('assets/font-awesome/css/all.min.css') }}">
    <!-- Bulma CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/bulma.min.css') }}">
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>

    

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const burger = document.querySelector('.navbar-burger');
            const menu = document.querySelector('.navbar-menu');

            if (burger && menu) {
                burger.addEventListener('click', () => {
                    menu.classList.toggle('is-active');
                    burger.classList.toggle('is-active');
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
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());

          gtag('config', 'G-0228GR7HD3');
        </script>

        <!-- Facebook Pixel (Add your pixel ID) -->
        <script>
            ! function(f, b, e, v, n, t, s) {
                if (f.fbq) return;
                n = f.fbq = function() {
                    n.callMethod ?
                        n.callMethod.apply(n, arguments) : n.queue.push(arguments)
                };
                if (!f._fbq) f._fbq = n;
                n.push = n;
                n.loaded = !0;
                n.version = '2.0';
                n.queue = [];
                t = b.createElement(e);
                t.async = !0;
                t.src = v;
                s = b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t, s)
            }(window, document, 'script',
                'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', 'FACEBOOK_PIXEL_ID');
            fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
                src="https://www.facebook.com/tr?id=FACEBOOK_PIXEL_ID&ev=PageView&noscript=1" /></noscript>
    @endif
</head>

<body>
    <!-- Toast Container -->
    <div class="toast-container"></div>

    <!-- Modern Navbar -->
    <nav class="navbar modern-navbar" role="navigation" aria-label="main navigation">
        <div class="container">
            <div class="navbar-brand">
                <a class="navbar-item" href="{{ url('/') }}" style="
    width: 125px;
    height: 50px;
">
                    <img src="/logo.svg" alt="Air to share logo" />
                </a>

                <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false">
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                </a>
            </div>

            <div class="navbar-menu">
                <div class="navbar-start">
                    <a class="navbar-item" href="{{ url('/') }}">
                        <i class="fas fa-home" style="margin-right: 0.5rem;"></i>
                        Home
                    </a>
                    <a class="navbar-item" href="{{ url('/how-it-works') }}">
                        <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>
                        How It Works
                    </a>
                    <a class="navbar-item" href="{{ url('/faq') }}">
                        <i class="fas fa-question-circle" style="margin-right: 0.5rem;"></i>
                        FAQ
                    </a>
                    <a class="navbar-item" href="{{ url('/feedback') }}">
                        <i class="fas fa-comment" style="margin-right: 0.5rem;"></i>
                        Feedback
                    </a>
                    <a class="navbar-item" href="{{ url('/coming-soon') }}">
                        <i class="fas fa-rocket" style="margin-right: 0.5rem;"></i>
                        Coming Soon
                    </a>
                </div>

                <div class="navbar-end">
                    <div class="navbar-item">
                        <div class="buttons">
                            <a class="button is-primary is-small" href="{{ url('/') }}"
                                style="background: var(--bg-gradient); border: none; border-radius: var(--border-radius-sm); font-weight: 600;">
                                <strong>Start Sharing</strong>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="modern-container">
        @yield('content')
    </div>

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
</body>

</html>
