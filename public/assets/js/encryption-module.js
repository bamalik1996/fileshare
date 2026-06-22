/**
 * AirToShare E2EE module (Requirement 15).
 * Key lives only in URL fragment (#k=...) — never sent to server.
 */
(function () {
    'use strict';

    var FRAGMENT_PREFIX = 'k=';
    var CHUNK_SIZE = 5 * 1024 * 1024;

    function toBase64Url(buffer) {
        var bytes = new Uint8Array(buffer);
        var bin = '';
        for (var i = 0; i < bytes.length; i++) bin += String.fromCharCode(bytes[i]);
        return btoa(bin).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    function fromBase64Url(str) {
        var b64 = str.replace(/-/g, '+').replace(/_/g, '/');
        while (b64.length % 4) b64 += '=';
        var bin = atob(b64);
        var out = new Uint8Array(bin.length);
        for (var i = 0; i < bin.length; i++) out[i] = bin.charCodeAt(i);
        return out.buffer;
    }

    function parseFragmentKey() {
        var hash = window.location.hash.replace(/^#/, '');
        if (!hash.startsWith(FRAGMENT_PREFIX)) return null;
        try {
            var raw = fromBase64Url(hash.slice(FRAGMENT_PREFIX.length));
            if (raw.byteLength !== 32) return null;
            return raw;
        } catch (e) {
            return null;
        }
    }

    function placeKeyInFragment(keyBuffer) {
        var url = window.location.pathname + window.location.search + '#' + FRAGMENT_PREFIX + toBase64Url(keyBuffer);
        history.replaceState(null, '', url);
    }

    function assertCryptoAvailable() {
        if (typeof window !== 'undefined' && window.isSecureContext === false) {
            throw new Error(
                'E2EE requires HTTPS (or localhost). Open this site with https:// or use http://127.0.0.1 instead of a plain HTTP domain.'
            );
        }
        if (!window.crypto || !window.crypto.subtle) {
            throw new Error('Web Crypto (crypto.subtle) is not available in this browser.');
        }
    }

    function isSupported() {
        try {
            assertCryptoAvailable();
            return true;
        } catch (e) {
            return false;
        }
    }

    function supportMessage() {
        try {
            assertCryptoAvailable();
            return '';
        } catch (e) {
            return e && e.message ? e.message : 'E2EE is not available in this browser.';
        }
    }

    async function generateKey() {
        assertCryptoAvailable();
        return crypto.subtle.generateKey({ name: 'AES-GCM', length: 256 }, true, ['encrypt', 'decrypt']);
    }

    async function exportRawKey(cryptoKey) {
        return crypto.subtle.exportKey('raw', cryptoKey);
    }

    async function importRawKey(raw) {
        return crypto.subtle.importKey('raw', raw, { name: 'AES-GCM', length: 256 }, false, ['encrypt', 'decrypt']);
    }

    async function encryptBlob(cryptoKey, plaintext) {
        var iv = crypto.getRandomValues(new Uint8Array(12));
        var cipher = await crypto.subtle.encrypt({ name: 'AES-GCM', iv: iv }, cryptoKey, plaintext);
        return { iv: iv, ciphertext: new Uint8Array(cipher) };
    }

    async function decryptBlob(cryptoKey, iv, ciphertext) {
        try {
            var plain = await crypto.subtle.decrypt({ name: 'AES-GCM', iv: iv }, cryptoKey, ciphertext);
            return new Uint8Array(plain);
        } catch (e) {
            return null;
        }
    }

    async function ensureShareKey() {
        assertCryptoAvailable();
        var existing = parseFragmentKey();
        if (existing) return importRawKey(existing);
        var key = await generateKey();
        var raw = await exportRawKey(key);
        placeKeyInFragment(raw);
        return key;
    }

    function wipeBuffer(buf) {
        if (buf instanceof Uint8Array) buf.fill(0);
    }

    window.__airtoshareE2ee = {
        parseFragmentKey: parseFragmentKey,
        ensureShareKey: ensureShareKey,
        encryptBlob: encryptBlob,
        decryptBlob: decryptBlob,
        wipeBuffer: wipeBuffer,
        isSupported: isSupported,
        supportMessage: supportMessage,
        CHUNK_SIZE: CHUNK_SIZE,
        isEnabled: function () {
            return document.querySelector('[data-airtoshare-e2ee="1"]') !== null;
        }
    };
})();
