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
    <div class="error-container">
        <div class="error-content">
            <!-- Floating Icons -->
            <div class="error-illustration">
                <div class="floating-icon warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="floating-icon warning">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="floating-icon warning">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="floating-icon warning">
                    <i class="fas fa-question-circle"></i>
                </div>
            </div>

            <!-- Error Number -->
            <div class="error-number error-400">400</div>

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