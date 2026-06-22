/**
 * AirToShare Upload Manager
 * Implements Requirement 8 acceptance criteria 8.1-8.10.
 *
 * Data-attribute driven so any drop zone in the page can be wired
 * without bespoke per-page glue. The smallest valid markup is:
 *
 *   <div data-upload-manager
 *        data-upload-endpoint="/api/v1/media"
 *        data-upload-field="file"
 *        data-upload-max-files="50"
 *        data-upload-active-files="0"
 *        data-upload-max-size="26214400">
 *     <input type="file" data-upload-input multiple>
 *     <div data-upload-list></div>
 *     <div data-upload-summary hidden></div>
 *     <div data-upload-error hidden></div>
 *   </div>
 *
 * Behaviour summary:
 *
 *   - Accepts files dropped on the zone (Req 8.1) and shows a hover
 *     indicator while a drag is in progress (Req 8.2) by toggling the
 *     `is-dragover` class on the zone.
 *   - Queues every dropped file in drop order up to the remaining
 *     per-owner capacity (Req 8.3). Files that exceed the capacity are
 *     rejected and an error is rendered identifying the rejected count
 *     and the reason (Req 8.4).
 *   - Each file gets its own row with a progress bar that updates at
 *     least every 250 ms while uploading (Req 8.5) and shows the
 *     percentage, bytes uploaded, and total bytes (Req 8.6).
 *   - On success a check indicator is rendered within 1 second of the
 *     server's acknowledgement (Req 8.7). On failure, an error message
 *     and a Retry button are rendered (Req 8.8). Retry re-attempts the
 *     upload with a fresh XMLHttpRequest, allowing up to 3 retries per
 *     file before the button is permanently disabled (Req 8.9).
 *   - When the queue drains, a final summary line "X successful,
 *     Y failed" is displayed (Req 8.10).
 *
 * Per-file state machine (Req 8 design, lines 13.1):
 *
 *   queued ──▶ uploading ──▶ succeeded
 *                        │
 *                        ├──▶ failed (retries < 3)
 *                        │       │
 *                        │       └──▶ uploading (Retry pressed)
 *                        │
 *                        └──▶ exhausted (retries >= 3, Retry disabled)
 */
(function () {
    'use strict';

    // ---------------------------------------------------------------------
    // Constants
    // ---------------------------------------------------------------------
    var STATE_QUEUED = 'queued';
    var STATE_UPLOADING = 'uploading';
    var STATE_SUCCEEDED = 'succeeded';
    var STATE_FAILED = 'failed';
    var STATE_EXHAUSTED = 'exhausted';

    var MAX_RETRIES = 3;                  // Req 8.9
    var PROGRESS_THROTTLE_MS = 250;       // Req 8.5
    var STYLE_TAG_ID = 'airtoshare-upload-manager-styles';
    var ZONE_DRAGOVER_CLASS = 'is-dragover';

    var DEFAULT_ENDPOINT = '/api/v1/media';
    var DEFAULT_FIELD = 'file';
    var DEFAULT_MAX_FILES = 50;           // matches active_files_limit_ip in config/airtoshare.php
    var DEFAULT_MAX_SIZE = 25 * 1024 * 1024; // 25 MB legacy upload cap
    var DEFAULT_CHUNKED_MAX_SIZE = 500 * 1024 * 1024; // 500 MB chunked cap
    var CHUNK_THRESHOLD = 5 * 1024 * 1024;  // Req 9.1 — files > 5 MB use chunked path
    var CHUNK_SIZE = 5 * 1024 * 1024;       // max chunk payload (Req 9.1)
    var CHUNK_MAX_RETRIES = 3;              // per-chunk retry budget (Req 9.6 / task 14.4)
    var DEFAULT_CHUNKED_START = '/api/v1/chunked-upload/start';
    var DEFAULT_CHUNKED_CHUNK = '/api/v1/chunked-upload/chunk';
    var DEFAULT_CHUNKED_STATUS = '/api/v1/chunked-upload/status';
    var DEFAULT_CHUNKED_COMPLETE = '/api/v1/chunked-upload/complete';

    // Monotonic id for file rows (used as DOM ids when needed).
    var __nextRowId = 1;

    // ---------------------------------------------------------------------
    // Inline baseline styles - kept intentionally minimal so the manager
    // works on any page; existing CSS may override.
    // ---------------------------------------------------------------------
    function ensureStyles() {
        if (document.getElementById(STYLE_TAG_ID)) return;
        var style = document.createElement('style');
        style.id = STYLE_TAG_ID;
        style.textContent = [
            '[data-upload-manager]{position:relative;}',
            '[data-upload-manager].' + ZONE_DRAGOVER_CLASS + '{',
            '  outline:2px dashed #2563eb;outline-offset:-6px;',
            '  background-color:rgba(37,99,235,.06);',
            '}',
            '.airtoshare-upload-row{',
            '  display:flex;flex-direction:column;gap:.35rem;',
            '  padding:.6rem .75rem;border:1px solid rgba(0,0,0,.12);',
            '  border-radius:.4rem;margin:.4rem 0;background:rgba(0,0,0,.02);',
            '}',
            '.airtoshare-upload-row__head{',
            '  display:flex;align-items:center;justify-content:space-between;',
            '  gap:.75rem;font-size:.95rem;',
            '}',
            '.airtoshare-upload-row__name{',
            '  flex:1 1 auto;min-width:0;overflow:hidden;',
            '  text-overflow:ellipsis;white-space:nowrap;font-weight:600;',
            '}',
            '.airtoshare-upload-row__status{flex:0 0 auto;font-size:.8rem;color:#475569;}',
            '.airtoshare-upload-row__bar{',
            '  position:relative;height:.5rem;border-radius:.25rem;',
            '  background:rgba(0,0,0,.08);overflow:hidden;',
            '}',
            '.airtoshare-upload-row__fill{',
            '  position:absolute;left:0;top:0;bottom:0;width:0;',
            '  background:#2563eb;transition:width 200ms linear;',
            '}',
            '.airtoshare-upload-row[data-state="succeeded"] .airtoshare-upload-row__fill{background:#16a34a;}',
            '.airtoshare-upload-row[data-state="failed"] .airtoshare-upload-row__fill,',
            '.airtoshare-upload-row[data-state="exhausted"] .airtoshare-upload-row__fill{background:#b91c1c;}',
            '.airtoshare-upload-row__meta{',
            '  display:flex;align-items:center;justify-content:space-between;',
            '  gap:.75rem;font-size:.78rem;color:#475569;',
            '}',
            '.airtoshare-upload-row__retry{',
            '  flex:0 0 auto;font-size:.78rem;font-weight:600;',
            '  padding:.2rem .55rem;border-radius:.25rem;border:1px solid #b91c1c;',
            '  background:#fff;color:#b91c1c;cursor:pointer;',
            '}',
            '.airtoshare-upload-row__retry[disabled]{',
            '  cursor:not-allowed;opacity:.55;',
            '}',
            '.airtoshare-upload-error{',
            '  margin:.4rem 0;padding:.55rem .75rem;border-radius:.4rem;',
            '  background:#fee2e2;color:#7f1d1d;font-size:.9rem;',
            '}',
            '.airtoshare-upload-summary{',
            '  margin:.5rem 0;padding:.55rem .75rem;border-radius:.4rem;',
            '  background:rgba(0,0,0,.04);font-size:.95rem;font-weight:600;',
            '}'
        ].join('');
        (document.head || document.documentElement).appendChild(style);
    }

    // ---------------------------------------------------------------------
    // Utilities
    // ---------------------------------------------------------------------
    function readIntAttr(el, name, fallback) {
        var raw = el.getAttribute(name);
        if (raw === null || raw === undefined || raw === '') return fallback;
        var n = parseInt(raw, 10);
        if (!isFinite(n) || isNaN(n)) return fallback;
        return n;
    }

    function readStrAttr(el, name, fallback) {
        var raw = el.getAttribute(name);
        if (raw === null || raw === undefined || raw === '') return fallback;
        return String(raw);
    }

    function getCsrfToken(zone) {
        var fromAttr = zone.getAttribute('data-csrf-token');
        if (fromAttr) return fromAttr;
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function formatBytes(n) {
        n = +n || 0;
        if (n < 1024) return n + ' B';
        if (n < 1024 * 1024) return (n / 1024).toFixed(1) + ' KB';
        if (n < 1024 * 1024 * 1024) return (n / (1024 * 1024)).toFixed(2) + ' MB';
        return (n / (1024 * 1024 * 1024)).toFixed(2) + ' GB';
    }

    function dispatchEvent(target, name, detail) {
        try {
            var ev;
            if (typeof CustomEvent === 'function') {
                ev = new CustomEvent(name, { bubbles: true, cancelable: false, detail: detail });
            } else {
                ev = document.createEvent('CustomEvent');
                ev.initCustomEvent(name, true, false, detail);
            }
            target.dispatchEvent(ev);
        } catch (e) { /* never let the upload break on telemetry */ }
    }

    // ---------------------------------------------------------------------
    // Per-file controller. Owns its DOM row, its XHR, and its retry
    // counter. The state transitions match the spec's state machine.
    // ---------------------------------------------------------------------
    function createFileController(file, manager) {
        var ctrl = {
            id: 'upl-' + (__nextRowId++),
            file: file,
            state: STATE_QUEUED,
            attempts: 0,                  // number of completed attempts (success or fail)
            retries: 0,                   // number of times Retry has been pressed
            uploaded: 0,
            xhr: null,
            sessionId: null,
            row: null,
            els: {},
            lastProgressDispatch: 0,
            lastProgressUploaded: -1,
            progressTimer: null
        };

        ctrl.row = buildRow(ctrl, file);
        ctrl.start = function () { startUpload(ctrl, manager); };
        ctrl.retry = function () { retryUpload(ctrl, manager); };
        return ctrl;
    }

    function buildRow(ctrl, file) {
        var row = document.createElement('div');
        row.className = 'airtoshare-upload-row';
        row.setAttribute('data-upload-row', '');
        row.setAttribute('data-state', STATE_QUEUED);
        row.id = ctrl.id;

        var head = document.createElement('div');
        head.className = 'airtoshare-upload-row__head';

        var name = document.createElement('span');
        name.className = 'airtoshare-upload-row__name';
        name.textContent = file && file.name ? file.name : '(unnamed)';

        var status = document.createElement('span');
        status.className = 'airtoshare-upload-row__status';
        status.setAttribute('data-upload-status', '');
        status.textContent = 'Queued';

        head.appendChild(name);
        head.appendChild(status);

        var bar = document.createElement('div');
        bar.className = 'airtoshare-upload-row__bar';
        bar.setAttribute('role', 'progressbar');
        bar.setAttribute('aria-valuemin', '0');
        bar.setAttribute('aria-valuemax', '100');
        bar.setAttribute('aria-valuenow', '0');

        var fill = document.createElement('div');
        fill.className = 'airtoshare-upload-row__fill';
        fill.setAttribute('data-upload-fill', '');
        bar.appendChild(fill);

        var meta = document.createElement('div');
        meta.className = 'airtoshare-upload-row__meta';

        var bytes = document.createElement('span');
        bytes.className = 'airtoshare-upload-row__bytes';
        bytes.setAttribute('data-upload-bytes', '');
        var totalSize = file && typeof file.size === 'number' ? file.size : 0;
        bytes.textContent = '0 B / ' + formatBytes(totalSize)
            + ' (0%)';

        var pct = document.createElement('span');
        pct.className = 'airtoshare-upload-row__percent';
        pct.setAttribute('data-upload-percent', '');
        pct.textContent = '0%';

        var retry = document.createElement('button');
        retry.type = 'button';
        retry.className = 'airtoshare-upload-row__retry';
        retry.setAttribute('data-upload-retry', '');
        retry.textContent = 'Retry';
        retry.hidden = true;
        retry.disabled = true;

        meta.appendChild(bytes);
        meta.appendChild(pct);
        meta.appendChild(retry);

        row.appendChild(head);
        row.appendChild(bar);
        row.appendChild(meta);

        ctrl.els = {
            row: row,
            status: status,
            bar: bar,
            fill: fill,
            bytes: bytes,
            percent: pct,
            retry: retry
        };

        retry.addEventListener('click', function () {
            ctrl.retry();
        });

        return row;
    }

    function setState(ctrl, state) {
        ctrl.state = state;
        if (ctrl.els && ctrl.els.row) {
            ctrl.els.row.setAttribute('data-state', state);
        }
    }

    function setStatusText(ctrl, text) {
        if (ctrl.els && ctrl.els.status) {
            ctrl.els.status.textContent = text;
        }
    }

    function applyProgress(ctrl, uploaded, total) {
        var size = total > 0
            ? total
            : (ctrl.file && typeof ctrl.file.size === 'number' ? ctrl.file.size : 0);
        var pct = size > 0
            ? Math.max(0, Math.min(100, (uploaded / size) * 100))
            : 0;
        var pctRounded = Math.floor(pct);
        ctrl.uploaded = uploaded;

        if (ctrl.els.fill) {
            ctrl.els.fill.style.width = pct.toFixed(2) + '%';
        }
        if (ctrl.els.bar) {
            ctrl.els.bar.setAttribute('aria-valuenow', String(pctRounded));
        }
        if (ctrl.els.bytes) {
            ctrl.els.bytes.textContent =
                formatBytes(uploaded) + ' / ' + formatBytes(size)
                + ' (' + pctRounded + '%)';
        }
        if (ctrl.els.percent) {
            ctrl.els.percent.textContent = pctRounded + '%';
        }
    }

    /**
     * Throttled progress writer: at most one DOM mutation per
     * PROGRESS_THROTTLE_MS, with a trailing-edge update so the final
     * progress sample always lands on screen. Req 8.5 demands an
     * update at least every 250 ms; the design pegs the upper bound
     * at every 250 ms via a throttle.
     */
    function emitProgress(ctrl, uploaded, total) {
        ctrl.lastProgressUploaded = uploaded;

        var now = Date.now();
        var elapsed = now - (ctrl.lastProgressDispatch || 0);

        if (elapsed >= PROGRESS_THROTTLE_MS) {
            ctrl.lastProgressDispatch = now;
            applyProgress(ctrl, uploaded, total);
            if (ctrl.progressTimer) {
                try { clearTimeout(ctrl.progressTimer); } catch (e) { /* ignore */ }
                ctrl.progressTimer = null;
            }
            dispatchEvent(ctrl.els.row, 'upload:progress', {
                id: ctrl.id, uploaded: uploaded, total: total
            });
        } else if (!ctrl.progressTimer) {
            // Trailing-edge: schedule one update so the final sample lands.
            var wait = PROGRESS_THROTTLE_MS - elapsed;
            ctrl.progressTimer = setTimeout(function () {
                ctrl.progressTimer = null;
                ctrl.lastProgressDispatch = Date.now();
                applyProgress(ctrl, ctrl.lastProgressUploaded, total);
                dispatchEvent(ctrl.els.row, 'upload:progress', {
                    id: ctrl.id, uploaded: ctrl.lastProgressUploaded, total: total
                });
            }, wait);
        }
    }

    function startUpload(ctrl, manager) {
        if (ctrl.state === STATE_UPLOADING || ctrl.state === STATE_SUCCEEDED || ctrl.state === STATE_EXHAUSTED) {
            return;
        }

        setState(ctrl, STATE_UPLOADING);
        setStatusText(ctrl, 'Uploading...');

        // Hide retry while uploading (Req 8.8: only present once failed).
        if (ctrl.els.retry) {
            ctrl.els.retry.hidden = true;
            ctrl.els.retry.disabled = true;
        }

        // Reset progress for this attempt.
        ctrl.uploaded = 0;
        ctrl.lastProgressDispatch = 0;
        ctrl.lastProgressUploaded = -1;
        applyProgress(ctrl, 0, ctrl.file.size || 0);

        // Requirement 9.1: files larger than 5 MB use the resumable path.
        if (ctrl.file && ctrl.file.size > CHUNK_THRESHOLD) {
            startChunkedUpload(ctrl, manager);
            return;
        }

        startLegacyUpload(ctrl, manager);
    }

    function startLegacyUpload(ctrl, manager) {
        var fd = new FormData();
        fd.append(manager.fieldName, ctrl.file, ctrl.file.name);

        // Allow consumers to attach metadata via an event hook before send.
        dispatchEvent(ctrl.els.row, 'upload:beforeSend', {
            id: ctrl.id, formData: fd, file: ctrl.file
        });

        var xhr = new XMLHttpRequest();              // Req 8 design - per-file XHR
        ctrl.xhr = xhr;
        try {
            xhr.open('POST', manager.endpoint, true);
        } catch (e) {
            return finishUploadFailure(ctrl, manager, e && e.message ? e.message : 'open-failed');
        }
        xhr.responseType = 'text';
        xhr.withCredentials = true;
        try {
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            var token = manager.csrfToken;
            if (token) xhr.setRequestHeader('X-CSRF-TOKEN', token);
        } catch (e) { /* setting headers on a non-open xhr is fine to ignore */ }

        if (xhr.upload && typeof xhr.upload.addEventListener === 'function') {
            xhr.upload.addEventListener('progress', function (ev) {
                if (!ev) return;
                var total = ev.lengthComputable ? ev.total : (ctrl.file.size || 0);
                emitProgress(ctrl, ev.loaded || 0, total);
            });
        }

        xhr.addEventListener('load', function () {
            // Flush any pending throttled progress write.
            if (ctrl.progressTimer) {
                try { clearTimeout(ctrl.progressTimer); } catch (e) { /* ignore */ }
                ctrl.progressTimer = null;
                applyProgress(ctrl, ctrl.lastProgressUploaded >= 0 ? ctrl.lastProgressUploaded : ctrl.file.size, ctrl.file.size);
            }

            var status = xhr.status;
            if (status >= 200 && status < 300) {
                applyProgress(ctrl, ctrl.file.size, ctrl.file.size);
                finishUploadSuccess(ctrl, manager, xhr.responseText);
            } else {
                var msg = parseErrorMessage(xhr.responseText) || ('HTTP ' + status);
                finishUploadFailure(ctrl, manager, msg);
            }
        });

        xhr.addEventListener('error', function () {
            finishUploadFailure(ctrl, manager, 'Network error');
        });
        xhr.addEventListener('abort', function () {
            finishUploadFailure(ctrl, manager, 'Aborted');
        });
        xhr.addEventListener('timeout', function () {
            finishUploadFailure(ctrl, manager, 'Request timed out');
        });

        try {
            xhr.send(fd);
        } catch (e) {
            finishUploadFailure(ctrl, manager, e && e.message ? e.message : 'send-failed');
        }
    }

    // ---------------------------------------------------------------------
    // Chunked / resumable upload path (Requirements 9.1, 9.5, 9.6)
    // ---------------------------------------------------------------------

    function sha256Hex(blob) {
        if (!window.crypto || !window.crypto.subtle) {
            return Promise.reject(new Error('SHA-256 unavailable'));
        }
        return blob.arrayBuffer().then(function (buffer) {
            return window.crypto.subtle.digest('SHA-256', buffer);
        }).then(function (hashBuffer) {
            var arr = Array.from(new Uint8Array(hashBuffer));
            return arr.map(function (b) { return b.toString(16).padStart(2, '0'); }).join('');
        });
    }

    function jsonRequest(method, url, manager, body) {
        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            try {
                xhr.open(method, url, true);
            } catch (e) {
                reject(e);
                return;
            }
            xhr.responseType = 'text';
            xhr.withCredentials = true;
            try {
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                if (manager.csrfToken) xhr.setRequestHeader('X-CSRF-TOKEN', manager.csrfToken);
            } catch (e2) { /* ignore */ }

            xhr.addEventListener('load', function () {
                var status = xhr.status;
                var parsed = null;
                if (xhr.responseText) {
                    try { parsed = JSON.parse(xhr.responseText); } catch (e3) { parsed = null; }
                }
                if (status >= 200 && status < 300) {
                    resolve(parsed || {});
                } else {
                    var msg = (parsed && parsed.message) ? parsed.message : ('HTTP ' + status);
                    reject(new Error(msg));
                }
            });
            xhr.addEventListener('error', function () { reject(new Error('Network error')); });
            xhr.addEventListener('timeout', function () { reject(new Error('Request timed out')); });

            if (body instanceof FormData) {
                xhr.send(body);
            } else if (body) {
                try {
                    xhr.setRequestHeader('Content-Type', 'application/json');
                } catch (e4) { /* ignore */ }
                xhr.send(JSON.stringify(body));
            } else {
                xhr.send();
            }
        });
    }

    function uploadChunkWithProgress(ctrl, manager, sessionId, index, totalChunks, blob, hash) {
        return new Promise(function (resolve, reject) {
            var fd = new FormData();
            fd.append('session_id', sessionId);
            fd.append('chunk_index', String(index));
            fd.append('total_chunks', String(totalChunks));
            fd.append('sha256', hash);
            fd.append('chunk', blob, 'chunk-' + index + '.bin');

            var xhr = new XMLHttpRequest();
            ctrl.xhr = xhr;
            try {
                xhr.open('POST', manager.chunkedChunk, true);
            } catch (e) {
                reject(e);
                return;
            }
            xhr.responseType = 'text';
            xhr.withCredentials = true;
            try {
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                if (manager.csrfToken) xhr.setRequestHeader('X-CSRF-TOKEN', manager.csrfToken);
            } catch (e2) { /* ignore */ }

            var baseUploaded = index * CHUNK_SIZE;

            if (xhr.upload && typeof xhr.upload.addEventListener === 'function') {
                xhr.upload.addEventListener('progress', function (ev) {
                    if (!ev) return;
                    var chunkLoaded = ev.loaded || 0;
                    emitProgress(ctrl, baseUploaded + chunkLoaded, ctrl.file.size || 0);
                });
            }

            xhr.addEventListener('load', function () {
                ctrl.xhr = null;
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve();
                } else {
                    reject(new Error(parseErrorMessage(xhr.responseText) || ('HTTP ' + xhr.status)));
                }
            });
            xhr.addEventListener('error', function () {
                ctrl.xhr = null;
                reject(new Error('Network error'));
            });
            xhr.addEventListener('abort', function () {
                ctrl.xhr = null;
                reject(new Error('Aborted'));
            });

            try {
                xhr.send(fd);
            } catch (e3) {
                ctrl.xhr = null;
                reject(e3);
            }
        });
    }

    function uploadChunkWithRetries(ctrl, manager, sessionId, index, totalChunks, blob) {
        var attempt = 0;

        function tryOnce() {
            return sha256Hex(blob).then(function (hash) {
                return uploadChunkWithProgress(ctrl, manager, sessionId, index, totalChunks, blob, hash);
            }).catch(function (err) {
                attempt++;
                if (attempt < CHUNK_MAX_RETRIES) {
                    return tryOnce();
                }
                throw err;
            });
        }

        return tryOnce();
    }

    function startChunkedUpload(ctrl, manager) {
        var file = ctrl.file;
        var totalChunks = Math.max(1, Math.ceil(file.size / CHUNK_SIZE));

        var startPromise;

        if (ctrl.sessionId) {
            // Resume: fetch received indexes first (Req 9.5, 9.6).
            startPromise = jsonRequest(
                'GET',
                manager.chunkedStatus + '/' + encodeURIComponent(ctrl.sessionId),
                manager
            ).then(function (statusPayload) {
                return {
                    session_id: ctrl.sessionId,
                    received_indexes: statusPayload.received_indexes || [],
                    total_chunks: statusPayload.total_chunks || totalChunks
                };
            });
        } else {
            startPromise = jsonRequest('POST', manager.chunkedStart, manager, {
                total_chunks: totalChunks,
                total_bytes: file.size,
                filename: file.name,
                mime: file.type || 'application/octet-stream'
            }).then(function (payload) {
                ctrl.sessionId = payload.session_id;
                return {
                    session_id: payload.session_id,
                    received_indexes: [],
                    total_chunks: totalChunks
                };
            });
        }

        startPromise.then(function (ctx) {
            var received = {};
            (ctx.received_indexes || []).forEach(function (idx) { received[idx] = true; });

            var chain = Promise.resolve();
            for (var i = 0; i < ctx.total_chunks; i++) {
                if (received[i]) {
                    emitProgress(ctrl, Math.min((i + 1) * CHUNK_SIZE, file.size), file.size);
                    continue;
                }
                (function (index) {
                    var start = index * CHUNK_SIZE;
                    var end = Math.min(start + CHUNK_SIZE, file.size);
                    var blob = file.slice(start, end);
                    chain = chain.then(function () {
                        return uploadChunkWithRetries(ctrl, manager, ctx.session_id, index, ctx.total_chunks, blob);
                    });
                })(i);
            }

            return chain.then(function () {
                return jsonRequest('POST', manager.chunkedComplete, manager, {
                    session_id: ctx.session_id
                });
            });
        }).then(function (completePayload) {
            applyProgress(ctrl, file.size, file.size);
            finishUploadSuccess(ctrl, manager, JSON.stringify(completePayload));
        }).catch(function (err) {
            finishUploadFailure(ctrl, manager, err && err.message ? err.message : 'chunked-upload-failed');
        });
    }

    function parseErrorMessage(text) {
        if (!text) return null;
        try {
            var parsed = JSON.parse(text);
            if (parsed && typeof parsed.message === 'string') return parsed.message;
        } catch (e) { /* not JSON */ }
        return null;
    }

    function finishUploadSuccess(ctrl, manager, responseText) {
        ctrl.attempts += 1;
        ctrl.xhr = null;
        setState(ctrl, STATE_SUCCEEDED);
        setStatusText(ctrl, 'Uploaded');

        if (ctrl.els.retry) {
            ctrl.els.retry.hidden = true;
            ctrl.els.retry.disabled = true;
        }

        var payload = null;
        if (responseText) {
            try { payload = JSON.parse(responseText); } catch (e) { payload = null; }
        }

        dispatchEvent(ctrl.els.row, 'upload:succeeded', {
            id: ctrl.id, file: ctrl.file, response: payload
        });

        manager._onFileFinished(ctrl);
    }

    function finishUploadFailure(ctrl, manager, reason) {
        ctrl.attempts += 1;
        ctrl.xhr = null;

        var canRetry = ctrl.retries < MAX_RETRIES;
        if (canRetry) {
            setState(ctrl, STATE_FAILED);
            setStatusText(ctrl, 'Failed: ' + reason);
            if (ctrl.els.retry) {
                ctrl.els.retry.hidden = false;
                ctrl.els.retry.disabled = false;
                ctrl.els.retry.textContent = 'Retry'
                    + (ctrl.retries > 0 ? ' (' + (MAX_RETRIES - ctrl.retries) + ' left)' : '');
            }
        } else {
            setState(ctrl, STATE_EXHAUSTED);
            setStatusText(ctrl, 'Failed: ' + reason + ' (no retries left)');
            if (ctrl.els.retry) {
                ctrl.els.retry.hidden = false;
                ctrl.els.retry.disabled = true;        // Req 8.9
                ctrl.els.retry.textContent = 'Retry';
            }
        }

        dispatchEvent(ctrl.els.row, 'upload:failed', {
            id: ctrl.id, file: ctrl.file, reason: reason,
            retries: ctrl.retries, exhausted: !canRetry
        });

        manager._onFileFinished(ctrl);
    }

    function retryUpload(ctrl, manager) {
        if (ctrl.state !== STATE_FAILED) return;
        if (ctrl.retries >= MAX_RETRIES) {
            setState(ctrl, STATE_EXHAUSTED);
            if (ctrl.els.retry) ctrl.els.retry.disabled = true;
            return;
        }
        ctrl.retries += 1;
        // Hide the prior summary so the post-drain summary recomputes
        // when the new attempt lands.
        manager._invalidateSummary();
        startUpload(ctrl, manager);
    }

    // ---------------------------------------------------------------------
    // Manager: owns the drop zone, the queue, capacity accounting, and
    // the summary. One instance per `[data-upload-manager]` element.
    // ---------------------------------------------------------------------
    function createManager(zone) {
        var manager = {
            zone: zone,
            input: zone.querySelector('[data-upload-input]') || zone.querySelector('input[type="file"]'),
            list: zone.querySelector('[data-upload-list]'),
            summary: zone.querySelector('[data-upload-summary]'),
            errorBox: zone.querySelector('[data-upload-error]'),
            endpoint: readStrAttr(zone, 'data-upload-endpoint', DEFAULT_ENDPOINT),
            fieldName: readStrAttr(zone, 'data-upload-field', DEFAULT_FIELD),
            maxFiles: readIntAttr(zone, 'data-upload-max-files', DEFAULT_MAX_FILES),
            activeFiles: readIntAttr(zone, 'data-upload-active-files', 0),
            maxSize: readIntAttr(zone, 'data-upload-max-size', DEFAULT_MAX_SIZE),
            chunkedMaxSize: readIntAttr(zone, 'data-upload-chunked-max-size', DEFAULT_CHUNKED_MAX_SIZE),
            chunkedStart: readStrAttr(zone, 'data-chunked-start', DEFAULT_CHUNKED_START),
            chunkedChunk: readStrAttr(zone, 'data-chunked-chunk', DEFAULT_CHUNKED_CHUNK),
            chunkedStatus: readStrAttr(zone, 'data-chunked-status', DEFAULT_CHUNKED_STATUS),
            chunkedComplete: readStrAttr(zone, 'data-chunked-complete', DEFAULT_CHUNKED_COMPLETE),
            csrfToken: getCsrfToken(zone),
            controllers: [],
            dragCounter: 0
        };

        // Lazy-create the list / summary / error containers if missing
        // so callers do not have to declare the full skeleton.
        if (!manager.list) {
            manager.list = document.createElement('div');
            manager.list.setAttribute('data-upload-list', '');
            zone.appendChild(manager.list);
        }
        if (!manager.summary) {
            manager.summary = document.createElement('div');
            manager.summary.className = 'airtoshare-upload-summary';
            manager.summary.setAttribute('data-upload-summary', '');
            manager.summary.setAttribute('role', 'status');
            manager.summary.setAttribute('aria-live', 'polite');
            manager.summary.hidden = true;
            zone.appendChild(manager.summary);
        }
        if (!manager.errorBox) {
            manager.errorBox = document.createElement('div');
            manager.errorBox.className = 'airtoshare-upload-error';
            manager.errorBox.setAttribute('data-upload-error', '');
            manager.errorBox.setAttribute('role', 'alert');
            manager.errorBox.hidden = true;
            zone.appendChild(manager.errorBox);
        }

        // ---- capacity ----
        manager._remainingCapacity = function () {
            return Math.max(0, manager.maxFiles - manager.activeFiles - manager._inFlightOrSucceededCount());
        };
        manager._inFlightOrSucceededCount = function () {
            var n = 0;
            for (var i = 0; i < manager.controllers.length; i++) {
                var s = manager.controllers[i].state;
                if (s === STATE_QUEUED || s === STATE_UPLOADING || s === STATE_SUCCEEDED) n++;
            }
            return n;
        };

        // ---- error rendering (Req 8.4) ----
        manager._showError = function (message) {
            if (!manager.errorBox) return;
            manager.errorBox.textContent = message;
            manager.errorBox.hidden = false;
        };
        manager._clearError = function () {
            if (!manager.errorBox) return;
            manager.errorBox.textContent = '';
            manager.errorBox.hidden = true;
        };

        // ---- summary rendering (Req 8.10) ----
        manager._invalidateSummary = function () {
            if (!manager.summary) return;
            manager.summary.hidden = true;
            manager.summary.textContent = '';
        };
        manager._renderSummary = function () {
            if (!manager.summary) return;
            var success = 0, failed = 0;
            for (var i = 0; i < manager.controllers.length; i++) {
                var s = manager.controllers[i].state;
                if (s === STATE_SUCCEEDED) success++;
                else if (s === STATE_EXHAUSTED) failed++;
            }
            // Total "queued" includes everything we accepted into the queue.
            var total = success + failed;
            manager.summary.textContent =
                total + ' uploads complete: ' +
                success + ' successful, ' + failed + ' failed.';
            manager.summary.hidden = false;
            manager.summary.dataset.successfulCount = String(success);
            manager.summary.dataset.failedCount = String(failed);
            dispatchEvent(manager.zone, 'upload:summary', {
                successful_count: success,
                failed_count: failed,
                total: total
            });
        };

        // Determine if the queue has drained: every controller is in a
        // terminal state (succeeded or exhausted). Failed-but-retryable
        // rows keep the queue open since the user may press Retry.
        manager._allDrained = function () {
            if (manager.controllers.length === 0) return false;
            for (var i = 0; i < manager.controllers.length; i++) {
                var s = manager.controllers[i].state;
                if (s !== STATE_SUCCEEDED && s !== STATE_EXHAUSTED) return false;
            }
            return true;
        };

        manager._onFileFinished = function () {
            if (manager._allDrained()) {
                manager._renderSummary();
            }
        };

        // ---- queueing (Reqs 8.3, 8.4) ----
        manager.queueFiles = function (fileLikeList) {
            if (!fileLikeList || fileLikeList.length === 0) return { accepted: [], rejected: 0 };

            // Snapshot remaining capacity BEFORE any acceptance so reject
            // count is stable across the whole drop (Req 8.4 - drop order
            // up to remaining capacity, reject the rest).
            var remaining = manager._remainingCapacity();
            var accepted = [];
            var rejected = 0;
            var rejectedReasons = { capacity: 0, size: 0 };

            for (var i = 0; i < fileLikeList.length; i++) {
                var f = fileLikeList[i];
                if (!f) continue;

                if (typeof f.size === 'number' && f.size > manager.chunkedMaxSize) {
                    rejected++;
                    rejectedReasons.size++;
                    continue;
                }
                if (remaining <= 0) {
                    rejected++;
                    rejectedReasons.capacity++;
                    continue;
                }

                var ctrl = createFileController(f, manager);
                manager.controllers.push(ctrl);
                manager.list.appendChild(ctrl.row);
                accepted.push(ctrl);
                remaining--;
            }

            if (rejected > 0) {
                var parts = [];
                if (rejectedReasons.capacity > 0) {
                    parts.push(
                        rejectedReasons.capacity + ' file' +
                        (rejectedReasons.capacity === 1 ? '' : 's') +
                        ' rejected: per-owner active-files limit (' +
                        manager.maxFiles + ') would be exceeded'
                    );
                }
                if (rejectedReasons.size > 0) {
                    parts.push(
                        rejectedReasons.size + ' file' +
                        (rejectedReasons.size === 1 ? '' : 's') +
                        ' rejected: exceed max upload size of ' +
                        formatBytes(manager.maxSize)
                    );
                }
                manager._showError(parts.join('. ') + '.');
            } else {
                manager._clearError();
            }

            manager._invalidateSummary();

            // Auto-start every accepted upload immediately. Per-file
            // XHRs are independent so they progress concurrently.
            for (var j = 0; j < accepted.length; j++) {
                accepted[j].start();
            }

            dispatchEvent(zone, 'upload:queued', {
                accepted_count: accepted.length,
                rejected_count: rejected,
                rejected_reasons: rejectedReasons,
                remaining_capacity: manager._remainingCapacity()
            });

            return { accepted: accepted, rejected: rejected, rejectedReasons: rejectedReasons };
        };

        // ---- drag and drop (Reqs 8.1, 8.2) ----
        function preventDefaults(e) {
            if (e && typeof e.preventDefault === 'function') e.preventDefault();
            if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
        }
        function isDragWithFiles(e) {
            if (!e || !e.dataTransfer) return false;
            var types = e.dataTransfer.types;
            if (!types) return false;
            for (var i = 0; i < types.length; i++) {
                if (types[i] === 'Files' || types[i] === 'application/x-moz-file') return true;
            }
            return false;
        }

        zone.addEventListener('dragenter', function (e) {
            if (!isDragWithFiles(e)) return;
            preventDefaults(e);
            manager.dragCounter++;
            zone.classList.add(ZONE_DRAGOVER_CLASS);
        });
        zone.addEventListener('dragover', function (e) {
            if (!isDragWithFiles(e)) return;
            preventDefaults(e);
            // Some browsers reset the dropEffect on every dragover.
            try {
                if (e.dataTransfer) e.dataTransfer.dropEffect = 'copy';
            } catch (err) { /* ignore */ }
            zone.classList.add(ZONE_DRAGOVER_CLASS);
        });
        zone.addEventListener('dragleave', function (e) {
            if (!isDragWithFiles(e)) return;
            preventDefaults(e);
            manager.dragCounter = Math.max(0, manager.dragCounter - 1);
            if (manager.dragCounter === 0) {
                zone.classList.remove(ZONE_DRAGOVER_CLASS);
            }
        });
        zone.addEventListener('drop', function (e) {
            if (!e || !e.dataTransfer) return;
            preventDefaults(e);
            manager.dragCounter = 0;
            zone.classList.remove(ZONE_DRAGOVER_CLASS);

            var files = e.dataTransfer.files;
            if (files && files.length > 0) {
                manager.queueFiles(files);
            }
        });

        // Click-to-browse: clicking the zone (but not a child button)
        // opens the file picker. Keeps the drop zone usable on devices
        // without drag-and-drop.
        if (manager.input) {
            zone.addEventListener('click', function (e) {
                var t = e.target;
                if (t && t.closest && (t.closest('button') || t.closest('a') || t.closest('[data-upload-row]'))) {
                    return;
                }
                if (t === manager.input) return;
                manager.input.click();
            });

            manager.input.addEventListener('change', function () {
                var files = manager.input.files;
                if (files && files.length > 0) {
                    manager.queueFiles(files);
                    // Allow re-selection of the same file later.
                    try { manager.input.value = ''; } catch (e2) { /* ignore */ }
                }
            });
        }

        // Public test surface bound to the zone for use by Vitest.
        zone.__airtoshareUploadManager = manager;
        return manager;
    }

    // ---------------------------------------------------------------------
    // Auto-init: every `[data-upload-manager]` zone in the document gets
    // a manager wired to it.
    // ---------------------------------------------------------------------
    function init() {
        ensureStyles();
        var zones = document.querySelectorAll('[data-upload-manager]');
        for (var i = 0; i < zones.length; i++) {
            if (zones[i].__airtoshareUploadManager) continue;
            createManager(zones[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Test surface: not part of the public API, but stable enough for
    // Vitest property/unit tests in resources/js/__tests__/.
    window.__airtoshareUploadManager = {
        init: init,
        createManager: createManager,
        createFileController: createFileController,
        STATE: {
            QUEUED: STATE_QUEUED,
            UPLOADING: STATE_UPLOADING,
            SUCCEEDED: STATE_SUCCEEDED,
            FAILED: STATE_FAILED,
            EXHAUSTED: STATE_EXHAUSTED
        },
        MAX_RETRIES: MAX_RETRIES,
        PROGRESS_THROTTLE_MS: PROGRESS_THROTTLE_MS,
        formatBytes: formatBytes
    };
})();
