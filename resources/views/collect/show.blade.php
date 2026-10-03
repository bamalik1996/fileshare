@extends('layouts.app')

@section('title', ($share->collect_title ?: 'Send files') . ' – AirToShare')
@section('description', 'Upload files securely to this AirToShare file request.')

@section('content')
    <div class="account-page">
        @include('partials.brand-header', ['branding' => $branding ?? null])
        <div class="account-hero">
            <h1 class="account-title">
                <i class="fas fa-inbox" aria-hidden="true"></i>
                {{ $share->collect_title ?: 'Send files' }}
            </h1>
            <p class="account-subtitle">
                @if ($share->collect_instructions)
                    {{ $share->collect_instructions }}
                @else
                    Drop your files below to send them securely. No account needed.
                @endif
            </p>
        </div>

        <div class="modern-card">
            <div id="collect-dropzone" class="collect-dropzone" tabindex="0" role="button"
                aria-label="Drag and drop files here or click to choose">
                <i class="fas fa-cloud-upload-alt collect-dropzone-icon" aria-hidden="true"></i>
                <p class="collect-dropzone-text">Drag &amp; drop files here, or <strong>click to choose</strong></p>
                <p class="collect-dropzone-hint">Up to 25 MB per file</p>
                <input type="file" id="collect-input" multiple hidden>
            </div>

            <ul id="collect-queue" class="collect-queue" aria-live="polite"></ul>
        </div>
    </div>

    <style>
        .collect-dropzone { border: 2px dashed rgba(128,128,128,.45); border-radius: 14px; padding: 2.5rem 1rem; text-align: center; cursor: pointer; transition: border-color .15s, background .15s; }
        .collect-dropzone.is-dragover { border-color: #6366f1; background: rgba(99,102,241,.08); }
        .collect-dropzone-icon { font-size: 2.5rem; opacity: .7; margin-bottom: .5rem; }
        .collect-dropzone-text { margin: .25rem 0; }
        .collect-dropzone-hint { font-size: .8rem; opacity: .6; margin: 0; }
        .collect-queue { list-style: none; padding: 0; margin: 1.25rem 0 0; }
        .collect-item { display: flex; align-items: center; gap: .75rem; padding: .6rem 0; border-bottom: 1px dashed rgba(128,128,128,.2); font-size: .9rem; }
        .collect-item-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .collect-item-bar { width: 120px; height: 8px; background: rgba(128,128,128,.2); border-radius: 999px; overflow: hidden; flex-shrink: 0; }
        .collect-item-fill { height: 100%; width: 0; background: linear-gradient(90deg,#6366f1,#8b5cf6); transition: width .2s; }
        .collect-item-status { width: 90px; text-align: right; flex-shrink: 0; font-size: .8rem; }
        .collect-item.is-done .collect-item-status { color: #22c55e; }
        .collect-item.is-error .collect-item-status { color: #ef4444; }
    </style>

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
