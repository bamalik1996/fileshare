@extends('layouts.app')

@section('title', 'Coming Soon - Exciting New Features | AirToShare')
@section('description',
    'Exciting new features are coming to AirToShare! Dark mode, clipboard sync, real-time
    collaboration, and more advanced file sharing capabilities.')
@section('keywords',
    'AirToShare updates, new features, dark mode, clipboard sync, file sharing improvements, coming
    soon')

@section('schema')
    <script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "WebPage",
  "name": "Coming Soon - New Features",
  "description": "Exciting new features coming to AirToShare file sharing platform",
  "url": "{{ url('/coming-soon') }}",
  "mainEntity": {
    "@@type": "SoftwareApplication",
    "name": "AirToShare",
    "applicationCategory": "UtilitiesApplication",
    "operatingSystem": "Web Browser",
    "offers": {
      "@@type": "Offer",
      "price": "0",
      "priceCurrency": "USD"
    }
  }
}
</script>
@endsection

@section('content')

    <div class="coming-soon-hero">
        <h1 class="coming-soon-title">
            <i class="fas fa-rocket"></i>
            Exciting Features Coming Soon!
        </h1>
        <p class="coming-soon-subtitle">
            We're working hard to bring you amazing new features that will make AirToShare even more powerful,
            intuitive, and enjoyable. Get ready for the next level of AirToShare!
        </p>
        <div class="launch-badge">
            <i class="fas fa-clock"></i>
            Launching Very Soon
        </div>
    </div>

    <div class="modern-card">
        <div class="features-preview">
            <h2 class="section-title">
                <i class="fas fa-star"></i>
                What's Coming Next
            </h2>

            <div class="features-grid">
                <!-- File Organization -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-folder-tree"></i>
                    </div>
                    <h3 class="feature-title">Smart File Organization</h3>
                    <p class="feature-description">
                        Automatic file categorization, search functionality, and custom folders.
                        Keep your shared files organized and easily discoverable.
                    </p>
                    <div class="feature-status">
                        <div class="status-dot"></div>
                        Planning Phase
                    </div>
                </div>

                <!-- Password Protected Files -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="feature-title">Password Protected Files</h3>
                    <p class="feature-description">
                        Add an extra layer of security by setting passwords for sensitive files.
                        Only users with the correct password can download protected files.
                    </p>
                    <div class="feature-status">
                        <div class="status-dot"></div>
                        Coming Soon
                    </div>
                </div>

                <!-- File Drag Reordering -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-arrows-alt"></i>
                    </div>
                    <h3 class="feature-title">Drag & Drop Reordering</h3>
                    <p class="feature-description">
                        Reorder your uploaded files by simply dragging and dropping them.
                        Organize your files exactly how you want them displayed.
                    </p>
                    <div class="feature-status">
                        <div class="status-dot"></div>
                        In Development
                    </div>
                </div>

                <!-- Multi-User Rooms -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Multi-User Sharing Rooms</h3>
                    <p class="feature-description">
                        Create private sharing rooms with unique links. Multiple users can upload
                        and download files in the same room. Perfect for team collaboration!
                    </p>
                    <div class="feature-status">
                        <div class="status-dot"></div>
                        Planning Phase
                    </div>
                </div>

                <!-- File Expiry Customization -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="feature-title">Custom Expiry Times</h3>
                    <p class="feature-description">
                        Choose how long your files stay available - from 1 hour to 7 days.
                        Set different expiry times for different files based on your needs.
                    </p>
                    <div class="feature-status">
                        <div class="status-dot"></div>
                        Coming Soon
                    </div>
                </div>

                <!-- Download Limits -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <h3 class="feature-title">Download Limits</h3>
                    <p class="feature-description">
                        Set a maximum number of downloads for your shared files.
                        Once the limit is reached, the file automatically becomes unavailable.
                    </p>
                    <div class="feature-status">
                        <div class="status-dot"></div>
                        In Development
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="timeline-section">
        <h2 class="section-title">
            <i class="fas fa-calendar-alt"></i>
            Development Roadmap
        </h2>

        <div class="timeline">
            <div class="timeline-item completed">
                <div class="timeline-content">
                    <div class="timeline-date">✅ Completed</div>
                    <div class="timeline-title">Dark Mode, QR Code & Real-time Sync</div>
                    <div class="timeline-desc">
                        Dark mode toggle, QR code sharing, clipboard sync, device nicknames, one-time downloads, and live
                        notifications - all shipped!
                    </div>
                </div>
                <div class="timeline-dot"></div>
            </div>

            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-date">Q1 2025</div>
                    <div class="timeline-title">Security & Access Control</div>
                    <div class="timeline-desc">
                        Password protected files, download limits, and custom expiry times for maximum control.
                    </div>
                </div>
                <div class="timeline-dot"></div>
            </div>

            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-date">Q2 2025</div>
                    <div class="timeline-title">File Management & UX</div>
                    <div class="timeline-desc">
                        Drag & drop reordering, smart file organization with categories, and improved search functionality.
                    </div>
                </div>
                <div class="timeline-dot"></div>
            </div>

            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-date">Q3 2025</div>
                    <div class="timeline-title">Multi-User Collaboration</div>
                    <div class="timeline-desc">
                        Private sharing rooms with unique links, team collaboration features, and real-time presence
                        indicators.
                    </div>
                </div>
                <div class="timeline-dot"></div>
            </div>
        </div>
    </div>

    <div class="social-proof">
        <h2 class="section-title">
            <i class="fas fa-chart-line"></i>
            Growing Every Day
        </h2>

        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number">10K+</div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">50K+</div>
                <div class="stat-label">Files Shared</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">99.9%</div>
                <div class="stat-label">Uptime</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">24/7</div>
                <div class="stat-label">Support</div>
            </div>
        </div>
    </div>

    <div class="newsletter-section">
        <h2 class="newsletter-title">
            <i class="fas fa-bell"></i>
            Stay Updated
        </h2>
        <p class="newsletter-text">
            Be the first to know when new AirToShare features launch! Get exclusive early access and updates delivered to
            your inbox.
        </p>
        <form class="newsletter-form" id="newsletterForm">
            <input type="email" class="newsletter-input" placeholder="Enter your email address" required>
            <button type="submit" class="newsletter-btn">
                <i class="fas fa-paper-plane"></i>
                Notify Me
            </button>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            setupNewsletterForm();
        });

        function setupNewsletterForm() {
            $('#newsletterForm').submit(function(e) {
                e.preventDefault();
                const email = $(this).find('input[type="email"]').val();

                // Simulate newsletter signup
                showToast('success', 'Subscribed!', `We'll notify you at ${email} when new features launch!`);
                $(this)[0].reset();
            });
        }
    </script>
@endsection
