@extends('layouts.app')

@section('title', 'Coming Soon - Exciting New Features | AirToShare')
@section('description', 'Exciting new features are coming to AirToShare! Dark mode, clipboard sync, real-time collaboration, and more advanced file sharing capabilities.')
@section('keywords', 'AirToShare updates, new features, dark mode, clipboard sync, file sharing improvements, coming soon')

@section('schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Coming Soon - New Features",
  "description": "Exciting new features coming to AirToShare file sharing platform",
  "url": "{{ url('/coming-soon') }}",
  "mainEntity": {
    "@type": "SoftwareApplication",
    "name": "AirToShare",
    "applicationCategory": "UtilitiesApplication",
    "operatingSystem": "Web Browser",
    "offers": {
      "@type": "Offer",
      "price": "0",
      "priceCurrency": "USD"
    }
  }
}
</script>
@endsection

@section('content')
    <style>
        .coming-soon-hero {
            text-align: center;
            margin-bottom: 4rem;
            padding: 4rem 2rem;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: var(--border-radius);
            position: relative;
            overflow: hidden;
        }

        .coming-soon-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .coming-soon-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: var(--bg-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            z-index: 2;
        }

        .coming-soon-subtitle {
            font-size: 1.5rem;
            color: var(--text-secondary);
            max-width: 700px;
            margin: 0 auto 2rem;
            line-height: 1.6;
            position: relative;
            z-index: 2;
        }

        .launch-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--bg-gradient);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 2;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .features-preview {
            margin: 4rem 0;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 3rem;
            color: var(--text-primary);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }

        .feature-card {
            background: var(--bg-primary);
            border-radius: var(--border-radius);
            padding: 2.5rem;
            box-shadow: var(--shadow-md);
            text-align: center;
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
                <!-- Dark Mode -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-moon"></i>
                    </div>
                    <h3 class="feature-title">Dark Mode</h3>
                    <p class="feature-description">
                        Toggle between light and dark themes for a comfortable viewing experience in any lighting condition.
                        Your eyes will thank you during those late-night file sharing sessions!
                    </p>
                    <div class="feature-status">
                        <div class="status-dot"></div>
                        In Development
                    </div>
                </div>

                <!-- Clipboard Sync -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <h3 class="feature-title">Clipboard Sync</h3>
                    <p class="feature-description">
                        Automatically sync clipboard content between devices on the same network.
                        Copy on one device, paste on another - seamless productivity across all your devices!
                    </p>
                    <div class="feature-status">
                        <div class="status-dot"></div>
                        Coming Soon
                    </div>
                </div>

                <!-- Real-time Collaboration -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Real-time Collaboration</h3>
                    <p class="feature-description">
                        See who's online and collaborate in real-time. Share files instantly with multiple users
                        and get live notifications when someone joins or shares content.
                    </p>
                    <div class="feature-status">
                        <div class="status-dot"></div>
                        Planning Phase
                    </div>
                </div>

                <!-- Advanced File Preview -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3 class="feature-title">Advanced File Preview</h3>
                    <p class="feature-description">
                        Preview more file types including videos, audio files, and documents without downloading.
                        Built-in media player and document viewer for instant access.
                    </p>
                    <div class="feature-status">
                        <div class="status-dot"></div>
                        In Development
                    </div>
                </div>

                <!-- QR Code Sharing -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <h3 class="feature-title">QR Code Sharing</h3>
                    <p class="feature-description">
                        Generate QR codes for instant access to your shared content.
                        Perfect for quickly connecting new devices or sharing with guests.
                    </p>
                    <div class="feature-status">
                        <div class="status-dot"></div>
                        Coming Soon
                    </div>
                </div>

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
            </div>
        </div>
    </div>

    <div class="timeline-section">
        <h2 class="section-title">
            <i class="fas fa-calendar-alt"></i>
            Development Roadmap
        </h2>

        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-date">Q1 2025</div>
                    <div class="timeline-title">Dark Mode & UI Enhancements</div>
                    <div class="timeline-desc">
                        Complete dark mode implementation with smooth transitions and improved accessibility features.
                    </div>
                </div>
                <div class="timeline-dot"></div>
            </div>

            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-date">Q2 2025</div>
                    <div class="timeline-title">Clipboard Sync & QR Codes</div>
                    <div class="timeline-desc">
                        Real-time clipboard synchronization and QR code generation for instant device connections.
                    </div>
                </div>
                <div class="timeline-dot"></div>
            </div>

            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-date">Q3 2025</div>
                    <div class="timeline-title">Advanced Previews & Collaboration</div>
                    <div class="timeline-desc">
                        Enhanced file preview capabilities and real-time collaboration features for team productivity.
                    </div>
                </div>
                <div class="timeline-dot"></div>
            </div>

            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-date">Q4 2025</div>
                    <div class="timeline-title">Smart Organization & AI Features</div>
                    <div class="timeline-desc">
                        AI-powered file organization, smart search, and intelligent content recommendations.
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
            Be the first to know when new AirToShare features launch! Get exclusive early access and updates delivered to your inbox.
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
