@extends('layouts.app')

@section('title', '404 - Page Not Found | AirToShare')
@section('description', 'The page you are looking for could not be found. Return to AirToShare homepage to continue sharing files instantly across your devices.')
@section('keywords', 'AirToShare 404, page not found, file sharing, error page')

@section('schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "404 - Page Not Found",
  "description": "Page not found error on AirToShare file sharing platform",
  "url": "{{ url()->current() }}",
  "mainEntity": {
    "@type": "SoftwareApplication",
    "name": "AirToShare",
    "applicationCategory": "UtilitiesApplication",
    "operatingSystem": "Web Browser"
  }
}
</script>
@endsection

@section('content')
    <style>
        .error-container {
            min-height: 70vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }

        .error-content {
            max-width: 600px;
            margin: 0 auto;
        }

        .error-number {
            font-size: 8rem;
            font-weight: 700;
            background: var(--bg-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 1rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }

        .error-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .error-subtitle {
            font-size: 1.25rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .error-illustration {
            margin: 2rem 0;
            position: relative;
        }

        .floating-icon {
            font-size: 3rem;
            color: var(--primary-color);
            opacity: 0.3;
            position: absolute;
            animation: float 3s ease-in-out infinite;
        }

        .floating-icon:nth-child(1) {
            top: 20px;
            left: 20%;
            animation-delay: 0s;
        }

        .floating-icon:nth-child(2) {
            top: 60px;
            right: 20%;
            animation-delay: 1s;
        }

        .floating-icon:nth-child(3) {
            bottom: 40px;
            left: 30%;
            animation-delay: 2s;
        }

        .floating-icon:nth-child(4) {
            bottom: 20px;
            right: 30%;
            animation-delay: 1.5s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .error-btn {
            background: var(--bg-gradient);
            color: white;
            border: none;
            border-radius: var(--border-radius-sm);
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            box-shadow: var(--shadow-md);
        }

        .error-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-colored);
            color: white;
        }

        .error-btn.secondary {
            background: var(--bg-primary);
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            box-shadow: var(--shadow-sm);
        }

        .error-btn.secondary:hover {
            background: var(--primary-color);
            color: white;
        }

        .helpful-links {
            margin-top: 3rem;
            padding: 2rem;
            background: var(--bg-gradient-light);
            border-radius: var(--border-radius);
            border: 1px solid rgba(14, 165, 233, 0.2);
        }

        .helpful-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .link-item {
            background: var(--bg-primary);
            border-radius: var(--border-radius-sm);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            border: 2px solid transparent;
            text-decoration: none;
            color: var(--text-primary);
        }

        .link-item:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
            color: var(--text-primary);
        }

        .link-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .link-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .link-desc {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .search-box {
            margin: 2rem 0;
            position: relative;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid rgba(14, 165, 233, 0.2);
            border-radius: 50px;
            font-size: 1rem;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: var(--transition);
            outline: none;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .error-number {
                font-size: 5rem;
            }

            .error-title {
                font-size: 2rem;
            }

            .error-subtitle {
                font-size: 1.125rem;
            }

            .error-actions {
                flex-direction: column;
                align-items: center;
            }

            .error-btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }

            .floating-icon {
                display: none;
            }
        }
    </style>

    <div class="error-container">
        <div class="error-content">
            <!-- Floating Icons -->
            <div class="error-illustration">
                <div class="floating-icon">
                    <i class="fas fa-file"></i>
                </div>
                <div class="floating-icon">
                    <i class="fas fa-cloud"></i>
                </div>
                <div class="floating-icon">
                    <i class="fas fa-share-alt"></i>
                </div>
                <div class="floating-icon">
                    <i class="fas fa-wifi"></i>
                </div>
            </div>

            <!-- Error Number -->
            <div class="error-number">404</div>

            <!-- Error Message -->
            <h1 class="error-title">
                <i class="fas fa-search" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                Page Not Found
            </h1>
            
            <p class="error-subtitle">
                Oops! The page you're looking for seems to have taken flight. 
                Don't worry, your files are still safe and ready to share!
            </p>

            <!-- Search Box -->
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input 
                    type="text" 
                    class="search-input" 
                    placeholder="Search for what you need..."
                    id="searchInput"
                >
            </div>

            <!-- Action Buttons -->
            <div class="error-actions">
                <a href="{{ url('/') }}" class="error-btn">
                    <i class="fas fa-home"></i>
                    Back to Home
                </a>
                <a href="{{ url('/how-it-works') }}" class="error-btn secondary">
                    <i class="fas fa-info-circle"></i>
                    How It Works
                </a>
            </div>
        </div>
    </div>

    <!-- Helpful Links Section -->
    <div class="modern-card">
        <div class="helpful-links">
            <h2 class="helpful-title">
                <i class="fas fa-compass"></i>
                Find What You're Looking For
            </h2>
            
            <div class="links-grid">
                <a href="{{ url('/') }}" class="link-item">
                    <div class="link-icon">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div class="link-title">Start Sharing</div>
                    <div class="link-desc">Upload and share files instantly</div>
                </a>

                <a href="{{ url('/how-it-works') }}" class="link-item">
                    <div class="link-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="link-title">How It Works</div>
                    <div class="link-desc">Learn about AirToShare features</div>
                </a>

                <a href="{{ url('/faq') }}" class="link-item">
                    <div class="link-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="link-title">FAQ</div>
                    <div class="link-desc">Find answers to common questions</div>
                </a>

                <a href="{{ url('/feedback') }}" class="link-item">
                    <div class="link-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="link-title">Get Help</div>
                    <div class="link-desc">Contact our support team</div>
                </a>

                <a href="{{ url('/coming-soon') }}" class="link-item">
                    <div class="link-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <div class="link-title">Coming Soon</div>
                    <div class="link-desc">Exciting new features ahead</div>
                </a>

                <a href="https://web.facebook.com/airtoshare/" target="_blank" class="link-item">
                    <div class="link-icon">
                        <i class="fab fa-facebook-f"></i>
                    </div>
                    <div class="link-title">Follow Us</div>
                    <div class="link-desc">Stay updated on Facebook</div>
                </a>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            setupSearch();
        });

        function setupSearch() {
            $('#searchInput').on('keypress', function(e) {
                if (e.which === 13) { // Enter key
                    const query = $(this).val().trim().toLowerCase();
                    
                    // Simple search logic
                    if (query.includes('share') || query.includes('upload') || query.includes('file')) {
                        window.location.href = '{{ url("/") }}';
                    } else if (query.includes('how') || query.includes('work') || query.includes('guide')) {
                        window.location.href = '{{ url("/how-it-works") }}';
                    } else if (query.includes('faq') || query.includes('question') || query.includes('help')) {
                        window.location.href = '{{ url("/faq") }}';
                    } else if (query.includes('contact') || query.includes('support') || query.includes('feedback')) {
                        window.location.href = '{{ url("/feedback") }}';
                    } else if (query.includes('new') || query.includes('feature') || query.includes('coming')) {
                        window.location.href = '{{ url("/coming-soon") }}';
                    } else if (query) {
                        // Default to home page for any other search
                        window.location.href = '{{ url("/") }}';
                    }
                }
            });
        }
    </script>
@endsection