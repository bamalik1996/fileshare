/**
 * AirToShare Preview Renderer
 * Implements Requirement 6 acceptance criteria 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7.
 *
 * Data-attribute driven so it can be applied to any share view without
 * bespoke per-page JavaScript. Each previewable file is wrapped in a
 * "preview row" that carries the metadata the renderer needs:
 *
 *   <div class="preview-row"
 *        data-preview-uuid="..."
 *        data-preview-mime="image/png"
 *        data-preview-size="123456"
 *        data-preview-url="/download/abc"
 *        data-preview-name="cat.png">
 *     <a class="preview-download" href="/download/abc" download>Download</a>
 *   </div>
 *
 * Behaviour summary:
 *
 *   - classify(mime, size) maps the (mime, size) pair to one of
 *     "image" | "pdf" | "video" | "generic" using the size ceilings in
 *     Reqs 6.1, 6.2, 6.3 and falls back to "generic" for everything else
 *     (Req 6.4). It is exposed on the test surface so unit/property
 *     tests can drive it directly without touching the DOM.
 *   - IntersectionObserver is used to lazy-load preview content only
 *     after the row enters the viewport (Req 6.6). A second observer
 *     debounces a 5 second out-of-view release window before tearing
 *     down the loaded preview to free memory (Req 6.6).
 *   - Each load is guarded by a 10 second timeout plus error-event
 *     listeners on <img>, <video>, and the PDF.js iframe; failures
 *     replace the preview area with an error indicator and a retry
 *     control while keeping the always-rendered Download button intact
 *     (Reqs 6.5, 6.7).
 *   - If the surrounding share is end-to-end encrypted (Req 15.7), the
 *     row is left for the encryption module to populate via blob URLs;
 *     this renderer skips it entirely.
 */
(function () {
    'use strict';

    // ---------------------------------------------------------------------
    // Configuration constants.
    // ---------------------------------------------------------------------
    var IMAGE_MAX_BYTES = 25 * 1024 * 1024;        // Req 6.1: ≤ 25 MB
    var PDF_MAX_BYTES = 25 * 1024 * 1024;          // Req 6.2: ≤ 25 MB
    var VIDEO_MAX_BYTES = 200 * 1024 * 1024;       // Req 6.3: ≤ 200 MB
    var LOAD_TIMEOUT_MS = 10000;                   // Req 6.7: 10 s timeout
    var RELEASE_DELAY_MS = 5000;                   // Req 6.6: 5 s out-of-view release
    var STYLE_TAG_ID = 'airtoshare-preview-styles';
    var ROW_SELECTOR = '.preview-row[data-preview-mime][data-preview-url]';

    // PDF.js viewer URL. The hosting application should drop the
    // PDF.js distribution under /assets/pdfjs/ (Task 10.2). The viewer
    // path is overridable via <meta name="airtoshare-pdfjs-viewer"
    // content="..."> so installations using a CDN or a custom path can
    // wire it without editing this file.
    var DEFAULT_PDFJS_VIEWER = '/assets/pdfjs/web/viewer.html';

    function pdfjsViewerUrl() {
        try {
            var meta = document.querySelector('meta[name="airtoshare-pdfjs-viewer"]');
            if (meta && meta.content) return String(meta.content);
        } catch (e) { /* ignore */ }
        return DEFAULT_PDFJS_VIEWER;
    }

    // ---------------------------------------------------------------------
    // Inline baseline styles. Kept minimal so the renderer works without
    // CSS changes to the host application; custom.css can override.
    // ---------------------------------------------------------------------
    function ensureStyles() {
        if (document.getElementById(STYLE_TAG_ID)) return;
        var style = document.createElement('style');
        style.id = STYLE_TAG_ID;
        style.textContent = [
            '.preview-row{position:relative;}',
            '.preview-frame{display:block;width:100%;max-width:100%;}',
            '.preview-frame img.preview-media,',
            '.preview-frame video.preview-media{',
            '  display:block;max-width:100%;height:auto;border-radius:.25rem;',
            '}',
            '.preview-frame iframe.preview-media{',
            '  display:block;width:100%;min-height:480px;border:0;border-radius:.25rem;',
            '}',
            '.preview-generic{',
            '  display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;',
            '  border:1px dashed rgba(0,0,0,.18);border-radius:.25rem;',
            '  font-size:.95rem;color:inherit;',
            '}',
            '.preview-generic .preview-icon{',
            '  width:32px;height:32px;display:inline-flex;align-items:center;',
            '  justify-content:center;border-radius:.25rem;background:rgba(0,0,0,.08);',
            '  font-size:1.1rem;flex:0 0 auto;',
            '}',
            '.preview-generic .preview-icon::before{content:"\\1F4C4";}', // 📄
            '.preview-error{',
            '  display:flex;align-items:center;justify-content:space-between;',
            '  gap:1rem;padding:.6rem .9rem;border-radius:.25rem;',
            '  background:rgba(185,28,28,.08);color:#b91c1c;font-size:.9rem;',
            '}',
            '.preview-error .preview-retry{',
            '  background:#b91c1c;color:#fff;border:0;border-radius:.25rem;',
            '  padding:.3rem .75rem;font-size:.85rem;font-weight:600;cursor:pointer;',
            '}',
            '.preview-error .preview-retry:hover{filter:brightness(1.05);}',
            '.preview-loading{',
            '  display:inline-block;padding:.4rem .75rem;font-size:.85rem;',
            '  color:rgba(0,0,0,.55);font-style:italic;',
            '}'
        ].join('');
        (document.head || document.documentElement).appendChild(style);
    }

    // ---------------------------------------------------------------------
    // Classifier (Reqs 6.1, 6.2, 6.3, 6.4).
    //
    // Returns one of:
    //   "image"   — image/* up to 25 MB
    //   "pdf"     — application/pdf up to 25 MB
    //   "video"   — video/* up to 200 MB
    //   "generic" — everything else, including unknown / oversized
    //
    // Defensive against missing or non-numeric size values: a missing
    // size is treated as 0 (still in range), a negative size as oversize.
    // ---------------------------------------------------------------------
    function classify(mime, size) {
        var m = String(mime == null ? '' : mime).toLowerCase().trim();
        // Strip any "; charset=..." parameter so e.g. "image/png; charset=binary"
        // still classifies as image.
        var semi = m.indexOf(';');
        if (semi !== -1) m = m.slice(0, semi).trim();

        var n = Number(size);
        if (!isFinite(n) || n < 0) return 'generic';

        if (m.indexOf('image/') === 0) {
            return n <= IMAGE_MAX_BYTES ? 'image' : 'generic';
        }
        if (m === 'application/pdf') {
            return n <= PDF_MAX_BYTES ? 'pdf' : 'generic';
        }
        if (m.indexOf('video/') === 0) {
            return n <= VIDEO_MAX_BYTES ? 'video' : 'generic';
        }
        return 'generic';
    }

    // ---------------------------------------------------------------------
    // DOM helpers
    // ---------------------------------------------------------------------
    function getOrCreateFrame(row) {
        var host = row.querySelector('.file-preview');
        if (host) {
            var frame = host.querySelector('.preview-frame');
            if (frame) return frame;
            frame = document.createElement('div');
            frame.className = 'preview-frame';
            emptyNode(host);
            host.appendChild(frame);
            return frame;
        }

        var frame = row.querySelector('.preview-frame');
        if (frame) return frame;
        frame = document.createElement('div');
        frame.className = 'preview-frame';
        // Insert before the always-rendered Download button so the
        // preview appears above the download control. If no download
        // button exists, just append.
        var dl = row.querySelector('.preview-download');
        if (dl && dl.parentNode === row) {
            row.insertBefore(frame, dl);
        } else {
            row.appendChild(frame);
        }
        return frame;
    }

    function emptyNode(node) {
        if (!node) return;
        while (node.firstChild) {
            try { node.removeChild(node.firstChild); }
            catch (e) { break; }
        }
    }

    function showLoading(frame) {
        emptyNode(frame);
        var span = document.createElement('span');
        span.className = 'preview-loading';
        span.setAttribute('aria-live', 'polite');
        span.textContent = 'Loading preview…';
        frame.appendChild(span);
    }

    function showGeneric(frame, name) {
        emptyNode(frame);
        var wrap = document.createElement('div');
        wrap.className = 'preview-generic';
        var icon = document.createElement('span');
        icon.className = 'preview-icon';
        icon.setAttribute('aria-hidden', 'true');
        var label = document.createElement('span');
        label.className = 'preview-name';
        label.textContent = String(name == null ? '' : name);
        wrap.appendChild(icon);
        wrap.appendChild(label);
        frame.appendChild(wrap);
    }

    function showError(frame, name, onRetry) {
        emptyNode(frame);
        var wrap = document.createElement('div');
        wrap.className = 'preview-error';
        wrap.setAttribute('role', 'alert');

        var msg = document.createElement('span');
        msg.className = 'preview-error__message';
        msg.textContent =
            'Preview failed for ' + String(name == null ? '' : name) + '.';

        var retry = document.createElement('button');
        retry.type = 'button';
        retry.className = 'preview-retry';
        retry.textContent = 'Retry';
        retry.addEventListener('click', function () {
            try { onRetry(); } catch (e) { /* ignore */ }
        });

        wrap.appendChild(msg);
        wrap.appendChild(retry);
        frame.appendChild(wrap);
    }

    // ---------------------------------------------------------------------
    // Per-row state. We keep references on the row element itself so the
    // GC can collect everything when the row is removed from the DOM.
    // ---------------------------------------------------------------------
    function getState(row) {
        if (!row.__previewState) {
            row.__previewState = {
                kind: null,
                loaded: false,
                loading: false,
                released: true,
                releaseTimer: null,
                loadTimer: null,
                inView: false
            };
        }
        return row.__previewState;
    }

    function clearTimers(state) {
        if (state.releaseTimer) {
            try { window.clearTimeout(state.releaseTimer); } catch (e) {}
            state.releaseTimer = null;
        }
        if (state.loadTimer) {
            try { window.clearTimeout(state.loadTimer); } catch (e) {}
            state.loadTimer = null;
        }
    }

    // ---------------------------------------------------------------------
    // Renderers per kind.
    // ---------------------------------------------------------------------
    function renderImage(row, frame, ctx) {
        var img = document.createElement('img');
        img.className = 'preview-media';
        img.alt = ctx.name || '';
        img.loading = 'lazy';            // Req 6.1: native lazy-load
        img.decoding = 'async';
        bindLoadGuard(row, img, ctx);
        emptyNode(frame);
        frame.appendChild(img);
        // Set src after handlers are attached so error/load fire predictably.
        img.src = ctx.url;
    }

    function renderPdf(row, frame, ctx) {
        var iframe = document.createElement('iframe');
        iframe.className = 'preview-media';
        iframe.title = ctx.name || 'PDF preview';
        iframe.setAttribute('allowfullscreen', '');
        iframe.setAttribute('referrerpolicy', 'no-referrer');
        // PDF.js viewer with the file= parameter pointing at the actual
        // download URL. Encoding the URL keeps query/hash characters
        // from breaking the viewer's parser.
        var viewer = pdfjsViewerUrl();
        iframe.src =
            viewer + '?file=' + encodeURIComponent(ctx.url);
        bindLoadGuard(row, iframe, ctx);
        emptyNode(frame);
        frame.appendChild(iframe);
    }

    function renderVideo(row, frame, ctx) {
        var video = document.createElement('video');
        video.className = 'preview-media';
        video.controls = true;
        video.preload = 'metadata';
        // Use a single <source> child so we can set the type hint.
        var src = document.createElement('source');
        src.src = ctx.url;
        if (ctx.mime) src.type = ctx.mime;
        video.appendChild(src);
        bindLoadGuard(row, video, ctx);
        emptyNode(frame);
        frame.appendChild(video);
        try { video.load(); } catch (e) { /* ignore */ }
    }

    // ---------------------------------------------------------------------
    // Load guard: 10 s timeout + error event handler (Req 6.7).
    //
    // Success is signalled by the underlying element's "load" event for
    // <img> and <iframe>, and by "loadeddata" / "loadedmetadata" for
    // <video>. On either timeout or error we replace the frame with the
    // error indicator + retry control while leaving the always-rendered
    // download button untouched (Req 6.5).
    // ---------------------------------------------------------------------
    function bindLoadGuard(row, el, ctx) {
        var state = getState(row);

        function clear() {
            if (state.loadTimer) {
                try { window.clearTimeout(state.loadTimer); } catch (e) {}
                state.loadTimer = null;
            }
            try {
                el.removeEventListener('load', onLoad);
                el.removeEventListener('loadeddata', onLoad);
                el.removeEventListener('loadedmetadata', onLoad);
                el.removeEventListener('error', onError);
            } catch (e) { /* ignore */ }
        }

        function onLoad() {
            state.loading = false;
            state.loaded = true;
            clear();
        }

        function onError() {
            state.loading = false;
            state.loaded = false;
            clear();
            handleFailure(row, ctx);
        }

        el.addEventListener('load', onLoad);
        el.addEventListener('loadeddata', onLoad);
        el.addEventListener('loadedmetadata', onLoad);
        el.addEventListener('error', onError);

        state.loadTimer = window.setTimeout(function () {
            state.loadTimer = null;
            // Only treat as failure if the load did not yet complete.
            if (!state.loaded) {
                clear();
                handleFailure(row, ctx);
            }
        }, LOAD_TIMEOUT_MS);
    }

    function handleFailure(row, ctx) {
        var frame = getOrCreateFrame(row);
        showError(frame, ctx.name, function retry() {
            // Reset state and re-trigger a load.
            var st = getState(row);
            clearTimers(st);
            st.loaded = false;
            st.loading = false;
            st.released = true;
            loadPreview(row);
        });
    }

    // ---------------------------------------------------------------------
    // Read row context once. Returns null for rows we explicitly skip
    // (e.g. E2EE-marked rows handed off to the encryption module).
    // ---------------------------------------------------------------------
    function readContext(row) {
        if (row.dataset.previewE2ee === '1') return null;
        var url = row.dataset.previewUrl;
        var mime = row.dataset.previewMime;
        if (!url || !mime) return null;
        return {
            uuid: row.dataset.previewUuid || '',
            mime: mime,
            size: Number(row.dataset.previewSize) || 0,
            url: url,
            name: row.dataset.previewName || ''
        };
    }

    // ---------------------------------------------------------------------
    // Public per-row entry points.
    // ---------------------------------------------------------------------
    function loadPreview(row) {
        ensureStyles();
        var ctx = readContext(row);
        if (!ctx) return;
        var state = getState(row);
        if (state.loading || state.loaded) return;

        var frame = getOrCreateFrame(row);
        var kind = classify(ctx.mime, ctx.size);
        state.kind = kind;
        state.released = false;

        if (kind === 'generic') {
            // Req 6.4: show generic icon + filename, no inline preview.
            showGeneric(frame, ctx.name);
            state.loaded = true;
            state.loading = false;
            return;
        }

        state.loading = true;
        showLoading(frame);

        if (kind === 'image') {
            renderImage(row, frame, ctx);
        } else if (kind === 'pdf') {
            renderPdf(row, frame, ctx);
        } else if (kind === 'video') {
            renderVideo(row, frame, ctx);
        }
    }

    function releasePreview(row) {
        var state = getState(row);
        if (state.released) return;
        clearTimers(state);
        var frame = row.querySelector('.preview-frame');
        if (!frame) {
            state.released = true;
            state.loaded = false;
            state.loading = false;
            return;
        }
        // Stop any media playback before tearing down (Req 6.6: release
        // loaded preview content from memory).
        var video = frame.querySelector('video.preview-media');
        if (video) {
            try { video.pause(); } catch (e) {}
            try { video.removeAttribute('src'); video.load(); } catch (e) {}
        }
        var img = frame.querySelector('img.preview-media');
        if (img) {
            try { img.src = ''; } catch (e) {}
        }
        var iframe = frame.querySelector('iframe.preview-media');
        if (iframe) {
            try { iframe.src = 'about:blank'; } catch (e) {}
        }
        emptyNode(frame);
        state.released = true;
        state.loaded = false;
        state.loading = false;
    }

    // ---------------------------------------------------------------------
    // IntersectionObserver wiring.
    //
    // - Entering the viewport triggers a load and cancels any pending
    //   release timer so a row that briefly leaves and returns within
    //   the 5 s window does not get torn down (Req 6.6).
    // - Leaving the viewport schedules a release 5 s later.
    // ---------------------------------------------------------------------
    var ioInstance = null;

    function ensureObserver() {
        if (ioInstance) return ioInstance;
        if (typeof window.IntersectionObserver !== 'function') {
            // No observer support — eagerly load every visible row.
            ioInstance = {
                observe: function (row) { loadPreview(row); },
                unobserve: function () {},
                disconnect: function () {}
            };
            return ioInstance;
        }
        ioInstance = new window.IntersectionObserver(function (entries) {
            for (var i = 0; i < entries.length; i++) {
                var entry = entries[i];
                var row = entry.target;
                var state = getState(row);
                if (entry.isIntersecting) {
                    state.inView = true;
                    if (state.releaseTimer) {
                        try { window.clearTimeout(state.releaseTimer); }
                        catch (e) {}
                        state.releaseTimer = null;
                    }
                    if (!state.loaded && !state.loading) {
                        loadPreview(row);
                    }
                } else {
                    state.inView = false;
                    if (state.releaseTimer) {
                        try { window.clearTimeout(state.releaseTimer); }
                        catch (e) {}
                    }
                    state.releaseTimer = window.setTimeout(function (r) {
                        return function () {
                            var st = getState(r);
                            st.releaseTimer = null;
                            if (!st.inView) releasePreview(r);
                        };
                    }(row), RELEASE_DELAY_MS);
                }
            }
        }, { root: null, rootMargin: '0px', threshold: 0.01 });
        return ioInstance;
    }

    // ---------------------------------------------------------------------
    // Public registration.
    // ---------------------------------------------------------------------
    function registerRow(row) {
        if (!row || row.__previewRegistered) return;
        var ctx = readContext(row);
        if (!ctx) return;
        row.__previewRegistered = true;

        // Frame is always present so the layout is stable while the
        // preview is unloaded.
        getOrCreateFrame(row);

        // Always-rendered Download button (Req 6.5). If the page did not
        // ship one, synthesise a minimal anchor so the preview is never
        // displayed without a download fallback.
        if (!row.querySelector('.preview-download')) {
            var a = document.createElement('a');
            a.className = 'preview-download';
            a.href = ctx.url;
            a.setAttribute('download', ctx.name || '');
            a.textContent = 'Download';
            row.appendChild(a);
        }

        ensureObserver().observe(row);
    }

    function scan(root) {
        var scope = root || document;
        var rows = scope.querySelectorAll(ROW_SELECTOR);
        for (var i = 0; i < rows.length; i++) {
            registerRow(rows[i]);
        }
    }

    function init() {
        ensureStyles();
        scan(document);

        // Watch for rows added later by other scripts (e.g. realtime
        // updates, upload manager success rows, account "my shares"
        // pagination). MutationObserver keeps the wiring automatic.
        if (typeof window.MutationObserver === 'function') {
            var mo = new window.MutationObserver(function (records) {
                for (var i = 0; i < records.length; i++) {
                    var added = records[i].addedNodes;
                    for (var j = 0; j < added.length; j++) {
                        var node = added[j];
                        if (node.nodeType !== 1) continue;
                        if (node.matches && node.matches(ROW_SELECTOR)) {
                            registerRow(node);
                        }
                        if (node.querySelectorAll) {
                            scan(node);
                        }
                    }
                }
            });
            mo.observe(document.documentElement || document.body, {
                childList: true,
                subtree: true
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Tiny test surface for unit/property tests. Hidden behind the
    // underscored namespace; not part of the public API.
    window.__airtoshareePreviewRenderer = {
        classify: classify,
        registerRow: registerRow,
        loadPreview: loadPreview,
        releasePreview: releasePreview,
        scan: scan,
        IMAGE_MAX_BYTES: IMAGE_MAX_BYTES,
        PDF_MAX_BYTES: PDF_MAX_BYTES,
        VIDEO_MAX_BYTES: VIDEO_MAX_BYTES,
        LOAD_TIMEOUT_MS: LOAD_TIMEOUT_MS,
        RELEASE_DELAY_MS: RELEASE_DELAY_MS
    };
})();
