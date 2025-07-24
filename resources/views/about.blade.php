@extends('layouts.app')

@section('title', 'About AirToShare')

@section('content')

    <div class="about-hero">
        <h1 class="about-title">
            <i class="fas fa-info-circle"></i>
            How AirToShare Works
        </h1>
        <p class="about-subtitle">
            Simple, secure, and instant file sharing across devices on your local network.
            No accounts, no uploads to external servers, just pure peer-to-peer sharing.
        </p>
    </div>

    <div class="modern-card">
        <div style="padding: 2rem;">
            <div class="steps-grid">
                <!-- Step 1 -->
                <div class="step-card">
                    <div class="step-icon">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <h3 class="step-title">1. Connect to Same Wi-Fi</h3>
                    <p class="step-description">
                        Ensure all devices you want to share files with are connected to the same Wi-Fi network.
                        This creates a secure local environment for sharing.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="step-card">
                    <div class="step-icon">
                        <i class="fas fa-upload"></i>
                    </div>
                    <h3 class="step-title">2. Upload & Share</h3>
                    <p class="step-description">
                        Upload files or paste text from any device. Your content is instantly available
                        to all devices on the same network using the same IP address.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="step-card">
                    <div class="step-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <h3 class="step-title">3. Access Anywhere</h3>
                    <p class="step-description">
                        Access your shared content from any device on the network. Download files,
                        copy text, and manage your content with ease.
                    </p>
                </div>
            </div>

            <div class="highlight-box">
                <div class="highlight-text">
                    <i class="fas fa-clock"></i>
                    Content automatically expires after 6 hours or 1 hour of inactivity for security
                </div>
            </div>

            <div class="features-section">
                <h2 class="step-title" style="text-align: center; margin-bottom: 2rem;">
                    <i class="fas fa-star"></i>
                    Key Features
                </h2>

                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="feature-title">Secure & Private</div>
                        <div class="feature-text">
                            Files stay on your local network. No external servers, no data mining.
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="feature-title">Lightning Fast</div>
                        <div class="feature-text">
                            Direct network transfer means instant sharing without internet delays.
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="feature-title">Cross-Platform</div>
                        <div class="feature-text">
                            Works on any device with a web browser. No apps to install.
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="feature-title">Multiple Formats</div>
                        <div class="feature-text">
                            Support for images, documents, text, and more. Up to 10MB per file.
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-link"></i>
                        </div>
                        <div class="feature-title">Smart Link Detection</div>
                        <div class="feature-text">
                            Automatically detects and makes URLs clickable in shared text.
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-trash-alt"></i>
                        </div>
                        <div class="feature-title">Auto-Cleanup</div>
                            <strong>Privacy Focused</strong><br>
                            Minimal analytics, no personal data collection
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="cta-section">
        <h2 class="cta-title">Ready to Start Sharing?</h2>
        <p class="cta-text">
            Join thousands of users who trust AirToShare for their local file sharing needs.
        </p>
        <a href="{{ url('/') }}" class="cta-button">
            <i class="fas fa-rocket"></i>
            Get Started Now
        </a>
    </div>
@endsection
