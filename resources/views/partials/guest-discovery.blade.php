{{--
    Guest feature discovery (marketing soft-sell).
    Only rendered for anonymous visitors. Skippable; state in localStorage.
--}}
@guest('account')
    <div class="whats-new-banner" id="whatsNewBanner" hidden role="region" aria-label="What's new on AirToShare">
        <div class="whats-new-banner-inner">
            <div class="whats-new-banner-copy">
                <span class="whats-new-banner-badge">
                    <i class="fas fa-star" aria-hidden="true"></i>
                    What’s new
                </span>
                <p class="whats-new-banner-text">
                    Free accounts unlock <strong>link analytics</strong>, <strong>revoke &amp; download limits</strong>,
                    <strong>file requests</strong>, <strong>custom branding</strong>, and higher limits —
                    while guest sharing still works as usual.
                </p>
            </div>
            <div class="whats-new-banner-actions">
                <a href="{{ route('auth.register') }}" class="modern-btn whats-new-primary">
                    <i class="fas fa-user-plus" aria-hidden="true"></i>
                    Free account
                </a>
                <a href="{{ route('blog.index') }}" class="modern-btn secondary whats-new-secondary">
                    See updates
                </a>
                <button type="button" class="whats-new-dismiss" id="whatsNewDismiss" aria-label="Dismiss what’s new">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="level-up-overlay" id="levelUpOverlay" hidden>
        <div class="level-up-card" role="dialog" aria-modal="true" aria-labelledby="levelUpTitle">
            <button type="button" class="level-up-close" id="levelUpClose" aria-label="Close">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
            <div class="level-up-icon" aria-hidden="true">
                <i class="fas fa-rocket"></i>
            </div>
            <h2 class="level-up-title" id="levelUpTitle">Share ready — go further?</h2>
            <p class="level-up-text">
                You can keep sharing as a guest. A free account adds power tools for the same workflow:
            </p>
            <ul class="level-up-list">
                <li><i class="fas fa-chart-line" aria-hidden="true"></i> See who viewed &amp; downloaded</li>
                <li><i class="fas fa-ban" aria-hidden="true"></i> Revoke links or burn after one download</li>
                <li><i class="fas fa-inbox" aria-hidden="true"></i> Collect files from clients (file requests)</li>
                <li><i class="fas fa-palette" aria-hidden="true"></i> Brand your public &amp; collect pages</li>
                <li><i class="fas fa-gauge-high" aria-hidden="true"></i> Higher limits (500 files · 10 GB · 30-day expiry)</li>
            </ul>
            <div class="level-up-actions">
                <a href="{{ route('auth.register') }}" class="modern-btn">
                    <i class="fas fa-user-plus" aria-hidden="true"></i>
                    Create free account
                </a>
                <button type="button" class="modern-btn secondary" id="levelUpLater">
                    Not now
                </button>
            </div>
            <p class="level-up-footnote">No credit card · Guest sharing stays free</p>
        </div>
    </div>

    <div class="limit-upsell" id="limitUpsell" hidden role="status">
        <div class="limit-upsell-inner">
            <i class="fas fa-gauge-high" aria-hidden="true"></i>
            <div class="limit-upsell-copy">
                <strong id="limitUpsellTitle">Guest limit reached</strong>
                <span id="limitUpsellText">Free accounts get 500 active files, 10 GB storage, and 30-day expiry.</span>
            </div>
            <a href="{{ route('auth.register') }}" class="modern-btn limit-upsell-cta">
                Unlock free
            </a>
            <button type="button" class="limit-upsell-dismiss" id="limitUpsellDismiss" aria-label="Dismiss">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <script>
        (function () {
            var WHAT_NEW_KEY = 'airtoshare_whats_new_v2026_10_saas';
            var LEVEL_UP_KEY = 'airtoshare_level_up_dismissed_v1';
            var LEVEL_UP_COOLDOWN_KEY = 'airtoshare_level_up_last_shown';
            var LEVEL_UP_COOLDOWN_MS = 48 * 60 * 60 * 1000; // 48h between auto shows

            function lsGet(key) {
                try { return localStorage.getItem(key); } catch (e) { return null; }
            }
            function lsSet(key, val) {
                try { localStorage.setItem(key, val); } catch (e) { /* ignore */ }
            }

            // —— What’s new banner ——
            var banner = document.getElementById('whatsNewBanner');
            var dismissBtn = document.getElementById('whatsNewDismiss');
            if (banner && !lsGet(WHAT_NEW_KEY)) {
                banner.hidden = false;
            }
            if (dismissBtn) {
                dismissBtn.addEventListener('click', function () {
                    lsSet(WHAT_NEW_KEY, '1');
                    if (banner) banner.hidden = true;
                });
            }

            // —— Level-up card ——
            var overlay = document.getElementById('levelUpOverlay');
            var closeBtn = document.getElementById('levelUpClose');
            var laterBtn = document.getElementById('levelUpLater');

            function hideLevelUp() {
                if (!overlay) return;
                overlay.hidden = true;
                document.body.classList.remove('level-up-open');
            }

            function dismissLevelUpForever() {
                lsSet(LEVEL_UP_KEY, '1');
                hideLevelUp();
            }

            function showLevelUp(force) {
                if (!overlay) return;
                if (!force && lsGet(LEVEL_UP_KEY)) return;
                if (!force) {
                    var last = parseInt(lsGet(LEVEL_UP_COOLDOWN_KEY) || '0', 10);
                    if (last && (Date.now() - last) < LEVEL_UP_COOLDOWN_MS) return;
                }
                overlay.hidden = false;
                document.body.classList.add('level-up-open');
                lsSet(LEVEL_UP_COOLDOWN_KEY, String(Date.now()));
            }

            if (closeBtn) closeBtn.addEventListener('click', dismissLevelUpForever);
            if (laterBtn) laterBtn.addEventListener('click', dismissLevelUpForever);
            if (overlay) {
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay) hideLevelUp();
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && !overlay.hidden) hideLevelUp();
                });
            }

            // —— Limit upsell strip ——
            var limitBox = document.getElementById('limitUpsell');
            var limitDismiss = document.getElementById('limitUpsellDismiss');
            var limitTitle = document.getElementById('limitUpsellTitle');
            var limitText = document.getElementById('limitUpsellText');

            function showLimitUpsell(title, text) {
                if (!limitBox) return;
                if (limitTitle && title) limitTitle.textContent = title;
                if (limitText && text) limitText.textContent = text;
                limitBox.hidden = false;
            }

            if (limitDismiss) {
                limitDismiss.addEventListener('click', function () {
                    if (limitBox) limitBox.hidden = true;
                });
            }

            function looksLikeLimitError(message) {
                if (!message) return false;
                var m = String(message).toLowerCase();
                return m.indexOf('limit') !== -1
                    || m.indexOf('maximum file') !== -1
                    || m.indexOf('storage') !== -1
                    || m.indexOf('too many') !== -1;
            }

            // Public API for home page / upload managers
            window.__airtoshareGuestDiscovery = {
                showLevelUp: showLevelUp,
                showLimitUpsell: showLimitUpsell,
                looksLikeLimitError: looksLikeLimitError,
                maybeLevelUpAfterSuccess: function () {
                    showLevelUp(false);
                },
                maybeLimitFromError: function (message) {
                    if (!looksLikeLimitError(message)) return;
                    showLimitUpsell(
                        'Guest limit reached',
                        'Free accounts get 500 active files, 10 GB storage, and up to 30-day expiry.'
                    );
                }
            };
        })();
    </script>
@endguest
