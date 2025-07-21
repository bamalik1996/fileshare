<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'AirToShare - Instant File Sharing Across Devices')</title>
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
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon.ico">

    <!-- Sitemap Reference -->
    <link rel="sitemap" type="application/xml" title="Sitemap" href="{{ url('/sitemap.xml') }}">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="@yield('og_title', 'AirToShare - Instant File Sharing')">
    <meta property="og:description" content="@yield('og_description', 'Share files and text instantly across devices on the same network. Simple, fast, and secure.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="AirToShare">
    <meta property="og:locale" content="en_US">
    <meta property="og:image" content="{{ url('/favicon.ico') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="AirToShare - Instant File Sharing">
    <meta property="fb:app_id" content="YOUR_FACEBOOK_APP_ID">
    <meta property="fb:pages" content="airtoshare">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter_title', 'AirToShare - Instant File Sharing')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Share files and text instantly across devices on the same network.')">
    <meta name="twitter:image" content="{{ url('/favicon.ico') }}">
    <meta name="twitter:site" content="@AirToShare">
    <meta name="twitter:creator" content="@AirToShare">

    <!-- Schema.org JSON-LD -->
    @yield('schema')

    <!-- Additional Schema for Organization -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
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
        "@type": "ContactPoint",
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
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "name": "Home",
          "item": "{{ url('/') }}"
        },
        {
          "@type": "ListItem",
          "position": 2,
          "name": "@yield('title', 'Page')",
          "item": "{{ url()->current() }}"
        }
      ]
    }
    </script>
    @endif

    <!-- Google Analytics (Add your tracking ID) -->
    @if (config('app.env') === 'production')
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=GA_TRACKING_ID"></script>
        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }
            gtag('js', new Date());
            gtag('config', 'GA_TRACKING_ID', {
                anonymize_ip: true,
                cookie_flags: 'SameSite=None;Secure'
            });
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

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('assets/font-awesome/css/all.min.css') }}">
    <!-- Bulma CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/bulma.min.css') }}">
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>

    <style>
        :root {
            /* Professional Blue & Teal Color Scheme */
            --primary-color: #0ea5e9;
            --primary-dark: #0284c7;
            --primary-light: #38bdf8;
            --secondary-color: #06b6d4;
            --secondary-dark: #0891b2;
            --accent-color: #8b5cf6;
            --accent-light: #a78bfa;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-light: #94a3b8;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --bg-gradient: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 50%, #8b5cf6 100%);
            --bg-gradient-light: linear-gradient(135deg, rgba(14, 165, 233, 0.1) 0%, rgba(6, 182, 212, 0.1) 50%, rgba(139, 92, 246, 0.1) 100%);
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-colored: 0 10px 15px -3px rgba(14, 165, 233, 0.2), 0 4px 6px -2px rgba(6, 182, 212, 0.1);
            --border-radius: 16px;
            --border-radius-sm: 8px;
            --border-radius-lg: 24px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.6;
            margin: 0;
            padding: 0;
            font-feature-settings: 'cv02', 'cv03', 'cv04', 'cv11';
        }

        /* Modern Navbar */
        .modern-navbar {
            background: var(--bg-primary);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(14, 165, 233, 0.1);
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand .navbar-item {
            font-weight: 700;
            font-size: 1.75rem;
            background: var(--bg-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.025em;
        }

        .navbar-item {
            color: var(--text-primary) !important;
            font-weight: 600;
            transition: var(--transition-fast);
            border-radius: var(--border-radius-sm);
            margin: 0 4px;
            position: relative;
        }

        .navbar-item:hover {
            background: var(--bg-gradient-light) !important;
            color: var(--primary-color) !important;
            transform: translateY(-1px);
        }

        /* Modern Container */
        .modern-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
            min-height: calc(100vh - 140px);
        }

        /* Hero Section */
        .hero-section {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem 0;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            background: var(--bg-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            line-height: 1.2;
            letter-spacing: -0.025em;
        }

        .hero-subtitle {
            font-size: 1.375rem;
            color: var(--text-secondary);
            font-weight: 400;
            max-width: 600px;
            margin: 0 auto 2rem;
            line-height: 1.7;
        }

        /* Modern Cards */
        .modern-card {
            background: var(--bg-primary);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(14, 165, 233, 0.1);
            transition: var(--transition);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .modern-card:hover {
            box-shadow: var(--shadow-colored);
            transform: translateY(-4px);
            border-color: var(--primary-color);
        }

        /* Info Panel */
        .info-panel {
            background: var(--bg-gradient-light);
            border: 1px solid rgba(14, 165, 233, 0.2);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-sm);
        }

        .info-item {
            display: inline-flex;
            align-items: center;
            margin-right: 2rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .info-item i {
            margin-right: 0.5rem;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        /* Modern Tabs */
        .modern-tabs {
            background: var(--bg-primary);
            border-radius: var(--border-radius);
            padding: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
            display: flex;
            gap: 0.5rem;
            border: 1px solid rgba(14, 165, 233, 0.1);
        }

        .modern-tab {
            flex: 1;
            padding: 1rem 2rem;
            border-radius: var(--border-radius-sm);
            background: transparent;
            border: none;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition-fast);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            position: relative;
        }

        .modern-tab.active {
            background: var(--bg-gradient);
            color: white;
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }

        .modern-tab:hover:not(.active) {
            background: var(--bg-gradient-light);
            color: var(--primary-color);
            transform: translateY(-1px);
        }

        /* Tab Content */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Modern Buttons */
        .modern-btn {
            background: var(--bg-gradient);
            color: white;
            border: none;
            border-radius: var(--border-radius-sm);
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .modern-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-colored);
        }

        .modern-btn:active {
            transform: translateY(0);
        }

        .modern-btn.secondary {
            background: var(--bg-primary);
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            box-shadow: var(--shadow-sm);
        }

        .modern-btn.secondary:hover {
            background: var(--primary-color);
            color: white;
        }

        .modern-btn.danger {
            background: linear-gradient(135deg, var(--error-color) 0%, #dc2626 100%);
        }

        .modern-btn.success {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
        }

        .modern-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Footer */
        .modern-footer {
            background: var(--bg-primary);
            border-top: 1px solid rgba(14, 165, 233, 0.1);
            padding: 2rem 0;
            text-align: center;
            color: var(--text-secondary);
            margin-top: auto;
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.125rem;
            }

            .modern-container {
                padding: 1rem;
            }

            .info-item {
                display: block;
                margin-bottom: 0.5rem;
                margin-right: 0;
            }

            .modern-tabs {
                flex-direction: column;
            }
        }

        /* Loading Animation */
        .loading-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-top: 2px solid var(--primary-light);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Utility Classes */
        .glass-effect {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(14, 165, 233, 0.2);
        }

        .gradient-text {
            background: var(--bg-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            pointer-events: none;
        }

        .toast {
            background: var(--bg-primary);
            border: 1px solid rgba(14, 165, 233, 0.2);
            border-radius: var(--border-radius);
            padding: 1rem 1.5rem;
            margin-bottom: 0.5rem;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 300px;
            transform: translateX(400px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: auto;
            backdrop-filter: blur(10px);
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast.success {
            border-left: 4px solid var(--success-color);
            background: rgba(16, 185, 129, 0.05);
        }

        .toast.error {
            border-left: 4px solid var(--error-color);
            background: rgba(239, 68, 68, 0.05);
        }

        .toast.info {
            border-left: 4px solid var(--primary-color);
            background: var(--bg-gradient-light);
        }

        .toast-icon {
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .toast.success .toast-icon {
            color: var(--success-color);
        }

        .toast.error .toast-icon {
            color: var(--error-color);
        }

        .toast.info .toast-icon {
            color: var(--primary-color);
        }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .toast-message {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .toast-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            transition: var(--transition);
        }

        .toast-close:hover {
            background: rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .toast-container {
                top: 10px;
                right: 10px;
                left: 10px;
            }

            .toast {
                min-width: auto;
                width: 100%;
            }
        }
    </style>

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
</head>

<body>
    <!-- Toast Container -->
    <div class="toast-container"></div>

    <!-- Modern Navbar -->
    <nav class="navbar modern-navbar" role="navigation" aria-label="main navigation">
        <div class="container">
            <div class="navbar-brand">
                <a class="navbar-item" href="{{ url('/') }}">
                    <i class="fas fa-paper-plane" style="margin-right: 0.5rem; color: var(--primary-color);"></i>
                    <strong>AirToShare</strong>
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
