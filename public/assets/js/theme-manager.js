/**
 * AirToShare Theme Manager
 * Implements Requirement 4 acceptance criteria 4.1, 4.2, 4.5, 4.9, 4.10.
 *
 * The pre-paint bootstrap in <head> already resolves the active theme and
 * writes it to <html data-theme="..."> before first paint (Reqs 4.3, 4.4,
 * 4.6, 4.7, 4.8). This module runs after the page has been styled and:
 *
 *   - Binds the toggle control on every page so users can switch between
 *     "light" and "dark" (Req 4.1).
 *   - Switches the active theme within 100 ms of activation (Req 4.2).
 *   - Persists the chosen theme as "light" or "dark" in
 *     localStorage["airtoshare_theme"] (Req 4.5).
 *   - Runs a runtime contrast self-check while in dark mode and disables
 *     the dark option in the toggle if any sampled text element fails
 *     WCAG 2.1 AA contrast (Reqs 4.9, 4.10). When dark is disabled by the
 *     check, the page is forced back to the light theme regardless of the
 *     stored value.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'airtoshare_theme';
    var DISABLED_KEY = 'airtoshare_dark_disabled';
    var TOGGLE_SELECTOR = '#themeToggle';
    var ICON_SELECTOR = '#themeIcon';

    // ---------------------------------------------------------------------
    // localStorage helpers — failures must never block rendering (Req 4.7).
    // ---------------------------------------------------------------------
    function readLs(key) {
        try {
            return window.localStorage.getItem(key);
        } catch (e) {
            return null;
        }
    }

    function writeLs(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch (e) {
            /* swallow — Req 4.7 */
        }
    }

    function isDarkDisabled() {
        return readLs(DISABLED_KEY) === '1';
    }

    // ---------------------------------------------------------------------
    // Theme application
    // ---------------------------------------------------------------------
    function currentTheme() {
        return document.documentElement.getAttribute('data-theme') === 'dark'
            ? 'dark'
            : 'light';
    }

    function applyTheme(theme) {
        var t = theme === 'dark' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', t);
        return t;
    }

    // ---------------------------------------------------------------------
    // Contrast self-check (WCAG 2.1 AA — Reqs 4.9, 4.10)
    // ---------------------------------------------------------------------
    function parseColor(value) {
        if (!value) return null;
        var m = value.match(
            /^rgba?\(\s*(\d+(?:\.\d+)?)\s*,?\s*(\d+(?:\.\d+)?)\s*,?\s*(\d+(?:\.\d+)?)\s*(?:,?\s*(\d+(?:\.\d+)?))?\s*\)/
        );
        if (!m) return null;
        return {
            r: parseFloat(m[1]),
            g: parseFloat(m[2]),
            b: parseFloat(m[3]),
            a: m[4] !== undefined ? parseFloat(m[4]) : 1
        };
    }

    function effectiveBackground(el) {
        var cur = el;
        while (cur && cur.nodeType === 1) {
            var style = window.getComputedStyle(cur);
            var col = parseColor(style.backgroundColor);
            if (col && col.a > 0) {
                return col;
            }
            cur = cur.parentElement;
        }
        // Fall back to body or html background; default to white if undetectable.
        var bodyCol = parseColor(window.getComputedStyle(document.body).backgroundColor);
        if (bodyCol && bodyCol.a > 0) return bodyCol;
        return { r: 255, g: 255, b: 255, a: 1 };
    }

    function relativeLuminance(c) {
        function chan(v) {
            v = v / 255;
            return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
        }
        return 0.2126 * chan(c.r) + 0.7152 * chan(c.g) + 0.0722 * chan(c.b);
    }

    function contrastRatio(fg, bg) {
        var l1 = relativeLuminance(fg);
        var l2 = relativeLuminance(bg);
        var lighter = Math.max(l1, l2);
        var darker = Math.min(l1, l2);
        return (lighter + 0.05) / (darker + 0.05);
    }

    // WCAG 2.1: large text is >= 18pt (24px) regular OR >= 14pt (18.66px) bold.
    function isLargeText(style) {
        var px = parseFloat(style.fontSize) || 16;
        var weight = parseInt(style.fontWeight, 10) || 400;
        if (px >= 24) return true;
        if (px >= 18.66 && weight >= 700) return true;
        return false;
    }

    function hasOwnTextNode(el) {
        for (var i = 0; i < el.childNodes.length; i++) {
            var n = el.childNodes[i];
            if (n.nodeType === 3 && n.nodeValue && n.nodeValue.trim().length > 0) {
                return true;
            }
        }
        return false;
    }

    function isVisible(el, style) {
        if (style.visibility === 'hidden' || style.display === 'none') return false;
        if (parseFloat(style.opacity) === 0) return false;
        var rect = el.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0;
    }

    /**
     * Sample text elements and verify they meet WCAG 2.1 AA contrast.
     * Returns true when every sampled element passes, false on the first
     * failure. The sample is bounded so the work stays trivial even on
     * large pages.
     */
    function runContrastCheck() {
        var selectors =
            'p, a, h1, h2, h3, h4, h5, h6, span, li, button, label, ' +
            'input, textarea, td, th, dt, dd, summary, figcaption';
        var nodes = document.querySelectorAll(selectors);
        var sampled = 0;
        var maxSamples = 150;

        for (var i = 0; i < nodes.length && sampled < maxSamples; i++) {
            var el = nodes[i];
            if (!hasOwnTextNode(el)) continue;

            var style = window.getComputedStyle(el);
            if (!isVisible(el, style)) continue;

            var fg = parseColor(style.color);
            if (!fg || fg.a === 0) continue;
            var bg = effectiveBackground(el);

            var ratio = contrastRatio(fg, bg);
            var threshold = isLargeText(style) ? 3 : 4.5;
            if (ratio + 1e-3 < threshold) {
                return false;
            }
            sampled++;
        }
        return true;
    }

    // ---------------------------------------------------------------------
    // Toggle binding (Reqs 4.1, 4.2, 4.5, 4.10)
    // ---------------------------------------------------------------------
    function findToggle() {
        return document.querySelector(TOGGLE_SELECTOR);
    }

    function updateToggleUi(theme, darkDisabled) {
        var btn = findToggle();
        if (!btn) return;

        var icon = btn.querySelector(ICON_SELECTOR) || document.querySelector(ICON_SELECTOR);
        if (icon) {
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }

        if (darkDisabled) {
            btn.setAttribute('disabled', 'disabled');
            btn.setAttribute('aria-disabled', 'true');
            btn.setAttribute(
                'title',
                'Dark mode disabled because the dark palette failed the contrast check.'
            );
            btn.classList.add('is-dark-disabled');
        } else {
            btn.removeAttribute('disabled');
            btn.removeAttribute('aria-disabled');
            btn.setAttribute('title', 'Toggle Dark Mode');
            btn.classList.remove('is-dark-disabled');
        }
    }

    function onToggleActivate(event) {
        if (event) event.preventDefault();

        if (isDarkDisabled()) {
            // Req 4.10: dark option is disabled; force light and persist.
            applyTheme('light');
            writeLs(STORAGE_KEY, 'light');
            updateToggleUi('light', true);
            return;
        }

        var next = currentTheme() === 'dark' ? 'light' : 'dark';
        applyTheme(next);
        writeLs(STORAGE_KEY, next);
        updateToggleUi(next, false);
    }

    function bindToggle() {
        var btn = findToggle();
        if (!btn) return;
        if (btn.dataset.themeManagerBound === '1') return;
        btn.dataset.themeManagerBound = '1';
        btn.addEventListener('click', onToggleActivate);
        btn.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {
                onToggleActivate(e);
            }
        });
    }

    // ---------------------------------------------------------------------
    // Boot
    // ---------------------------------------------------------------------
    function init() {
        // If a previous run already detected a contrast failure, the dark
        // option stays disabled and the page is forced light regardless of
        // the stored theme (Req 4.10).
        if (isDarkDisabled()) {
            applyTheme('light');
            writeLs(STORAGE_KEY, 'light');
            bindToggle();
            updateToggleUi('light', true);
            return;
        }

        bindToggle();
        updateToggleUi(currentTheme(), false);

        // Only the dark palette is interesting for the contrast check.
        if (currentTheme() !== 'dark') return;

        // Defer two animation frames so the dark CSS has been applied and
        // computed styles reflect the dark palette.
        var raf =
            window.requestAnimationFrame ||
            function (cb) {
                return window.setTimeout(cb, 16);
            };

        raf(function () {
            raf(function () {
                var passed = true;
                try {
                    passed = runContrastCheck();
                } catch (e) {
                    // If the check itself blows up, do not punish the user;
                    // leave the theme alone (Req 4.7 spirit).
                    passed = true;
                }
                if (!passed) {
                    writeLs(DISABLED_KEY, '1');
                    applyTheme('light');
                    writeLs(STORAGE_KEY, 'light');
                    updateToggleUi('light', true);
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose a tiny test surface so unit/property tests can exercise the
    // pure helpers in isolation. Hidden behind a namespace; not part of
    // the public API.
    window.__airtoshareThemeManager = {
        contrastRatio: contrastRatio,
        relativeLuminance: relativeLuminance,
        isLargeText: isLargeText,
        runContrastCheck: runContrastCheck,
        STORAGE_KEY: STORAGE_KEY,
        DISABLED_KEY: DISABLED_KEY
    };
})();
