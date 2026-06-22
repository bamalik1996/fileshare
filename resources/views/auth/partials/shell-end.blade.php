        </div>
    </div>

    @if (!empty($footerLinks))
        <div class="auth-footer-links">
            @foreach ($footerLinks as $link)
                <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
            @endforeach
        </div>
    @endif

    <p class="auth-guest-note">
        <a href="{{ url('/') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Continue without an account</a>
    </p>
</div>
