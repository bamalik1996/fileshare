@extends('layouts.app')

@section('title', ($share->collect_title ?: 'Send files') . ' – AirToShare')
@section('description', $share->collect_instructions ?: 'Upload files securely to this AirToShare file request.')
@section('robots', 'noindex, nofollow')

@section('content')
    @php
        $branding = $branding ?? null;
        $hasBrand = is_array($branding) && (
            ! empty($branding['logo_url'])
            || ! empty($branding['message'])
            || ! empty($branding['color'])
        );
        $brandColor = (! empty($branding['color']) && preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $branding['color']))
            ? $branding['color']
            : null;
    @endphp

    <div class="branded-request-page @if ($hasBrand) is-branded @endif"
        id="airtoshare-collect-view"
        @if ($brandColor) style="--brand-accent: {{ $brandColor }};" @endif>

        @include('partials.brand-header', ['branding' => $branding])

        <div class="branded-request-shell">
            <header class="branded-request-header">
                <span class="branded-request-kicker">
                    <i class="fas fa-inbox" aria-hidden="true"></i>
                    File request
                </span>
                <h1 class="branded-request-title">
                    {{ $share->collect_title ?: 'Send files' }}
                </h1>
                <p class="branded-request-subtitle">
                    @if ($share->collect_instructions)
                        {{ $share->collect_instructions }}
                    @else
                        Drop your files below to send them securely. No account needed.
                    @endif
                </p>
            </header>

            <div class="modern-card branded-request-card">
                <div id="collect-dropzone" class="collect-dropzone" tabindex="0" role="button"
                    aria-label="Drag and drop files here or click to choose">
                    <div class="collect-dropzone-icon-wrap" aria-hidden="true">
                        <i class="fas fa-cloud-upload-alt collect-dropzone-icon"></i>
                    </div>
                    <p class="collect-dropzone-text">Drag &amp; drop files here, or <strong>click to choose</strong></p>
                    <p class="collect-dropzone-hint">Up to 25 MB per file · Images, video, audio, PDF, Office, ZIP</p>
                    <input type="file" id="collect-input" multiple hidden
                        accept="image/*,video/*,audio/*,application/pdf,text/plain,.doc,.docx,.zip,.rar">
                </div>

                <ul id="collect-queue" class="collect-queue" aria-live="polite"></ul>
            </div>

            <footer class="branded-request-trust">
                <i class="fas fa-shield-alt" aria-hidden="true"></i>
                <span>Files are scanned and delivered securely via <strong>AirToShare</strong>.</span>
            </footer>
        </div>
    </div>

    <script>
        (function () {
            var csrf = document.querySelector('meta[name="csrf-token"]');
            var csrfToken = csrf ? csrf.content : '';
            var uploadUrl = @json(route('collect.upload', $share->collect_slug));
            var maxBytes = 25 * 1024 * 1024;

            var dropzone = document.getElementById('collect-dropzone');
            var input = document.getElementById('collect-input');
            var queue = document.getElementById('collect-queue');

            function human(bytes) {
                if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(1) + ' GB';
                if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
                if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
                return bytes + ' B';
            }

            function addRow(file) {
                var li = document.createElement('li');
                li.className = 'collect-item';
                li.innerHTML =
                    '<i class="fas fa-file" aria-hidden="true"></i>' +
                    '<span class="collect-item-name"></span>' +
                    '<span class="collect-item-bar"><span class="collect-item-fill"></span></span>' +
                    '<span class="collect-item-status">0%</span>';
                li.querySelector('.collect-item-name').textContent = file.name + ' (' + human(file.size) + ')';
                queue.appendChild(li);
                return li;
            }

            function upload(file) {
                var li = addRow(file);
                var fill = li.querySelector('.collect-item-fill');
                var status = li.querySelector('.collect-item-status');

                if (file.size > maxBytes) {
                    li.classList.add('is-error');
                    status.textContent = 'Too large';
                    return;
                }

                var form = new FormData();
                form.append('file', file);

                var xhr = new XMLHttpRequest();
                xhr.open('POST', uploadUrl, true);
                xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                xhr.setRequestHeader('Accept', 'application/json');

                xhr.upload.onprogress = function (e) {
                    if (e.lengthComputable) {
                        var pct = Math.round((e.loaded / e.total) * 100);
                        fill.style.width = pct + '%';
                        status.textContent = pct + '%';
                    }
                };

                xhr.onload = function () {
                    var ok = false;
                    try { ok = JSON.parse(xhr.responseText).status === 'success'; } catch (e) {}
                    if (xhr.status >= 200 && xhr.status < 300 && ok) {
                        li.classList.add('is-done');
                        fill.style.width = '100%';
                        status.textContent = 'Sent';
                        if (typeof window.showToast === 'function') {
                            window.showToast('success', 'Uploaded', file.name + ' sent.');
                        }
                    } else {
                        li.classList.add('is-error');
                        var msg = 'Failed';
                        try { msg = JSON.parse(xhr.responseText).message || msg; } catch (e) {}
                        status.textContent = msg.length > 14 ? 'Failed' : msg;
                    }
                };

                xhr.onerror = function () {
                    li.classList.add('is-error');
                    status.textContent = 'Failed';
                };

                xhr.send(form);
            }

            function handleFiles(files) {
                Array.prototype.forEach.call(files, upload);
            }

            dropzone.addEventListener('click', function () { input.click(); });
            dropzone.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); }
            });
            input.addEventListener('change', function () {
                handleFiles(input.files);
                input.value = '';
            });

            ['dragenter', 'dragover'].forEach(function (evt) {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault(); e.stopPropagation();
                    dropzone.classList.add('is-dragover');
                });
            });
            ['dragleave', 'drop'].forEach(function (evt) {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault(); e.stopPropagation();
                    dropzone.classList.remove('is-dragover');
                });
            });
            dropzone.addEventListener('drop', function (e) {
                if (e.dataTransfer && e.dataTransfer.files) {
                    handleFiles(e.dataTransfer.files);
                }
            });
        })();
    </script>
@endsection
