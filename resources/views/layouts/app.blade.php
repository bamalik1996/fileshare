<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'AirForShare - Instant File Sharing Across Devices')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="@yield('description', 'Share files and text instantly across devices on the same network. Simple, fast, and secure file sharing without accounts or external servers.')">
    <meta name="keywords" content="@yield('keywords', 'file sharing, instant sharing, local network, secure sharing, cross-device, no account required')">
    <meta name="author" content="AirForShare">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="@yield('og_title', 'AirForShare - Instant File Sharing')">
    <meta property="og:description" content="@yield('og_description', 'Share files and text instantly across devices on the same network. Simple, fast, and secure.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="AirForShare">
    <meta property="og:locale" content="en_US">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter_title', 'AirForShare - Instant File Sharing')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Share files and text instantly across devices on the same network.')">
    
    <!-- Schema.org JSON-LD -->
    @yield('schema')

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bulma CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/bulma.min.css') }}">
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    
    <style>
        :root {
            --primary-color: #667eea;
            --primary-dark: #5a67d8;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --success-color: #48bb78;
            --warning-color: #ed8936;
            --error-color: #f56565;
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --bg-primary: #ffffff;
            --bg-secondary: #f7fafc;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        /* Modern Navbar */
        .modern-navbar {
            background: var(--bg-primary);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand .navbar-item {
            font-weight: 700;
            font-size: 1.5rem;
            background: var(--bg-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .navbar-item {
            color: var(--text-primary) !important;
            font-weight: 500;
            transition: var(--transition);
            border-radius: 8px;
            margin: 0 4px;
        }

        .navbar-item:hover {
            background: rgba(102, 126, 234, 0.1) !important;
            color: var(--primary-color) !important;
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
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            background: var(--bg-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--text-secondary);
            font-weight: 400;
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        /* Modern Cards */
        .modern-card {
            background: var(--bg-primary);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition);
            overflow: hidden;
        }

        .modern-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        /* Info Panel */
        .info-panel {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
        }

        .info-item {
            display: inline-flex;
            align-items: center;
            margin-right: 2rem;
            font-weight: 500;
        }

        .info-item i {
            margin-right: 0.5rem;
            color: var(--primary-color);
        }

        /* Modern Tabs */
        .modern-tabs {
            background: var(--bg-primary);
            border-radius: var(--border-radius);
            padding: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            display: flex;
            gap: 0.5rem;
        }

        .modern-tab {
            flex: 1;
            padding: 1rem 2rem;
            border-radius: 8px;
            background: transparent;
            border: none;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .modern-tab.active {
            background: var(--bg-gradient);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .modern-tab:hover:not(.active) {
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary-color);
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
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Modern Buttons */
        .modern-btn {
            background: var(--bg-gradient);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            box-shadow: var(--shadow-sm);
        }

        .modern-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .modern-btn:active {
            transform: translateY(0);
        }

        .modern-btn.secondary {
            background: var(--bg-primary);
            color: var(--text-primary);
            border: 2px solid var(--primary-color);
        }

        .modern-btn.danger {
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
        }

        .modern-btn.success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        }

        .modern-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Footer */
        .modern-footer {
            background: var(--bg-primary);
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            padding: 2rem 0;
            text-align: center;
            color: var(--text-secondary);
            margin-top: auto;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
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
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Utility Classes */
        .glass-effect {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .gradient-text {
            background: var(--bg-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
    </script>
</head>

<body>
    <!-- Modern Navbar -->
    <nav class="navbar modern-navbar" role="navigation" aria-label="main navigation">
        <div class="container">
            <div class="navbar-brand">
                <a class="navbar-item" href="{{ url('/') }}">
                    <i class="fas fa-cloud-upload-alt" style="margin-right: 0.5rem;"></i>
                    <strong>AirForShare</strong>
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
            <p>
                <strong class="gradient-text">AirForShare</strong> &copy; {{ date('Y') }} - 
                Instant file sharing made simple and secure
            </p>
        </div>
    </footer>
</body>

</html>