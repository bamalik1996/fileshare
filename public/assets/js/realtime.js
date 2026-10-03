/**
 * AirToShare Realtime client (Requirements 14.1, 14.5, 14.8, 14.9).
 *
 * Subscribes to `private-share.{id}` via Laravel Echo + Reverb when
 * Reverb credentials are present in `<meta>` tags and one or more share
 * ids are available on `[data-airtoshare-share-id]` (home page = one;
 * My Shares = one per card).
 */
(function () {
    'use strict';

    var MAX_ATTEMPTS = 10;
    var bannerId = 'airtoshare-realtime-banner';

    function meta(name) {
        var el = document.querySelector('meta[name="' + name + '"]');
        return el ? el.getAttribute('content') : '';
    }

    function readShareIds() {
        var nodes = document.querySelectorAll('[data-airtoshare-share-id]');
        var ids = [];
        var seen = {};
        for (var i = 0; i < nodes.length; i++) {
            var id = nodes[i].getAttribute('data-airtoshare-share-id');
            if (!id || seen[id]) continue;
            seen[id] = true;
            ids.push(id);
        }
        return ids;
    }

    function backoffMs(attempt) {
        return Math.min(30000, Math.pow(2, attempt) * 1000);
    }

    function ensureBanner() {
        var el = document.getElementById(bannerId);
        if (el) return el;
        el = document.createElement('div');
        el.id = bannerId;
        el.className = 'notification is-warning airtoshare-realtime-banner';
        el.style.cssText = 'position:fixed;bottom:1rem;left:1rem;right:1rem;z-index:9999;display:none;';
        el.textContent = 'Real-time updates unavailable.';
        document.body.appendChild(el);
        return el;
    }

    function showOfflineBanner(show) {
        var el = ensureBanner();
        el.style.display = show ? 'block' : 'none';
    }

    function fetchState(shareId) {
        return fetch('/api/v1/shares/' + encodeURIComponent(shareId) + '/state', {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        }).then(function (r) { return r.json(); });
    }

    function reconcileDom(payload) {
        document.dispatchEvent(new CustomEvent('airtoshare:state', { detail: payload }));
        var lenEl = document.querySelector('[data-airtoshare-text-length]');
        if (lenEl && typeof payload.text_length === 'number') {
            lenEl.textContent = String(payload.text_length);
        }
    }

    function subscribeShare(shareId, options) {
        if (!getEcho()) return null;
        options = options || {};

        var attempt = 0;
        var channel = null;

        function connect() {
            try {
                channel = getEcho().private('share.' + shareId);
                channel.listen('.media.added', function (e) {
                    var detail = e || {};
                    if (detail.share_id == null) {
                        detail.share_id = shareId;
                    }
                    document.dispatchEvent(new CustomEvent('airtoshare:media.added', { detail: detail }));
                });
                channel.listen('.media.deleted', function (e) {
                    var detail = e || {};
                    if (detail.share_id == null) {
                        detail.share_id = shareId;
                    }
                    document.dispatchEvent(new CustomEvent('airtoshare:media.deleted', { detail: detail }));
                });
                channel.listen('.text.updated', function (e) {
                    var detail = e || {};
                    if (detail.share_id == null) {
                        detail.share_id = shareId;
                    }
                    document.dispatchEvent(new CustomEvent('airtoshare:text.updated', { detail: detail }));
                });
                showOfflineBanner(false);
                if (!options.skipReconcile) {
                    fetchState(shareId).then(reconcileDom).catch(function () { /* ignore */ });
                }
            } catch (err) {
                scheduleReconnect();
            }
        }

        function scheduleReconnect() {
            if (attempt >= MAX_ATTEMPTS) {
                showOfflineBanner(true);
                return;
            }
            var wait = backoffMs(attempt);
            attempt++;
            setTimeout(connect, wait);
        }

        connect();
        return channel;
    }

    var echoInstance = null;

    function getEcho() {
        return echoInstance;
    }

    function initEcho(cfg) {
        if (echoInstance) return true;
        if (!window.Pusher) return false;

        var EchoCtor = (typeof window.Echo === 'function') ? window.Echo : null;
        if (!EchoCtor && typeof Echo === 'function') EchoCtor = Echo;
        if (!EchoCtor) return false;

        echoInstance = new EchoCtor({
            broadcaster: 'reverb',
            key: cfg.key,
            wsHost: cfg.host,
            wsPort: cfg.port,
            wssPort: cfg.port,
            forceTLS: cfg.scheme === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': meta('csrf-token')
                }
            }
        });
        return true;
    }

    function encodeIpForChannel(ip) {
        return ip.replace(/\./g, '-').replace(/:/g, '_');
    }

    function init() {
        var shareIds = readShareIds();
        var key = meta('airtoshare-reverb-key');
        if (!shareIds.length || !key) return;

        var cfg = {
            key: key,
            host: meta('airtoshare-reverb-host') || window.location.hostname,
            port: parseInt(meta('airtoshare-reverb-port') || '8080', 10),
            scheme: meta('airtoshare-reverb-scheme') || 'http'
        };

        if (!initEcho(cfg)) {
            return;
        }

        var multi = shareIds.length > 1;
        for (var i = 0; i < shareIds.length; i++) {
            subscribeShare(shareIds[i], { skipReconcile: multi });
        }

        var ip = meta('airtoshare-owner-ip');
        if (ip && getEcho()) {
            getEcho().private('ip.' + encodeIpForChannel(ip)).listen('.share.expiry_reminder', function (e) {
                if (typeof window.showToast === 'function') {
                    window.showToast('info', 'Expiry reminder', e.message || 'Your share is expiring soon.');
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.__airtoshareRealtime = { init: init, backoffMs: backoffMs };
})();
