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
    <div class="error-container">
        <div class="error-content">
            <!-- Floating Icons -->
            <div class="error-illustration">
                <div class="floating-icon primary">
                    <i class="fas fa-file"></i>
                </div>
                <div class="floating-icon primary">
                    <i class="fas fa-cloud"></i>
                </div>
                <div class="floating-icon primary">
                    <i class="fas fa-share-alt"></i>
                </div>
                <div class="floating-icon primary">
                    <i class="fas fa-wifi"></i>
                </div>
            </div>

            <!-- Error Number -->
            <div class="error-number error-404">404</div>

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
