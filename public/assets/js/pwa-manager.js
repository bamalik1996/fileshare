/**
 * PWA registration, update prompt, offline banner (Requirement 19).
 */
(function () {
    'use strict';

    var SW_URL = '/sw.js';
    var bannerId = 'airtoshare-pwa-update-banner';
    var offlineId = 'airtoshare-offline-banner';

    function ensureBanner(id, text, className) {
        var el = document.getElementById(id);
        if (el) return el;
        el = document.createElement('div');
        el.id = id;
        el.className = className || 'notification is-info';
        el.style.cssText = 'position:fixed;top:1rem;left:1rem;right:1rem;z-index:9998;display:none;padding:0.75rem 1rem;';
        el.textContent = text;
        document.body.appendChild(el);
        return el;
    }

    function registerSw() {
        if (!('serviceWorker' in navigator)) return;
        navigator.serviceWorker.register(SW_URL, { scope: '/' }).catch(function () { /* non-blocking */ });
    }

    function setupUpdatePrompt() {
        if (!('serviceWorker' in navigator)) return;
        var refreshing = false;
        navigator.serviceWorker.addEventListener('controllerchange', function () {
            if (refreshing) return;
            var banner = ensureBanner(bannerId, 'Update available.', 'notification is-warning');
            banner.innerHTML = 'Update available. <button type="button" class="modern-btn is-small" id="airtoshare-pwa-reload">Reload</button>';
            banner.style.display = 'block';
            document.getElementById('airtoshare-pwa-reload').addEventListener('click', function () {
                refreshing = true;
                window.location.reload();
            });
        });
    }

    function setupOfflineBanner() {
        var timer = null;
        var banner = ensureBanner(offlineId, 'You are offline.', 'notification is-warning');
        function show(state) {
            clearTimeout(timer);
            timer = setTimeout(function () {
                banner.style.display = state ? 'block' : 'none';
            }, 2000);
        }
        window.addEventListener('online', function () { show(false); });
        window.addEventListener('offline', function () { show(true); });
        if (!navigator.onLine) show(true);
    }

    function init() {
        registerSw();
        setupUpdatePrompt();
        setupOfflineBanner();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
