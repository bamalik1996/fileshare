@extends('layouts.app')

@section('title', 'How AirToShare Works - Step by Step Guide | File Sharing Tutorial')
@section('description',
    'Learn how to use AirToShare for instant file sharing across devices. Step-by-step guide for
    sharing files and text on the same Wi-Fi network securely.')
@section('keywords',
    'file sharing app, transfer files via Wi-Fi, local network transfer, cross-device file share, private file sharing')

@section('schema')
    <script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "HowTo",
  "name": "How to Share Files with AirToShare",
  "description": "Step-by-step guide to share files and text across devices using AirToShare",
  "image": "{{ url('/') }}/favicon.ico",
  "totalTime": "PT2M",
  "estimatedCost": {
    "@@type": "MonetaryAmount",
    "currency": "USD",
    "value": "0"
  },
  "supply": [
    {
      "@@type": "HowToSupply",
      "name": "Devices connected to same Wi-Fi network"
    }
  ],
  "tool": [
    {
      "@@type": "HowToTool",
      "name": "Web browser"
    }
  ],
  "step": [
    {
      "@@type": "HowToStep",
      "name": "Connect to Same Wi-Fi",
      "text": "Ensure all devices are connected to the same Wi-Fi network",
      "image": "{{ url('/') }}/favicon.ico"
    },
    {
      "@@type": "HowToStep",
      "name": "Upload Files or Text",
      "text": "Upload files or paste text from any device on the network",
      "image": "{{ url('/') }}/favicon.ico"
    },
    {
      "@@type": "HowToStep",
      "name": "Access from Other Devices",
      "text": "Access shared content from any device on the same network",
      "image": "{{ url('/') }}/favicon.ico"
    }
  ]
}
</script>
@endsection

@section('content')

    <div class="how-it-works-body">

        <div class="how-it-works-hero">
            <h1 class="how-it-works-title">
                <i class="fas fa-lightbulb"></i>
                How AirToShare Works
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
                            Upload files by dragging and dropping or clicking to browse. Paste text directly into the text
                            area.
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

    </div>
@endsection
