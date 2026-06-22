/**
 * AirToShare Clipboard Component
 * Implements Requirement 5 acceptance criteria 5.2, 5.3, 5.4, 5.5, 5.6.
 *
 * Data-attribute driven so it can be wired to any "Copy" button in the
 * page without bespoke per-page JavaScript:
 *
 *   <button data-copy="#textInput">Copy</button>
 *       Copies the value/textContent of the element matching the
 *       selector in `data-copy`.
 *
 *   <button data-copy-text="hello world">Copy</button>
 *       Copies the literal string in `data-copy-text` (overrides
 *       `data-copy`).
 *
 * Behaviour summary:
 *
 *   - Activation by mouse click or by Enter / Space while the button
 *     has keyboard focus (Req 5.2). For native <button> elements the
 *     browser already synthesises a click for Enter and Space, so this
 *     module only adds an explicit keydown handler for non-button
 *     elements that opt in to `data-copy`.
 *   - While a copy is in flight the button is disabled and further
 *     activations are ignored (Req 5.3). A `data-copy-state="copying"`
 *     attribute is set so callers / tests can observe the state.
 *   - On success a confirmation indicator (<span class="clipboard-confirm">)
 *     is appended to the button and the button is given the
 *     `confirm-2s` class. Both are removed after 2.5 seconds, which is
 *     inside the 2-5 second window required by Req 5.4.
 *   - Tri-strategy chain (Reqs 5.2, 5.5, 5.6):
 *       1. `navigator.clipboard.writeText` (Async Clipboard API)
 *       2. Hidden <textarea> + `document.execCommand('copy')`
 *       3. Persistent, user-dismissable error banner that survives
 *          subsequent activations until the user dismisses it or
 *          navigates away from the page.
 */
(function () {
    'use strict';

    var CONFIRM_MS = 2500;                         // 2.5s — inside the 2-5s window (Req 5.4)
    var CONFIRM_CLASS = 'confirm-2s';
    var ERROR_BANNER_ID = 'airtoshare-clipboard-error';
    var STYLE_TAG_ID = 'airtoshare-clipboard-styles';

    // ---------------------------------------------------------------------
    // Inline baseline styles. These are deliberately minimal so the
    // confirmation indicator and persistent error banner remain visible
    // without requiring application CSS changes; the existing
    // `custom.css` may override them later.
    // ---------------------------------------------------------------------
    function ensureStyles() {
        if (document.getElementById(STYLE_TAG_ID)) return;
        var style = document.createElement('style');
        style.id = STYLE_TAG_ID;
        style.textContent = [
            '.clipboard-confirm{',
            '  display:inline-block;margin-left:.5rem;padding:.1rem .45rem;',
            '  border-radius:.25rem;background:#16a34a;color:#fff;',
            '  font-size:.75rem;font-weight:600;line-height:1.2;',
            '  vertical-align:middle;',
            '}',
            '.confirm-2s{position:relative;}',
            '#' + ERROR_BANNER_ID + '{',
            '  position:fixed;top:0;left:0;right:0;z-index:2147483646;',
            '  display:flex;align-items:center;justify-content:space-between;',
            '  gap:1rem;padding:.75rem 1rem;background:#b91c1c;color:#fff;',
            '  font-size:.95rem;font-weight:500;box-shadow:0 2px 6px rgba(0,0,0,.25);',
            '}',
            '#' + ERROR_BANNER_ID + ' .clipboard-error-banner__close{',
            '  background:transparent;border:0;color:inherit;font-size:1.25rem;',
            '  font-weight:700;cursor:pointer;line-height:1;padding:.25rem .5rem;',
            '}',
            '#' + ERROR_BANNER_ID + ' .clipboard-error-banner__close:hover{',
            '  text-decoration:underline;',
            '}'
        ].join('');
        (document.head || document.documentElement).appendChild(style);
    }

    // ---------------------------------------------------------------------
    // Text resolution: data-copy-text overrides; otherwise read from the
    // element matching the selector in data-copy.
    // ---------------------------------------------------------------------
    function resolveText(button) {
        if (button.dataset.copyText !== undefined) {
            return String(button.dataset.copyText);
        }
        var sel = button.dataset.copy;
        if (!sel) return '';
        var el = null;
        try {
            el = document.querySelector(sel);
        } catch (e) {
            return '';
        }
        if (!el) return '';
        var tag = el.tagName;
        if (tag === 'TEXTAREA' || tag === 'INPUT') {
            return String(el.value == null ? '' : el.value);
        }
        // Prefer textContent so the copied payload mirrors what the user
        // sees, with whitespace preserved verbatim.
        return String(el.textContent == null ? '' : el.textContent);
    }

    // ---------------------------------------------------------------------
    // Strategy 1: Async Clipboard API (Req 5.2).
    // ---------------------------------------------------------------------
    function strategyClipboardApi(text) {
        try {
            if (
                typeof navigator === 'undefined' ||
                !navigator.clipboard ||
                typeof navigator.clipboard.writeText !== 'function'
            ) {
                return Promise.reject(new Error('clipboard-api-unavailable'));
            }
            return Promise.resolve(navigator.clipboard.writeText(text));
        } catch (e) {
            return Promise.reject(e);
        }
    }

    // ---------------------------------------------------------------------
    // Strategy 2: hidden <textarea> + document.execCommand('copy') (Req 5.5).
    // ---------------------------------------------------------------------
    function strategyExecCommand(text) {
        return new Promise(function (resolve, reject) {
            var ta;
            var prevActive = document.activeElement;
            var prevRange = null;
            try {
                ta = document.createElement('textarea');
                ta.value = text;
                ta.setAttribute('readonly', 'readonly');
                ta.setAttribute('aria-hidden', 'true');
                // Position offscreen but still selectable; visibility:hidden
                // would prevent selection, and display:none would too.
                ta.style.position = 'fixed';
                ta.style.top = '0';
                ta.style.left = '0';
                ta.style.width = '1px';
                ta.style.height = '1px';
                ta.style.padding = '0';
                ta.style.border = '0';
                ta.style.outline = 'none';
                ta.style.boxShadow = 'none';
                ta.style.background = 'transparent';
                ta.style.opacity = '0';
                ta.style.pointerEvents = 'none';
                document.body.appendChild(ta);

                var sel = document.getSelection ? document.getSelection() : null;
                if (sel && sel.rangeCount > 0) {
                    try { prevRange = sel.getRangeAt(0); } catch (e) { prevRange = null; }
                }

                ta.focus();
                ta.select();
                if (typeof ta.setSelectionRange === 'function') {
                    ta.setSelectionRange(0, ta.value.length);
                }

                var ok = false;
                try {
                    ok = document.execCommand && document.execCommand('copy');
                } catch (e) {
                    ok = false;
                }

                if (ok) {
                    resolve();
                } else {
                    reject(new Error('execCommand-failed'));
                }
            } catch (err) {
                reject(err);
            } finally {
                if (ta && ta.parentNode) {
                    try { ta.parentNode.removeChild(ta); } catch (e) { /* ignore */ }
                }
                if (prevRange) {
                    try {
                        var sel2 = document.getSelection();
                        if (sel2) {
                            sel2.removeAllRanges();
                            sel2.addRange(prevRange);
                        }
                    } catch (e) { /* ignore */ }
                }
                if (prevActive && typeof prevActive.focus === 'function') {
                    try { prevActive.focus(); } catch (e) { /* ignore */ }
                }
            }
        });
    }

    // ---------------------------------------------------------------------
    // Confirmation indicator (Req 5.4) — visible for 2.5 s, in the 2-5 s
    // window required by the spec.
    // ---------------------------------------------------------------------
    function showConfirmIndicator(button) {
        ensureStyles();

        // Replace any existing indicator from a previous copy on this button.
        var existing = button.querySelector('.clipboard-confirm');
        if (existing && existing.parentNode === button) {
            try { button.removeChild(existing); } catch (e) { /* ignore */ }
        }
        button.classList.add(CONFIRM_CLASS);

        var span = document.createElement('span');
        span.className = 'clipboard-confirm';
        span.setAttribute('role', 'status');
        span.setAttribute('aria-live', 'polite');
        span.textContent = 'Copied';
        button.appendChild(span);

        // Stash the timer so a rapid second activation can replace it
        // cleanly instead of accumulating timeouts.
        if (button.__clipboardConfirmTimer) {
            try { window.clearTimeout(button.__clipboardConfirmTimer); }
            catch (e) { /* ignore */ }
        }
        button.__clipboardConfirmTimer = window.setTimeout(function () {
            try {
                button.classList.remove(CONFIRM_CLASS);
                if (span.parentNode === button) {
                    button.removeChild(span);
                }
            } catch (e) { /* ignore */ }
            button.__clipboardConfirmTimer = null;
        }, CONFIRM_MS);
    }

    // ---------------------------------------------------------------------
    // Persistent error banner (Req 5.6) — only one at a time; remains
    // until dismissed or the page is navigated away from.
    // ---------------------------------------------------------------------
    function showErrorBanner() {
        ensureStyles();

        if (document.getElementById(ERROR_BANNER_ID)) return;

        var banner = document.createElement('div');
        banner.id = ERROR_BANNER_ID;
        banner.className = 'clipboard-error-banner';
        banner.setAttribute('role', 'alert');
        banner.setAttribute('aria-live', 'assertive');

        var msg = document.createElement('span');
        msg.className = 'clipboard-error-banner__message';
        msg.textContent =
            'Copy failed. Your browser blocked clipboard access. ' +
            'Please select the text and copy manually.';

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'clipboard-error-banner__close';
        close.setAttribute('aria-label', 'Dismiss');
        close.textContent = '\u00d7'; // ×
        close.addEventListener('click', function () {
            if (banner.parentNode) {
                try { banner.parentNode.removeChild(banner); }
                catch (e) { /* ignore */ }
            }
        });

        banner.appendChild(msg);
        banner.appendChild(close);

        // Place at the very top of <body> so it is visible regardless of
        // the page's layout.
        if (document.body.firstChild) {
            document.body.insertBefore(banner, document.body.firstChild);
        } else {
            document.body.appendChild(banner);
        }
    }

    // ---------------------------------------------------------------------
    // Per-button copy operation orchestrator.
    // ---------------------------------------------------------------------
    function performCopy(button) {
        // Req 5.3: ignore activations while a copy is in flight.
        if (button.dataset.copyState === 'copying' || button.disabled) return;

        var text = resolveText(button);

        // Lock button.
        var prevDisabled = button.disabled;
        button.dataset.copyState = 'copying';
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');

        function unlock() {
            button.removeAttribute('aria-busy');
            button.dataset.copyState = '';
            button.disabled = prevDisabled;
        }

        function dispatch(name, detail) {
            try {
                var ev;
                if (typeof CustomEvent === 'function') {
                    ev = new CustomEvent(name, { bubbles: true, cancelable: false, detail: detail });
                } else {
                    ev = document.createEvent('CustomEvent');
                    ev.initCustomEvent(name, true, false, detail);
                }
                button.dispatchEvent(ev);
            } catch (e) { /* never let analytics break clipboard */ }
        }

        function onSuccess(strategy) {
            unlock();
            showConfirmIndicator(button);                    // Req 5.4
            dispatch('clipboard:copied', { strategy: strategy, length: text.length });
        }

        function onFailure(reason) {
            unlock();
            showErrorBanner();                               // Req 5.6
            dispatch('clipboard:failed', { reason: String(reason && reason.message || reason) });
        }

        // Strategy 1 -> Strategy 2 -> banner.
        strategyClipboardApi(text).then(
            function () { onSuccess('clipboard-api'); },
            function () {
                strategyExecCommand(text).then(
                    function () { onSuccess('exec-command'); },
                    function (err) { onFailure(err); }
                );
            }
        );
    }

    // ---------------------------------------------------------------------
    // Event delegation: catch activations on any [data-copy] / [data-copy-text]
    // element, no matter where in the document it lives.
    // ---------------------------------------------------------------------
    function findCopyTarget(node) {
        var cur = node;
        while (cur && cur.nodeType === 1) {
            if (
                cur.hasAttribute('data-copy') ||
                cur.hasAttribute('data-copy-text')
            ) {
                return cur;
            }
            cur = cur.parentElement;
        }
        return null;
    }

    function onClick(e) {
        var btn = findCopyTarget(e.target);
        if (!btn) return;
        // Anchors and form submitters in particular need their default
        // suppressed so the page does not navigate or submit on copy.
        if (typeof e.preventDefault === 'function') e.preventDefault();
        performCopy(btn);
    }

    function onKeydown(e) {
        // Req 5.2: Enter and Space activate the copy.
        // Native <button> already synthesises a click for both, so we
        // only need to handle non-button copy targets to avoid firing
        // the operation twice.
        if (e.key !== 'Enter' && e.key !== ' ' && e.key !== 'Spacebar') return;
        var btn = findCopyTarget(e.target);
        if (!btn) return;
        if (btn.tagName === 'BUTTON') return;
        if (typeof e.preventDefault === 'function') e.preventDefault();
        performCopy(btn);
    }

    function init() {
        ensureStyles();
        document.addEventListener('click', onClick);
        document.addEventListener('keydown', onKeydown);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Tiny test surface for unit/property tests. Hidden behind an
    // underscored namespace; not part of the public API.
    window.__airtoshareClipboard = {
        resolveText: resolveText,
        performCopy: performCopy,
        showConfirmIndicator: showConfirmIndicator,
        showErrorBanner: showErrorBanner,
        findCopyTarget: findCopyTarget,
        CONFIRM_MS: CONFIRM_MS,
        CONFIRM_CLASS: CONFIRM_CLASS,
        ERROR_BANNER_ID: ERROR_BANNER_ID
    };
})();
