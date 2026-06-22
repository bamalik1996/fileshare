@if (filled(config('app.recpatcha.RECAPTCHA_SITE_KEY')))
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif
