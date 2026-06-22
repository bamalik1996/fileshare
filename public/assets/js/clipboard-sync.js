/**
 * Room clipboard sync subscriber (Requirements 10.1, 10.3, 10.5).
 */
(function () {
    'use strict';

    var DEVICE_KEY = 'airtoshare_device_id';
    var presenceTimer = null;

    function meta(name) {
        var el = document.querySelector('meta[name="' + name + '"]');
        return el ? el.getAttribute('content') : '';
    }

    function deviceId() {
        try {
            var existing = localStorage.getItem(DEVICE_KEY);
            if (existing) return existing;
            var id = 'dev-' + Math.random().toString(36).slice(2) + Date.now().toString(36);
            localStorage.setItem(DEVICE_KEY, id);
            return id;
        } catch (e) {
            return 'dev-session-' + Date.now();
        }
    }

    function roomId() {
        var root = document.querySelector('[data-airtoshare-room-id]');
        return root ? root.getAttribute('data-airtoshare-room-id') : '';
    }

    function roomCode() {
        var root = document.querySelector('[data-airtoshare-room-code]');
        return root ? root.getAttribute('data-airtoshare-room-code') : '';
    }

    function textTarget() {
        return document.querySelector('[data-airtoshare-clipboard-target]')
            || document.querySelector('#sharedText')
            || document.querySelector('textarea[name="text"]');
    }

    function applyText(text) {
        var target = textTarget();
        if (!target) return;
        if ('value' in target) {
            target.value = text;
        } else {
            target.textContent = text;
        }
        target.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function postPresence(code) {
        return fetch('/api/v1/rooms/' + encodeURIComponent(code) + '/clipboard/presence', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': meta('csrf-token'),
                'X-Airtoshare-Device-Id': deviceId()
            },
            body: JSON.stringify({ device_id: deviceId() })
        });
    }

    function pushClipboard(code, text) {
        return fetch('/api/v1/rooms/' + encodeURIComponent(code) + '/clipboard', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': meta('csrf-token')
            },
            body: JSON.stringify({
                text: text,
                updated_at: new Date().toISOString()
            })
        });
    }

    function subscribeRoom(roomIdValue, code) {
        if (!window.Echo || !roomIdValue) return;

        window.Echo.private('room.' + roomIdValue + '.clipboard')
            .listen('.clipboard.updated', function (event) {
                if (event && typeof event.text === 'string') {
                    applyText(event.text);
                }
            });

        window.Echo.join('presence-room.' + roomIdValue);
    }

    function initEcho() {
        if (window.Echo || !window.Pusher) return false;
        var key = meta('airtoshare-reverb-key');
        if (!key) return false;
        window.Echo = new window.Echo({
            broadcaster: 'reverb',
            key: key,
            wsHost: meta('airtoshare-reverb-host') || window.location.hostname,
            wsPort: parseInt(meta('airtoshare-reverb-port') || '8080', 10),
            wssPort: parseInt(meta('airtoshare-reverb-port') || '8080', 10),
            forceTLS: (meta('airtoshare-reverb-scheme') || 'http') === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: { headers: { 'X-CSRF-TOKEN': meta('csrf-token') } }
        });
        return true;
    }

    function init() {
        var code = roomCode();
        var roomIdValue = roomId();
        if (!code || !roomIdValue) return;

        if (!initEcho()) return;

        subscribeRoom(roomIdValue, code);
        postPresence(code).catch(function () { /* non-blocking */ });

        if (presenceTimer) clearInterval(presenceTimer);
        presenceTimer = setInterval(function () {
            postPresence(code).catch(function () { /* ignore */ });
        }, 15000);

        document.addEventListener('airtoshare:clipboard-push', function (ev) {
            var detail = ev.detail || {};
            if (typeof detail.text === 'string') {
                pushClipboard(code, detail.text).catch(function () { /* ignore */ });
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.__airtoshareClipboardSync = {
        init: init,
        push: function (text) {
            document.dispatchEvent(new CustomEvent('airtoshare:clipboard-push', { detail: { text: text } }));
        }
    };
})();
