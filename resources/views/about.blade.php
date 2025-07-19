@extends('layouts.app')

@section('title', 'About AirForShare')

@section('content')
    <style>
        .about-hero {
            text-align: center;
            margin-bottom: 4rem;
        }

        .about-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: var(--bg-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .about-subtitle {
            font-size: 1.25rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }

        .step-card {
            background: var(--bg-primary);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            text-align: center;
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .step-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .step-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--bg-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
        }

        .step-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .step-description {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .features-section {
            margin: 4rem 0;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .feature-item {
            background: rgba(102, 126, 234, 0.05);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border-left: 4px solid var(--primary-color);
        }

        .feature-icon {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .feature-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .feature-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .cta-section {
            text-align: center;
            margin: 4rem 0;
            padding: 3rem 2rem;
            background: var(--bg-gradient);
            border-radius: var(--border-radius);
            color: white;
        }

        .cta-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .cta-text {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .cta-button {
            background: white;
            color: var(--primary-color);
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .highlight-box {
            background: rgba(237, 137, 54, 0.1);
            border: 1px solid rgba(237, 137, 54, 0.3);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: center;
        }

        .highlight-text {
            color: var(--warning-color);
            font-weight: 600;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .about-title {
                font-size: 2rem;
            }
            
            .steps-grid {
                grid-template-columns: 1fr;
            }
            
            .cta-section {
                padding: 2rem 1rem;
            }
        }
    </style>

    <div class="about-hero">
        <h1 class="about-title">
            <i class="fas fa-info-circle"></i>
            How AirForShare Works
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
                        <div class="feature-text">
                            Content automatically expires for security and storage management.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="cta-section">
        <h2 class="cta-title">Ready to Start Sharing?</h2>
        <p class="cta-text">
            Join thousands of users who trust AirForShare for their local file sharing needs.
        </p>
        <a href="{{ url('/') }}" class="cta-button">
            <i class="fas fa-rocket"></i>
            Get Started Now
        </a>
    </div>
@endsection