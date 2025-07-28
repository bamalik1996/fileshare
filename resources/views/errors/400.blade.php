@extends('layouts.app')

@section('title', '400 - Bad Request | AirToShare')
@section('description', 'Bad request error on AirToShare. Please check your request and try again.')
@section('keywords', 'AirToShare 400, bad request, error page, file sharing')

@section('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "WebPage",
  "name": "400 - Bad Request",
  "description": "Bad request error on AirToShare file sharing platform",
  "url": "{{ url()->current() }}",
  "mainEntity": {
    "@@type": "SoftwareApplication",
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
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 1rem;
            animation: shake 2s infinite;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
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
            color: var(--warning-color);
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

        .helpful-tips {
            margin-top: 3rem;
            padding: 2rem;
            background: rgba(245, 158, 11, 0.1);
            border-radius: var(--border-radius);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .tips-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .tips-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .tip-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: var(--bg-primary);
            border-radius: var(--border-radius-sm);
            border-left: 4px solid var(--warning-color);
        }

        .tip-icon {
            color: var(--warning-color);
            font-size: 1.2rem;
            margin-top: 0.2rem;
        }

        .tip-text {
            color: var(--text-secondary);
            line-height: 1.6;
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
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="floating-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="floating-icon">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="floating-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
            </div>

            <!-- Error Number -->
            <div class="error-number">400</div>

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
