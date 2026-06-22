/**
 * AirToShare Rich Text Editor
 * Implements Requirement 12 acceptance criteria 12.2, 12.3, 12.4, 12.5, 12.9, 12.10.
 *
 * Data-attribute driven. The smallest valid markup is:
 *
 *   <div data-rich-editor>
 *     <div data-rich-toolbar></div>
 *     <textarea data-rich-source></textarea>
 *     <div data-rich-preview></div>
 *     <div data-rich-error hidden></div>
 *   </div>
 *
 * Behaviour summary:
 *
 *   - Toolbar controls for bold, italic, H1-H3, unordered list, ordered
 *     list, inline code, and fenced code block (Req 12.2).
 *   - When a non-empty selection exists, the control wraps the selected
 *     text with the corresponding Markdown syntax (Req 12.3).
 *   - When no selection exists, the control inserts the syntax at the
 *     cursor and places the cursor between the inserted markers (Req 12.4).
 *   - Live preview rendered by `marked` (CDN-loaded); debounce 200 ms for
 *     sources ≤ 50,000 chars, 1000 ms for larger sources (Req 12.5).
 *   - On paste: reads `text/html`, converts via Turndown to Markdown if
 *     available; falls back to `text/plain` (Req 12.9).
 *   - Enforces 500,000-char limit on input and paste; excess is rejected
 *     and an error message is shown (Req 12.10).
 */
(function () {
    'use strict';

    // ------------------------------------------------------------------
    // Constants
    // ------------------------------------------------------------------
    var MAX_CHARS      = 500000;          // Req 12.8, 12.10
    var DEBOUNCE_SMALL = 200;             // ms — sources ≤ 50,000 chars (Req 12.5)
    var DEBOUNCE_LARGE = 1000;            // ms — sources > 50,000 chars (Req 12.5)
    var SMALL_THRESHOLD = 50000;          // boundary between the two debounce tiers

    var STYLE_TAG_ID = 'airtoshare-rich-editor-styles';

    // ------------------------------------------------------------------
    // Toolbar button definitions
    //
    // Each entry describes one button.
    //   label   – accessible label / tooltip
    //   icon    – FontAwesome class string (fa-*)
    //   wrap    – function(selected: string, hasSelection: bool) → {before, after, placeholder}
    //             `before` and `after` are the syntax halves placed around the text.
    //             `placeholder` is inserted between them when there is no selection.
    // ------------------------------------------------------------------
    var TOOLBAR_BUTTONS = [
        {
            label: 'Bold',
            icon:  'fas fa-bold',
            wrap: function () {
                return { before: '**', after: '**', placeholder: 'bold text' };
            }
        },
        {
            label: 'Italic',
            icon:  'fas fa-italic',
            wrap: function () {
                return { before: '*', after: '*', placeholder: 'italic text' };
            }
        },
        {
            label: 'Heading 1',
            icon:  'fas fa-heading',
            className: 'h1-btn',
            wrap: function (selected, hasSelection) {
                // Block-level: works best on its own line; we prepend the
                // marker regardless and use a newline suffix only when
                // no selection exists.
                return {
                    before: '# ',
                    after: '',
                    placeholder: 'Heading 1'
                };
            }
        },
        {
            label: 'Heading 2',
            icon:  'fas fa-heading',
            className: 'h2-btn',
            wrap: function () {
                return { before: '## ', after: '', placeholder: 'Heading 2' };
            }
        },
        {
            label: 'Heading 3',
            icon:  'fas fa-heading',
            className: 'h3-btn',
            wrap: function () {
                return { before: '### ', after: '', placeholder: 'Heading 3' };
            }
        },
        {
            label: 'Unordered list',
            icon:  'fas fa-list-ul',
            wrap: function () {
                return { before: '- ', after: '', placeholder: 'list item' };
            }
        },
        {
            label: 'Ordered list',
            icon:  'fas fa-list-ol',
            wrap: function () {
                return { before: '1. ', after: '', placeholder: 'list item' };
            }
        },
        {
            label: 'Inline code',
            icon:  'fas fa-code',
            wrap: function () {
                return { before: '`', after: '`', placeholder: 'code' };
            }
        },
        {
            label: 'Fenced code block',
            icon:  'fas fa-file-code',
            wrap: function () {
                return { before: '```\n', after: '\n```', placeholder: 'code' };
            }
        }
    ];

    // ------------------------------------------------------------------
    // Minimal inline styles — kept deliberately spare so the application
    // CSS can freely override them.
    // ------------------------------------------------------------------
    function ensureStyles() {
        if (document.getElementById(STYLE_TAG_ID)) return;
        var style = document.createElement('style');
        style.id = STYLE_TAG_ID;
        style.textContent = [
            '[data-rich-editor]{display:flex;flex-direction:column;gap:.5rem;}',
            '[data-rich-toolbar]{display:flex;flex-wrap:wrap;gap:.25rem;}',
            '[data-rich-toolbar] button{',
            '  cursor:pointer;padding:.3rem .55rem;border-radius:.25rem;',
            '  border:1px solid #ccc;background:#fff;font-size:.85rem;line-height:1;',
            '  color:#333;',
            '}',
            '[data-rich-toolbar] button:hover,[data-rich-toolbar] button:focus{',
            '  background:#e8e8e8;outline:none;',
            '}',
            '[data-rich-source]{width:100%;box-sizing:border-box;font-family:monospace;',
            '  min-height:12rem;resize:vertical;}',
            '[data-rich-preview]{min-height:4rem;padding:.75rem;border:1px solid #ddd;',
            '  border-radius:.25rem;background:#fafafa;overflow-y:auto;}',
            '[data-rich-error]{color:#b91c1c;font-size:.875rem;margin-top:.25rem;}',
            '.h1-btn sup,.h2-btn sup,.h3-btn sup{font-size:.6em;vertical-align:super;}',
            '[data-theme="dark"] [data-rich-toolbar] button{',
            '  background:#2d2d2d;color:#e0e0e0;border-color:#555;',
            '}',
            '[data-theme="dark"] [data-rich-toolbar] button:hover,[data-theme="dark"] [data-rich-toolbar] button:focus{',
            '  background:#3d3d3d;',
            '}',
            '[data-theme="dark"] [data-rich-preview]{',
            '  background:#1a1a1a;color:#e0e0e0;border-color:#444;',
            '}'
        ].join('');
        (document.head || document.documentElement).appendChild(style);
    }

    // ------------------------------------------------------------------
    // Turndown accessor — loaded lazily. Turndown may be window.TurndownService
    // or a global `turndown` function, depending on which CDN build is used.
    // ------------------------------------------------------------------
    function getTurndownService() {
        if (typeof window.TurndownService === 'function') {
            return window.TurndownService;
        }
        return null;
    }

    // Convert an HTML string to Markdown via Turndown (Req 12.9).
    // Returns null when Turndown is not available.
    function htmlToMarkdown(html) {
        var Svc = getTurndownService();
        if (!Svc) return null;
        try {
            var td = new Svc({
                headingStyle:   'atx',
                hr:             '---',
                bulletListMarker: '-',
                codeBlockStyle: 'fenced',
                fence:          '```',
                emDelimiter:    '*',
                strongDelimiter:'**'
            });
            return td.turndown(html);
        } catch (e) {
            return null;
        }
    }

    // ------------------------------------------------------------------
    // marked accessor — loaded lazily.
    // ------------------------------------------------------------------
    function getMarked() {
        if (typeof window.marked !== 'undefined') {
            // marked v4+ exposes `marked.parse`; v1/v2/v3 exposes `marked` directly.
            if (typeof window.marked.parse === 'function') return window.marked.parse;
            if (typeof window.marked === 'function') return window.marked;
        }
        return null;
    }

    // Render Markdown source to an HTML string.
    // Returns the raw source wrapped in <pre> when marked is not yet loaded.
    function renderMarkdown(source) {
        var parse = getMarked();
        if (!parse) {
            // Fallback: show raw source safely
            return '<pre>' + escapeHtml(source) + '</pre>';
        }
        try {
            return parse(source);
        } catch (e) {
            return '<pre>' + escapeHtml(source) + '</pre>';
        }
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // ------------------------------------------------------------------
    // Debounce helper with per-instance timer tracking.
    // ------------------------------------------------------------------
    function createDebouncer() {
        var timerId = null;
        return function debounce(fn, delay) {
            if (timerId !== null) {
                clearTimeout(timerId);
                timerId = null;
            }
            timerId = setTimeout(function () {
                timerId = null;
                fn();
            }, delay);
        };
    }

    // ------------------------------------------------------------------
    // Wrap / insert logic (Reqs 12.3, 12.4)
    //
    // Given the textarea, `before`, `after`, and `placeholder`:
    //
    //   - If there is a non-empty selection: replace selection with
    //     before + selectedText + after.
    //   - If there is no selection: insert before + placeholder + after at
    //     cursor, then place cursor between before and after (i.e. select
    //     the placeholder so the user can immediately type over it).
    // ------------------------------------------------------------------
    function applyWrap(textarea, before, after, placeholder) {
        textarea.focus();

        var start = textarea.selectionStart;
        var end   = textarea.selectionEnd;
        var value = textarea.value;

        var selected = value.slice(start, end);
        var hasSelection = selected.length > 0;

        var replacement;
        var cursorStart;
        var cursorEnd;

        if (hasSelection) {
            // Req 12.3: wrap the selected text
            replacement = before + selected + after;
            cursorStart = start + before.length;
            cursorEnd   = start + before.length + selected.length;
        } else {
            // Req 12.4: insert at cursor, place cursor between markers
            replacement = before + placeholder + after;
            cursorStart = start + before.length;
            cursorEnd   = start + before.length + placeholder.length;
        }

        // Enforce the character limit before committing the change (Req 12.10)
        var newValue = value.slice(0, start) + replacement + value.slice(end);
        if (newValue.length > MAX_CHARS) {
            return false; // caller will surface the error
        }

        // Use execCommand when available so the operation is undoable.
        // Fall back to direct assignment when execCommand is not supported.
        var applied = false;
        if (
            document.execCommand &&
            typeof document.execCommand === 'function'
        ) {
            try {
                textarea.select();
                textarea.setSelectionRange(start, end);
                applied = document.execCommand('insertText', false, replacement);
            } catch (e) {
                applied = false;
            }
        }

        if (!applied) {
            // Direct assignment fallback
            textarea.value = newValue;
        }

        // Set cursor / selection
        textarea.setSelectionRange(cursorStart, cursorEnd);
        return true;
    }

    // ------------------------------------------------------------------
    // Error message helpers
    // ------------------------------------------------------------------
    function showError(errorEl, message) {
        if (!errorEl) return;
        errorEl.textContent = message;
        errorEl.removeAttribute('hidden');
    }

    function clearError(errorEl) {
        if (!errorEl) return;
        errorEl.textContent = '';
        errorEl.setAttribute('hidden', '');
    }

    // ------------------------------------------------------------------
    // Editor instance factory — one per [data-rich-editor] node.
    // ------------------------------------------------------------------
    function createEditorInstance(root) {
        var toolbar   = root.querySelector('[data-rich-toolbar]');
        var source    = root.querySelector('[data-rich-source]');
        var preview   = root.querySelector('[data-rich-preview]');
        var errorEl   = root.querySelector('[data-rich-error]');

        if (!source) return; // minimal requirement: a textarea must exist

        var debounce = createDebouncer();

        // ----------------------------------------------------------------
        // Build toolbar (Req 12.2)
        // ----------------------------------------------------------------
        if (toolbar) {
            TOOLBAR_BUTTONS.forEach(function (def) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.setAttribute('aria-label', def.label);
                btn.title = def.label;
                if (def.className) btn.className = def.className;

                var icon = document.createElement('i');
                icon.className = def.icon;
                icon.setAttribute('aria-hidden', 'true');
                btn.appendChild(icon);

                // Heading buttons: add small superscript to disambiguate
                if (def.label === 'Heading 1') {
                    var sup1 = document.createElement('sup');
                    sup1.textContent = '1';
                    btn.appendChild(sup1);
                } else if (def.label === 'Heading 2') {
                    var sup2 = document.createElement('sup');
                    sup2.textContent = '2';
                    btn.appendChild(sup2);
                } else if (def.label === 'Heading 3') {
                    var sup3 = document.createElement('sup');
                    sup3.textContent = '3';
                    btn.appendChild(sup3);
                }

                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    var params = def.wrap(
                        source.value.slice(source.selectionStart, source.selectionEnd),
                        source.selectionStart !== source.selectionEnd
                    );
                    var ok = applyWrap(source, params.before, params.after, params.placeholder);
                    if (!ok) {
                        showError(errorEl, 'Maximum length of ' + MAX_CHARS.toLocaleString() + ' characters reached.');
                    } else {
                        clearError(errorEl);
                    }
                    schedulePreview();
                    // fire input event so external listeners (e.g. form dirty tracking) are notified
                    source.dispatchEvent(new Event('input', { bubbles: true }));
                });

                toolbar.appendChild(btn);
            });
        }

        // ----------------------------------------------------------------
        // Live preview scheduling (Req 12.5)
        // ----------------------------------------------------------------
        function schedulePreview() {
            if (!preview) return;
            var len   = source.value.length;
            var delay = len <= SMALL_THRESHOLD ? DEBOUNCE_SMALL : DEBOUNCE_LARGE;
            var capturedSource = source.value;
            debounce(function () {
                renderPreview(capturedSource);
            }, delay);
        }

        function renderPreview(markdownSource) {
            if (!preview) return;
            preview.innerHTML = renderMarkdown(markdownSource);
        }

        // ----------------------------------------------------------------
        // Input event — enforce limit and schedule preview (Req 12.10, 12.5)
        // ----------------------------------------------------------------
        source.addEventListener('input', function () {
            if (source.value.length > MAX_CHARS) {
                // Truncate to the limit and restore cursor at the boundary
                var pos = Math.min(source.selectionStart, MAX_CHARS);
                source.value = source.value.slice(0, MAX_CHARS);
                source.setSelectionRange(pos, pos);
                showError(errorEl, 'Maximum length of ' + MAX_CHARS.toLocaleString() + ' characters reached.');
            } else {
                clearError(errorEl);
            }
            schedulePreview();
        });

        // ----------------------------------------------------------------
        // Paste handler (Reqs 12.9, 12.10)
        //
        // Strategy:
        //   1. Read text/html from the clipboard and convert via Turndown.
        //   2. If Turndown is unavailable or returns null, fall back to
        //      text/plain.
        //   3. After resolving the pasted text, enforce the 500,000-char
        //      limit; reject the excess and show an error if exceeded.
        // ----------------------------------------------------------------
        source.addEventListener('paste', function (event) {
            var clipData = event.clipboardData || window.clipboardData;
            if (!clipData) return; // let default paste proceed

            var htmlContent  = clipData.getData('text/html');
            var plainContent = clipData.getData('text/plain');

            // Only intercept when we have something to convert
            if (!htmlContent && !plainContent) return;

            event.preventDefault();

            var pastedText;

            if (htmlContent) {
                var converted = htmlToMarkdown(htmlContent);
                pastedText = (converted !== null) ? converted : plainContent;
            } else {
                pastedText = plainContent;
            }

            if (!pastedText) return;

            // Insert the resolved text at the current cursor position
            var start   = source.selectionStart;
            var end     = source.selectionEnd;
            var current = source.value;
            var newValue = current.slice(0, start) + pastedText + current.slice(end);

            if (newValue.length > MAX_CHARS) {
                // Reject the excess — keep only what fits (Req 12.10)
                var available = MAX_CHARS - (current.length - (end - start));
                if (available <= 0) {
                    showError(errorEl, 'Maximum length of ' + MAX_CHARS.toLocaleString() + ' characters reached.');
                    return;
                }
                pastedText = pastedText.slice(0, available);
                newValue   = current.slice(0, start) + pastedText + current.slice(end);
                showError(errorEl, 'Maximum length of ' + MAX_CHARS.toLocaleString() + ' characters reached. Paste was truncated.');
            } else {
                clearError(errorEl);
            }

            // Apply via execCommand for undo support, fall back to direct
            var applied = false;
            if (document.execCommand && typeof document.execCommand === 'function') {
                try {
                    source.setSelectionRange(start, end);
                    applied = document.execCommand('insertText', false, pastedText);
                } catch (e) {
                    applied = false;
                }
            }

            if (!applied) {
                source.value = newValue;
                var cursorPos = start + pastedText.length;
                source.setSelectionRange(cursorPos, cursorPos);
            }

            schedulePreview();
            source.dispatchEvent(new Event('input', { bubbles: true }));
        });

        // ----------------------------------------------------------------
        // Keydown: prevent typing past the limit (Req 12.10)
        // Intercept only printable characters (not control keys / shortcuts).
        // ----------------------------------------------------------------
        source.addEventListener('keydown', function (e) {
            if (source.value.length >= MAX_CHARS) {
                // Allow navigation, deletion, and modifier-combos
                var allow =
                    e.key === 'Backspace'  ||
                    e.key === 'Delete'     ||
                    e.key === 'ArrowLeft'  ||
                    e.key === 'ArrowRight' ||
                    e.key === 'ArrowUp'    ||
                    e.key === 'ArrowDown'  ||
                    e.key === 'Home'       ||
                    e.key === 'End'        ||
                    e.key === 'Tab'        ||
                    e.ctrlKey || e.metaKey || e.altKey;
                if (!allow && e.key.length === 1) {
                    // Only block if no text is selected (replacing selected text is fine)
                    if (source.selectionStart === source.selectionEnd) {
                        e.preventDefault();
                        showError(errorEl, 'Maximum length of ' + MAX_CHARS.toLocaleString() + ' characters reached.');
                    }
                }
            }
        });

        // ----------------------------------------------------------------
        // Initial preview render
        // ----------------------------------------------------------------
        if (source.value) {
            schedulePreview();
        }

        // ----------------------------------------------------------------
        // Expose a small test surface
        // ----------------------------------------------------------------
        root.__richEditorInstance = {
            applyWrap:       applyWrap,
            htmlToMarkdown:  htmlToMarkdown,
            renderMarkdown:  renderMarkdown,
            schedulePreview: schedulePreview,
            MAX_CHARS:       MAX_CHARS,
            TOOLBAR_BUTTONS: TOOLBAR_BUTTONS,
            source:          source,
            preview:         preview,
            errorEl:         errorEl
        };
    }

    // ------------------------------------------------------------------
    // Scan the document for [data-rich-editor] roots and initialise each.
    // ------------------------------------------------------------------
    function init() {
        ensureStyles();
        var roots = document.querySelectorAll('[data-rich-editor]');
        for (var i = 0; i < roots.length; i++) {
            var root = roots[i];
            if (root.dataset.richEditorBound === '1') continue;
            root.dataset.richEditorBound = '1';
            createEditorInstance(root);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ------------------------------------------------------------------
    // Public test surface (mirrors the pattern in clipboard.js /
    // theme-manager.js).
    // ------------------------------------------------------------------
    window.__airtoshareRichEditor = {
        init:           init,
        applyWrap:      applyWrap,
        htmlToMarkdown: htmlToMarkdown,
        renderMarkdown: renderMarkdown,
        escapeHtml:     escapeHtml,
        MAX_CHARS:      MAX_CHARS,
        DEBOUNCE_SMALL: DEBOUNCE_SMALL,
        DEBOUNCE_LARGE: DEBOUNCE_LARGE,
        SMALL_THRESHOLD: SMALL_THRESHOLD,
        TOOLBAR_BUTTONS: TOOLBAR_BUTTONS
    };
})();
