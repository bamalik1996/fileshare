@extends('layouts.app')

@section('title', 'Feedback & Support - AirToShare | Contact Us')
@section('description', 'Send feedback, report bugs, or request features for AirToShare. Our support team is here to help improve your file sharing experience.')
@section('keywords', 'AirToShare feedback, contact support, bug report, feature request, file sharing help')

@section('schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ContactPage",
  "name": "AirToShare Feedback & Support",
  "description": "Contact AirToShare support team for feedback, bug reports, and feature requests",
  "url": "{{ url('/feedback') }}",
  "mainEntity": {
    "@type": "Organization",
    "name": "AirToShare",
    "contactPoint": {
      "@type": "ContactPoint",
      "contactType": "Customer Support",
      "availableLanguage": "English"
    }
  }
}
</script>
@endsection

@section('content')
    <style>
        .feedback-hero {
            text-align: center;
            margin-bottom: 3rem;
        }

        .feedback-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: var(--bg-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .feedback-subtitle {
            font-size: 1.25rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .feedback-form {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .form-input,
        .form-textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: var(--transition);
            outline: none;
        }

        .form-input:focus,
        .form-textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-button {
            width: 100%;
            background: var(--bg-gradient);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .form-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .form-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .feedback-types {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .feedback-type {
            background: rgba(102, 126, 234, 0.05);
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: var(--border-radius);
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .feedback-type:hover {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.1);
        }

        .feedback-type.selected {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.1);
        }

        .feedback-type-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .feedback-type-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .feedback-type-desc {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .success-message {
            background: rgba(72, 187, 120, 0.1);
            border: 1px solid rgba(72, 187, 120, 0.3);
            color: var(--success-color);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            text-align: center;
            margin-bottom: 2rem;
            display: none;
        }

        .error-message {
            background: rgba(245, 101, 101, 0.1);
            border: 1px solid rgba(245, 101, 101, 0.3);
            color: var(--error-color);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            text-align: center;
            margin-bottom: 2rem;
            display: none;
        }

        .contact-info {
            background: rgba(102, 126, 234, 0.05);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-top: 3rem;
            text-align: center;
        }

        .contact-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .contact-text {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }

        .contact-methods {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .contact-method {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .contact-method:hover {
            color: var(--primary-dark);
        }

        @media (max-width: 768px) {
            .feedback-title {
                font-size: 2rem;
            }
            
            .feedback-form {
                padding: 1rem;
            }
            
            .contact-methods {
                flex-direction: column;
                align-items: center;
                gap: 1rem;
            }
        }
    </style>

    <div class="feedback-hero">
        <h1 class="feedback-title">
            <i class="fas fa-comment-dots"></i>
            Share Your Feedback
        </h1>
        <p class="feedback-subtitle">
            Help us improve AirToShare! Your feedback is valuable and helps us create a better experience for everyone.
        </p>
    </div>

    <div class="modern-card">
        <div class="feedback-form">
            <div class="success-message" id="successMessage">
                <i class="fas fa-check-circle" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                <div>Thank you for your feedback! We appreciate your input and will review it carefully.</div>
            </div>

            <div class="error-message" id="errorMessage">
                <i class="fas fa-exclamation-circle" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                <div>Please fill in all required fields before submitting.</div>
            </div>

            <form id="feedbackForm">
                <div class="form-group">
                    <label class="form-label">What type of feedback do you have?</label>
                    <div class="feedback-types">
                        <div class="feedback-type" data-type="bug">
                            <div class="feedback-type-icon">
                                <i class="fas fa-bug"></i>
                            </div>
                            <div class="feedback-type-title">Bug Report</div>
                            <div class="feedback-type-desc">Something isn't working</div>
                        </div>
                        <div class="feedback-type" data-type="feature">
                            <div class="feedback-type-icon">
                                <i class="fas fa-lightbulb"></i>
                            </div>
                            <div class="feedback-type-title">Feature Request</div>
                            <div class="feedback-type-desc">Suggest new features</div>
                        </div>
                        <div class="feedback-type" data-type="improvement">
                            <div class="feedback-type-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="feedback-type-title">Improvement</div>
                            <div class="feedback-type-desc">Make existing features better</div>
                        </div>
                        <div class="feedback-type" data-type="general">
                            <div class="feedback-type-icon">
                                <i class="fas fa-comment"></i>
                            </div>
                            <div class="feedback-type-title">General</div>
                            <div class="feedback-type-desc">Other feedback</div>
                        </div>
                    </div>
                    <input type="hidden" id="feedbackType" name="type" required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">
                        Email Address <span style="color: var(--error-color);">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="your.email@example.com"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="subject" class="form-label">
                        Subject <span style="color: var(--error-color);">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="subject" 
                        name="subject" 
                        class="form-input" 
                        placeholder="Brief description of your feedback"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="message" class="form-label">
                        Message <span style="color: var(--error-color);">*</span>
                    </label>
                    <textarea 
                        id="message" 
                        name="message" 
                        class="form-textarea" 
                        placeholder="Please provide detailed feedback. For bug reports, include steps to reproduce the issue."
                        required
                    ></textarea>
                </div>

                <button type="submit" class="form-button" id="submitBtn">
                    <i class="fas fa-paper-plane"></i>
                    <span id="submitText">Send Feedback</span>
                    <div class="loading-spinner" id="submitLoader" style="display: none;"></div>
                </button>
            </form>
        </div>
    </div>

    <div class="contact-info">
        <h3 class="contact-title">
            <i class="fas fa-envelope"></i>
            Other Ways to Reach Us
        </h3>
        <p class="contact-text">
            Prefer a different way to get in touch? We're here to help through multiple channels.
        </p>
        <div class="contact-methods">
            <a href="mailto:support@airforshare.com" class="contact-method">
                <i class="fas fa-envelope"></i>
                support@airforshare.com
            </a>
            <a href="https://web.facebook.com/airtoshare/" target="_blank" class="contact-method">
                <i class="fab fa-facebook-f"></i>
                Facebook Page
            </a>
            <a href="https://github.com/airtoshare" target="_blank" class="contact-method">
                <i class="fab fa-github"></i>
                GitHub Issues
            </a>
            <a href="https://twitter.com/airtoshare" target="_blank" class="contact-method">
                <i class="fab fa-twitter"></i>
                @AirForShare
            </a>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            setupFeedbackForm();
        });

        function setupFeedbackForm() {
            // Feedback type selection
            $('.feedback-type').click(function() {
                $('.feedback-type').removeClass('selected');
                $(this).addClass('selected');
                $('#feedbackType').val($(this).data('type'));
            });

            // Form submission
            $('#feedbackForm').submit(function(e) {
                e.preventDefault();
                submitFeedback();
            });
        }

        function submitFeedback() {
            const form = $('#feedbackForm');
            const submitBtn = $('#submitBtn');
            const submitText = $('#submitText');
            const submitLoader = $('#submitLoader');

            // Validate form
            if (!validateForm()) {
                showMessage('error', 'Please fill in all required fields.');
                return;
            }

            // Show loading state
            submitBtn.prop('disabled', true);
            submitText.hide();
            submitLoader.show();

            // Simulate form submission (replace with actual endpoint)
            setTimeout(() => {
                // Reset loading state
                submitBtn.prop('disabled', false);
                submitText.show();
                submitLoader.hide();

                // Show success message
                showMessage('success', 'Thank you for your feedback!');
                
                // Reset form
                form[0].reset();
                $('.feedback-type').removeClass('selected');
                $('#feedbackType').val('');
            }, 2000);
        }

        function validateForm() {
            const email = $('#email').val().trim();
            const subject = $('#subject').val().trim();
            const message = $('#message').val().trim();
            const type = $('#feedbackType').val();

            return email && subject && message && type;
        }

        function showMessage(type, message) {
            const successMsg = $('#successMessage');
            const errorMsg = $('#errorMessage');

            // Hide all messages first
            successMsg.hide();
            errorMsg.hide();

            if (type === 'success') {
                successMsg.find('div').last().text(message);
                successMsg.show();
                
                // Scroll to top to show message
                $('html, body').animate({
                    scrollTop: successMsg.offset().top - 100
                }, 500);
            } else {
                errorMsg.find('div').last().text(message);
                errorMsg.show();
            }

            // Auto-hide after 5 seconds
            setTimeout(() => {
                successMsg.hide();
                errorMsg.hide();
            }, 5000);
        }
    </script>
@endsection