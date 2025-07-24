@extends('layouts.app')

@section('title', '400 - Bad Request | AirToShare')
@section('description', 'Bad request error on AirToShare. Please check your request and try again.')
@section('keywords', 'AirToShare 400, bad request, error page, file sharing')

@section('schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "400 - Bad Request",
  "description": "Bad request error on AirToShare file sharing platform",
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
                <i class="fas fa-exclamation-triangle" style="color: var(--warning-color); margin-right: 0.5rem;"></i>
                Bad Request
            </h1>
            
            <p class="error-subtitle">
                Oops! Something went wrong with your request. 
                Don't worry, let's get you back on track with AirToShare!
            </p>

            <!-- Action Buttons -->
            <div class="error-actions">
                <a href="{{ url('/') }}" class="error-btn">
                    <i class="fas fa-home"></i>
                    Back to Home
                </a>
                <button onclick="window.history.back()" class="error-btn secondary">
                    <i class="fas fa-arrow-left"></i>
                    Go Back
                </button>
            </div>
        </div>
    </div>

    <!-- Helpful Tips Section -->
    <div class="modern-card">
        <div class="helpful-tips">
            <h2 class="tips-title">
                <i class="fas fa-lightbulb"></i>
                What Might Have Gone Wrong?
            </h2>
            
            <ul class="tips-list">
                <li class="tip-item">
                    <div class="tip-icon">
                        <i class="fas fa-file-times"></i>
                    </div>
                    <div class="tip-text">
                        <strong>File Size Too Large:</strong> Make sure your file is under 10MB in size.
                    </div>
                </li>

                <li class="tip-item">
                    <div class="tip-icon">
                        <i class="fas fa-ban"></i>
                    </div>
                    <div class="tip-text">
                        <strong>Unsupported File Type:</strong> We support images, documents, text files, and archives.
                    </div>
                </li>

                <li class="tip-item">
                    <div class="tip-icon">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <div class="tip-text">
                        <strong>Network Issue:</strong> Check your internet connection and try again.
                    </div>
                </li>

                <li class="tip-item">
                    <div class="tip-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="tip-text">
                        <strong>Session Expired:</strong> Your session may have expired. Please refresh and try again.
                    </div>
                </li>

                <li class="tip-item">
                    <div class="tip-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="tip-text">
                        <strong>Too Many Files:</strong> You can upload maximum 20 files per session.
                    </div>
                </li>
            </ul>
        </div>
    </div>
@endsection