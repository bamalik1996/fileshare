@if (filled(config('app.recpatcha.RECAPTCHA_SITE_KEY')))
    <div class="form-group auth-captcha">
        <div class="g-recaptcha" data-sitekey="{{ config('app.recpatcha.RECAPTCHA_SITE_KEY') }}"></div>
        @error('g-recaptcha-response')
            <p class="auth-captcha-error">{{ $message }}</p>
        @enderror
    </div>
@endif
