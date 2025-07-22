@extends('layouts.app')

@section('title', '500 - Server Error | AirToShare')
@section('description', 'Internal server error on AirToShare. Our team has been notified and is working to fix this issue.')
@section('keywords', 'AirToShare 500, server error, technical issue, file sharing')

@section('schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "500 - Server Error",
  "description": "Internal server error on AirToShare file sharing platform",
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
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
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
            height: 120px;
        }

        .floating-icon {
            font-size: 3rem;
            color: var(--error-color);
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

        .status-section {
            margin-top: 3rem;
            padding: 2rem;
            background: rgba(239, 68, 68, 0.1);
            border-radius: var(--border-radius);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .status-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .status-item {
            background: var(--bg-primary);
            border-radius: var(--border-radius-sm);
            padding: 1.5rem;
            text-align: center;
            border-left: 4px solid var(--error-color);
        }

        .status-icon {
            font-size: 2rem;
            color: var(--error-color);
            margin-bottom: 1rem;
        }

        .status-item-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .status-desc {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.6;
        }

        .refresh-timer {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(14, 165, 233, 0.1);
            border-radius: var(--border-radius-sm);
            border: 1px solid rgba(14, 165, 233, 0.3);
            text-align: center;
        }

        .timer-text {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.1rem;
        }

        .timer-countdown {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-top: 0.5rem;
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
                    <i class="fas fa-server"></i>
                </div>
                <div class="floating-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="floating-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <div class="floating-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
            </div>

            <!-- Error Number -->
            <div class="error-number">500</div>

            <!-- Error Message -->
            <h1 class="error-title">
                <i class="fas fa-server" style="color: var(--error-color); margin-right: 0.5rem;"></i>
                Server Error
            </h1>
            
            <p class="error-subtitle">
                We're experiencing some technical difficulties. Our team has been automatically notified 
                and is working hard to fix this issue. Please try again in a few moments.
            </p>

            <!-- Refresh Timer -->
            <div class="refresh-timer">
                <div class="timer-text">
                    <i class="fas fa-sync-alt"></i>
                    Auto-refresh in:
                </div>
                <div class="timer-countdown" id="countdown">30</div>
            </div>

            <!-- Action Buttons -->
            <div class="error-actions">
                <button onclick="window.location.reload()" class="error-btn">
                    <i class="fas fa-redo"></i>
                    Try Again
                </button>
                <a href="{{ url('/') }}" class="error-btn secondary">
                    <i class="fas fa-home"></i>
                    Back to Home
                </a>
            </div>
        </div>
    </div>

    <!-- Status Section -->
    <div class="modern-card">
        <div class="status-section">
            <h2 class="status-title">
                <i class="fas fa-info-circle"></i>
                What's Happening?
            </h2>
            
            <div class="status-grid">
                <div class="status-item">
                    <div class="status-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="status-item-title">Team Notified</div>
                    <div class="status-desc">
                        Our technical team has been automatically alerted about this issue and is investigating.
                    </div>
                </div>

                <div class="status-item">
                    <div class="status-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="status-item-title">Data Safe</div>
                    <div class="status-desc">
                        Your files and data are completely safe. This is just a temporary technical issue.
                    </div>
                </div>

                <div class="status-item">
                    <div class="status-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="status-item-title">Quick Resolution</div>
                    <div class="status-desc">
                        Most issues are resolved within minutes. Please try refreshing the page.
                    </div>
                </div>

                <div class="status-item">
                    <div class="status-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="status-item-title">Need Help?</div>
                    <div class="status-desc">
                        If the problem persists, please contact our support team for assistance.
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <a href="{{ url('/feedback') }}" class="error-btn secondary">
                    <i class="fas fa-envelope"></i>
                    Contact Support
                </a>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh countdown
        let countdown = 30;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.reload();
            }
        }, 1000);

        // Stop countdown if user interacts with page
        document.addEventListener('click', () => {
            clearInterval(timer);
            countdownElement.textContent = 'Stopped';
        });
    </script>
@endsection