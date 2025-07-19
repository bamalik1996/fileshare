@extends('layouts.app')

@section('title', 'How AirForShare Works - Step by Step Guide | File Sharing Tutorial')
@section('description', 'Learn how to use AirForShare for instant file sharing across devices. Step-by-step guide for sharing files and text on the same Wi-Fi network securely.')
@section('keywords', 'how to share files, file sharing tutorial, AirForShare guide, cross-device sharing, Wi-Fi file transfer, local network sharing')

@section('schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "HowTo",
  "name": "How to Share Files with AirForShare",
  "description": "Step-by-step guide to share files and text across devices using AirForShare",
  "image": "{{ url('/') }}/favicon.ico",
  "totalTime": "PT2M",
  "estimatedCost": {
    "@type": "MonetaryAmount",
    "currency": "USD",
    "value": "0"
  },
  "supply": [
    {
      "@type": "HowToSupply",
      "name": "Devices connected to same Wi-Fi network"
    }
  ],
  "tool": [
    {
      "@type": "HowToTool",
      "name": "Web browser"
    }
  ],
  "step": [
    {
      "@type": "HowToStep",
      "name": "Connect to Same Wi-Fi",
      "text": "Ensure all devices are connected to the same Wi-Fi network",
      "image": "{{ url('/') }}/favicon.ico"
    },
    {
      "@type": "HowToStep", 
      "name": "Upload Files or Text",
      "text": "Upload files or paste text from any device on the network",
      "image": "{{ url('/') }}/favicon.ico"
    },
    {
      "@type": "HowToStep",
      "name": "Access from Other Devices", 
      "text": "Access shared content from any device on the same network",
      "image": "{{ url('/') }}/favicon.ico"
    }
  ]
}
</script>
@endsection

@section('content')
    <style>
        .how-it-works-hero {
            text-align: center;
            margin-bottom: 4rem;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: var(--border-radius);
        }

        .how-it-works-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: var(--bg-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .how-it-works-subtitle {
            font-size: 1.25rem;
            color: var(--text-secondary);
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .steps-container {
            margin: 4rem 0;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 3rem;
            margin: 3rem 0;
        }

        .step-card {
            background: var(--bg-primary);
            border-radius: var(--border-radius);
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            text-align: center;
            transition: var(--transition);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .step-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--bg-gradient);
        }

        .step-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-color);
        }

        .step-number {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--bg-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 2rem;
            font-weight: 700;
            color: white;
            box-shadow: var(--shadow-md);
        }

        .step-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }

        .step-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .step-description {
            color: var(--text-secondary);
            line-height: 1.7;
            font-size: 1.1rem;
        }

        .features-section {
            margin: 5rem 0;
            padding: 3rem 2rem;
            background: rgba(102, 126, 234, 0.03);
            border-radius: var(--border-radius);
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
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-card {
            background: var(--bg-primary);
            border-radius: var(--border-radius);
            padding: 2rem;
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .feature-card:hover {
            transform: translateX(8px);
            box-shadow: var(--shadow-md);
        }

        .feature-icon {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .feature-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            font-size: 1.25rem;
        }

        .feature-text {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .security-section {
            margin: 5rem 0;
            padding: 3rem;
            background: linear-gradient(135deg, rgba(72, 187, 120, 0.1) 0%, rgba(56, 161, 105, 0.1) 100%);
            border-radius: var(--border-radius);
            border: 1px solid rgba(72, 187, 120, 0.2);
        }

        .security-title {
            text-align: center;
            font-size: 2rem;
            font-weight: 600;
            color: var(--success-color);
            margin-bottom: 2rem;
        }

        .security-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .security-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 8px;
        }

        .security-icon {
            color: var(--success-color);
            font-size: 1.5rem;
        }

        .cta-section {
            text-align: center;
            margin: 5rem 0;
            padding: 4rem 2rem;
            background: var(--bg-gradient);
            border-radius: var(--border-radius);
            color: white;
        }

        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .cta-text {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-button {
            background: white;
            color: var(--primary-color);
            padding: 1.25rem 3rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            transition: var(--transition);
            box-shadow: var(--shadow-lg);
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .how-it-works-title {
                font-size: 2rem;
            }
            
            .steps-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .step-card {
                padding: 2rem;
            }
            
            .cta-section {
                padding: 3rem 1rem;
            }
        }
    </style>

    <div class="how-it-works-hero">
        <h1 class="how-it-works-title">
            <i class="fas fa-lightbulb"></i>
            How AirForShare Works
        </h1>
        <p class="how-it-works-subtitle">
            Simple, secure, and instant file sharing across devices on your local network. 
            No accounts, no uploads to external servers, just pure peer-to-peer sharing in three easy steps.
        </p>
    </div>

    <div class="modern-card">
        <div class="steps-container">
            <div class="steps-grid">
                <!-- Step 1 -->
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-icon">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <h3 class="step-title">Connect to Same Wi-Fi</h3>
                    <p class="step-description">
                        Ensure all devices you want to share files with are connected to the same Wi-Fi network. 
                        This creates a secure local environment where your data never leaves your network.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h3 class="step-title">Upload & Share</h3>
                    <p class="step-description">
                        Upload files by dragging and dropping or clicking to browse. Paste text directly into the text area. 
                        Your content is instantly available to all devices using the same IP address.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <h3 class="step-title">Access Anywhere</h3>
                    <p class="step-description">
                        Access your shared content from any device on the network. Download individual files, 
                        create zip archives for multiple files, or send files via email with just a few clicks.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="features-section">
        <h2 class="section-title">
            <i class="fas fa-star"></i>
            Powerful Features
        </h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="feature-title">100% Secure & Private</div>
                <div class="feature-text">
                    Your files never leave your local network. No external servers, no data mining, 
                    no privacy concerns. Everything stays within your Wi-Fi network.
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="feature-title">Lightning Fast Transfer</div>
                <div class="feature-text">
                    Direct network transfer means instant sharing without internet delays. 
                    Transfer speeds limited only by your local network bandwidth.
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <div class="feature-title">Universal Compatibility</div>
                <div class="feature-text">
                    Works on any device with a web browser - phones, tablets, laptops, desktops. 
                    No apps to install, no platform restrictions.
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="feature-title">Multiple File Formats</div>
                <div class="feature-text">
                    Support for images, documents, PDFs, text files, and archives. 
                    Up to 10MB per file with 20 files maximum per session.
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-link"></i>
                </div>
                <div class="feature-title">Smart Text Features</div>
                <div class="feature-text">
                    Automatically detects and makes URLs clickable in shared text. 
                    Character counter and clipboard integration for better productivity.
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="feature-title">Auto-Cleanup</div>
                <div class="feature-text">
                    Content automatically expires after 6 hours or 1 hour of inactivity 
                    for security and storage management.
                </div>
            </div>
        </div>
    </div>

    <div class="security-section">
        <h2 class="security-title">
            <i class="fas fa-lock"></i>
            Security & Privacy First
        </h2>
        
        <div class="security-grid">
            <div class="security-item">
                <div class="security-icon">
                    <i class="fas fa-network-wired"></i>
                </div>
                <div>
                    <strong>Local Network Only</strong><br>
                    Files never leave your Wi-Fi network
                </div>
            </div>

            <div class="security-item">
                <div class="security-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <strong>No Account Required</strong><br>
                    No personal information collected
                </div>
            </div>

            <div class="security-item">
                <div class="security-icon">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <div>
                    <strong>Auto-Delete</strong><br>
                    Content automatically expires
                </div>
            </div>

            <div class="security-item">
                <div class="security-icon">
                    <i class="fas fa-eye-slash"></i>
                </div>
                <div>
                    <strong>No Tracking</strong><br>
                    No analytics or user tracking
                </div>
            </div>
        </div>
    </div>

    <div class="cta-section">
        <h2 class="cta-title">Ready to Start Sharing?</h2>
        <p class="cta-text">
            Experience the fastest and most secure way to share files across your devices. 
            No setup required - just start sharing!
        </p>
        <a href="{{ url('/') }}" class="cta-button">
            <i class="fas fa-rocket"></i>
            Start Sharing Now
        </a>
    </div>
@endsection