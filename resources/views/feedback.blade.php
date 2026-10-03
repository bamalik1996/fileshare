@extends('layouts.app')

@section('title', 'Feedback & Support - AirToShare | Contact Us')
@section('breadcrumb_label', 'Feedback')
@section('description',
    'Send feedback, report bugs, or request features for AirToShare. Our support team is here to
    help improve your file sharing experience.')
@section('keywords', 'AirToShare feedback, contact support, bug report, feature request, file sharing help')

@section('schema')
    <script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "ContactPage",
  "name": "AirToShare Feedback & Support",
  "description": "Contact AirToShare support team for feedback, bug reports, and feature requests",
  "url": "{{ url('/feedback') }}",
  "mainEntity": {
    "@@type": "Organization",
    "name": "AirToShare",
    "contactPoint": {
      "@@type": "ContactPoint",
      "contactType": "Customer Support",
      "availableLanguage": "English"
    }
  }
}
</script>
@endsection

@section('content')

<div class="feedback-page">

    <header class="feedback-page-hero">
        <span class="feedback-page-badge"><i class="fas fa-comment-dots" aria-hidden="true"></i> Contact us</span>
        <h1 class="feedback-page-title">Share your feedback</h1>
        <p class="feedback-page-lead">
            Report a bug, suggest a feature, or tell us what we can improve.
            Every message helps make AirToShare better.
        </p>
    </header>

    <div class="feedback-page-card">
        <div class="feedback-page-alert feedback-page-alert--success" id="successMessage" role="status" aria-live="polite" hidden>
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <span id="successMessageText">Thank you for your feedback! We appreciate your input and will review it carefully.</span>
        </div>

        <div class="feedback-page-alert feedback-page-alert--error" id="errorMessage" role="alert" aria-live="assertive" hidden>
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span id="errorMessageText">Please fill in all required fields before submitting.</span>
        </div>

        <form id="feedbackForm" class="feedback-page-form" novalidate>
            <div class="feedback-page-field">
                <span class="form-label" id="feedbackTypeLabel">What type of feedback do you have?</span>
                <div class="feedback-page-types" role="radiogroup" aria-labelledby="feedbackTypeLabel">
                    <button type="button" class="feedback-page-type" data-type="bug" role="radio" aria-checked="false">
                        <span class="feedback-page-type-icon"><i class="fas fa-bug" aria-hidden="true"></i></span>
                        <span class="feedback-page-type-title">Bug report</span>
                        <span class="feedback-page-type-desc">Something isn't working</span>
                    </button>
                    <button type="button" class="feedback-page-type" data-type="feature" role="radio" aria-checked="false">
                        <span class="feedback-page-type-icon"><i class="fas fa-lightbulb" aria-hidden="true"></i></span>
                        <span class="feedback-page-type-title">Feature request</span>
                        <span class="feedback-page-type-desc">Suggest something new</span>
                    </button>
                    <button type="button" class="feedback-page-type" data-type="improvement" role="radio" aria-checked="false">
                        <span class="feedback-page-type-icon"><i class="fas fa-chart-line" aria-hidden="true"></i></span>
                        <span class="feedback-page-type-title">Improvement</span>
                        <span class="feedback-page-type-desc">Make existing features better</span>
                    </button>
                    <button type="button" class="feedback-page-type" data-type="general" role="radio" aria-checked="false">
                        <span class="feedback-page-type-icon"><i class="fas fa-comment" aria-hidden="true"></i></span>
                        <span class="feedback-page-type-title">General</span>
                        <span class="feedback-page-type-desc">Other feedback</span>
                    </button>
                </div>
                <input type="hidden" id="feedbackType" name="type" value="">
                <p class="feedback-page-hint" id="feedbackTypeHint">Select a category to continue</p>
            </div>

            <div class="feedback-page-field">
                <label for="email" class="form-label">
                    Email address <span class="feedback-page-required" aria-hidden="true">*</span>
                </label>
                <input type="email" id="email" name="email" class="form-input feedback-page-input"
                    placeholder="you@example.com"
                    value="{{ auth('account')->check() ? auth('account')->user()->email : '' }}"
                    autocomplete="email" required>
            </div>

            <div class="feedback-page-field">
                <label for="subject" class="form-label">
                    Subject <span class="feedback-page-required" aria-hidden="true">*</span>
                </label>
                <input type="text" id="subject" name="subject" class="form-input feedback-page-input"
                    placeholder="Brief summary of your feedback" maxlength="200" required>
            </div>

            <div class="feedback-page-field">
                <label for="message" class="form-label">
                    Message <span class="feedback-page-required" aria-hidden="true">*</span>
                </label>
                <textarea id="message" name="message" class="form-textarea feedback-page-textarea"
                    placeholder="Tell us more. For bugs, include steps to reproduce, browser/device, and what you expected to happen."
                    rows="6" required></textarea>
            </div>

            <div class="feedback-page-field feedback-page-captcha">
                <div class="g-recaptcha" data-sitekey="{{ config('app.recpatcha.RECAPTCHA_SITE_KEY') }}"></div>
                @if ($errors->has('g-recaptcha-response'))
                    <p class="feedback-page-field-error">{{ $errors->first('g-recaptcha-response') }}</p>
                @endif
            </div>

            <button type="submit" class="modern-btn feedback-page-submit" id="submitBtn">
                <i class="fas fa-paper-plane" id="submitIcon" aria-hidden="true"></i>
                <span id="submitText">Send feedback</span>
                <span class="loading-spinner feedback-page-spinner" id="submitLoader" hidden aria-hidden="true"></span>
            </button>
        </form>
    </div>

    <section class="feedback-page-aside" aria-labelledby="feedback-aside-heading">
        <h2 class="feedback-page-aside-heading" id="feedback-aside-heading">
            <i class="fas fa-life-ring" aria-hidden="true"></i>
            Other ways to reach us
        </h2>
        <div class="feedback-page-channels">
            <a href="mailto:bilalmalik531996@gmail.com" class="feedback-page-channel">
                <span class="feedback-page-channel-icon"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                <span class="feedback-page-channel-label">bilalmalik531996@gmail.com</span>
            </a>
            <a href="https://web.facebook.com/airtoshare/" target="_blank" rel="noopener noreferrer" class="feedback-page-channel">
                <span class="feedback-page-channel-icon"><i class="fab fa-facebook-f" aria-hidden="true"></i></span>
                <span class="feedback-page-channel-label">Facebook</span>
            </a>
            <a href="https://github.com/airtoshare" target="_blank" rel="noopener noreferrer" class="feedback-page-channel">
                <span class="feedback-page-channel-icon"><i class="fab fa-github" aria-hidden="true"></i></span>
                <span class="feedback-page-channel-label">GitHub</span>
            </a>
            <a href="https://x.com/airtoshare" target="_blank" rel="noopener noreferrer" class="feedback-page-channel">
                <span class="feedback-page-channel-icon"><i class="fab fa-twitter" aria-hidden="true"></i></span>
                <span class="feedback-page-channel-label">@AirToShare</span>
            </a>
        </div>
    </section>

    <div class="feedback-page-footer-note">
        <p>Looking for quick answers? Check the <a href="{{ url('/faq') }}">FAQ</a> first — many common questions are already covered there.</p>
    </div>

</div>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<script>
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var typeButtons = document.querySelectorAll('.feedback-page-type');
        var typeInput = document.getElementById('feedbackType');
        var typeHint = document.getElementById('feedbackTypeHint');
        var form = document.getElementById('feedbackForm');

        typeButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                typeButtons.forEach(function (b) {
                    b.classList.remove('is-selected');
                    b.setAttribute('aria-checked', 'false');
                });
                btn.classList.add('is-selected');
                btn.setAttribute('aria-checked', 'true');
                typeInput.value = btn.getAttribute('data-type') || '';
                if (typeHint) {
                    typeHint.textContent = 'Selected: ' + btn.querySelector('.feedback-page-type-title').textContent;
                }
            });
        });

        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                submitFeedback();
            });
        }
    });

    function submitFeedback() {
        var form = document.getElementById('feedbackForm');
        var submitBtn = document.getElementById('submitBtn');
        var submitText = document.getElementById('submitText');
        var submitIcon = document.getElementById('submitIcon');
        var submitLoader = document.getElementById('submitLoader');

        if (!validateForm()) {
            showMessage('error', 'Please fill in all required fields and complete the captcha.');
            return;
        }

        submitBtn.disabled = true;
        submitText.hidden = true;
        if (submitIcon) submitIcon.hidden = true;
        submitLoader.hidden = false;

        var formData = window.jQuery ? window.jQuery(form).serialize() : new URLSearchParams(new FormData(form)).toString();

        window.jQuery.ajax({
            url: '/api/v1/submit-feedback',
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            success: function (response) {
                if (typeof safeGtag === 'function') {
                    safeGtag('event', 'feedback_submitted', {
                        event_category: 'Feedback',
                        event_label: 'Feedback Form',
                        value: 1
                    });
                }

                submitBtn.disabled = false;
                submitText.hidden = false;
                if (submitIcon) submitIcon.hidden = false;
                submitLoader.hidden = true;

                showMessage('success', response.message || 'Thank you for your feedback!');
                if (typeof showToast === 'function') {
                    showToast('success', 'Sent', response.message || 'Thank you for your feedback!');
                }

                form.reset();
                var emailField = document.getElementById('email');
                if (emailField && emailField.defaultValue) {
                    emailField.value = emailField.defaultValue;
                }
                document.querySelectorAll('.feedback-page-type').forEach(function (btn) {
                    btn.classList.remove('is-selected');
                    btn.setAttribute('aria-checked', 'false');
                });
                document.getElementById('feedbackType').value = '';
                var hint = document.getElementById('feedbackTypeHint');
                if (hint) hint.textContent = 'Select a category to continue';

                if (typeof grecaptcha !== 'undefined') {
                    grecaptcha.reset();
                }
            },
            error: function (xhr) {
                submitBtn.disabled = false;
                submitText.hidden = false;
                if (submitIcon) submitIcon.hidden = false;
                submitLoader.hidden = true;

                if (typeof grecaptcha !== 'undefined') {
                    grecaptcha.reset();
                }

                var errorMsg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Something went wrong. Please try again.';
                showMessage('error', errorMsg);
                if (typeof showToast === 'function') {
                    showToast('error', 'Error', errorMsg);
                }
            }
        });
    }

    function validateForm() {
        var email = document.getElementById('email').value.trim();
        var subject = document.getElementById('subject').value.trim();
        var message = document.getElementById('message').value.trim();
        var type = document.getElementById('feedbackType').value;
        var captchaOk = typeof grecaptcha !== 'undefined'
            && grecaptcha.getResponse().length > 0;

        return email && subject && message && type && captchaOk;
    }

    function showMessage(type, message) {
        var successMsg = document.getElementById('successMessage');
        var errorMsg = document.getElementById('errorMessage');
        var successText = document.getElementById('successMessageText');
        var errorText = document.getElementById('errorMessageText');

        successMsg.hidden = true;
        errorMsg.hidden = true;

        if (type === 'success') {
            successText.textContent = message;
            successMsg.hidden = false;
            successMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            errorText.textContent = message;
            errorMsg.hidden = false;
        }

        window.setTimeout(function () {
            successMsg.hidden = true;
            errorMsg.hidden = true;
        }, 6000);
    }
})();
</script>

@endsection
