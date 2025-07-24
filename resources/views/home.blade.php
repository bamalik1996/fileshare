@extends('layouts.app')

@section('title', 'AirToShare - Instant File Sharing Across Devices | Local Network File Transfer')
@section('description', 'AirToShare - Share files and text instantly across devices on the same Wi-Fi network. No accounts, no external servers - just secure peer-to-peer file sharing up to 10MB per file. Follow us on Facebook!')
@section('keywords', 'file sharing, instant sharing, local network, Wi-Fi sharing, cross-device, secure sharing, peer-to-peer, no account required')

@section('schema')
    <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebApplication",
  "name": "AirToShare",
  "description": "Instant file sharing across devices on the same network",
  "url": "{{ url('/') }}",
  "applicationCategory": "UtilitiesApplication",
  "operatingSystem": "Web Browser",
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "USD"
  },
  "featureList": [
    "Instant file sharing",
    "Text sharing",
    "Cross-device compatibility",
    "No account required",
    "Local network only",
    "Secure file transfer"
  ],
  "browserRequirements": "Requires JavaScript. Requires HTML5.",
  "softwareVersion": "1.0",
  "author": {
    "@type": "Organization",
    "name": "AirToShare"
  }
}
</script>
@endsection

@section('content')

    <!-- Hero Section -->
    <div class="hero-section">
        <h1 class="hero-title">
            <i class="fas fa-paper-plane" style="color: var(--primary-color);"></i>
            AirToShare
        </h1>
        <p class="hero-subtitle">
            Share files and text instantly across devices on the same network.
            Simple, fast, and secure file sharing without the hassle.
        </p>
    </div>

    <!-- IP Info Panel -->
    <div class="info-panel">
        <div class="info-item">
            <i class="fas fa-network-wired"></i>
            <strong>IP:</strong> <span id="userIp">Loading...</span>
        </div>
        <div class="info-item">
            <i class="fas fa-folder"></i>
            <strong>Files:</strong> <span id="fileCount">0</span>/<span id="maxFiles">20</span>
        </div>
        <div class="info-item">
            <i class="fas fa-weight-hanging"></i>
            <strong>Max Size:</strong> <span id="maxFileSize">10 MB</span>
        </div>
        <div class="info-item">
            <i class="fas fa-clock"></i>
            <strong>Auto-delete:</strong> 6 hours
        </div>
    </div>

    <!-- Modern Tabs -->
    <div class="modern-tabs">
        <button class="modern-tab active" data-tab="text">
            <i class="fas fa-edit"></i>
            Text Sharing
        </button>
        <button class="modern-tab" data-tab="file">
            <i class="fas fa-file-upload"></i>
            File Sharing
        </button>
    </div>

    <!-- Tab Contents -->
    <div class="modern-card">
        <!-- Text Tab -->
        <div class="tab-content active" id="text-tab">
            <div class="text-container">
                <textarea class="modern-textarea" id="textInput"
                    placeholder="Type or paste your text here... Links will be automatically detected and made clickable."
                    maxlength="50000"></textarea>

                <div class="textarea-footer">
                    <div class="char-counter" id="charCounter">0 / 50,000 characters</div>
                    <div class="button-group">
                        <button class="modern-btn danger" id="clearBtn" style="display: none;">
                            <i class="fas fa-trash"></i>
                            Clear
                        </button>
                        <button class="modern-btn" id="saveBtn">
                            {{-- <i class="fas fa-save"></i> --}}
                            <span id="saveBtnText">Save</span>
                            <div class="loading-spinner" id="saveLoader" style="display: none;"></div>
                        </button>
                    </div>
                </div>

                <div class="links-container" id="linksContainer" style="display: none;">
                    <strong><i class="fas fa-link"></i> Detected Links:</strong>
                    <div id="linksList"></div>
                </div>

                <div class="message success" id="textSuccessMessage">
                    <i class="fas fa-check-circle"></i>
                    <span></span>
                </div>
                <div class="message error" id="textErrorMessage">
                    <i class="fas fa-exclamation-circle"></i>
                    <span></span>
                </div>
            </div>
        </div>

        <!-- File Tab -->
        <div class="tab-content" id="file-tab">
            <div class="file-container">
                <div class="upload-zone" id="uploadZone">
                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                    <div class="upload-text">Drag & Drop Files Here</div>
                    <div class="upload-subtext">
                        or click to browse • Max 10MB per file • Up to 20 files
                        <br>
                        <small>Supported: Images, PDF, DOC, TXT, ZIP</small>
                    </div>
                    <input type="file" id="fileInput" multiple accept="image/*,.pdf,.docx,.txt,.zip"
                        style="display: none;">

                    <div class="progress-container" id="progressContainer">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                        <div class="progress-text" id="progressText">Uploading...</div>
                    </div>
                </div>

                <div class="message success" id="fileSuccessMessage">
                    <i class="fas fa-check-circle"></i>
                    <span></span>
                </div>
                <div class="message error" id="fileErrorMessage">
                    <i class="fas fa-exclamation-circle"></i>
                    <span></span>
                </div>
                <br />
                <!-- File Controls -->
                <div class="file-controls" id="fileControls" style="display: none;">
                    <div class="selection-info">
                        <button class="select-all-btn" id="selectAllBtn">
                            <i class="fas fa-check-square"></i>
                            Select All
                        </button>
                        <span id="selectionCount">0 files selected</span>
                    </div>
                    <div class="download-controls">
                        <button class="download-btn" id="downloadSelectedBtn" disabled>
                            <i class="fas fa-download"></i>
                            Download
                        </button>
                        <button class="download-btn email-btn" id="emailSelectedBtn" disabled>
                            <i class="fas fa-envelope"></i>
                            Email
                        </button>
                        <button class="modern-btn danger" id="removeAllBtn" style="display: none;">
                            <i class="fas fa-trash-alt"></i>
                            Remove All Files
                        </button>
                    </div>
                </div>
                <div class="file-grid" id="fileGrid">
                    <div class="empty-state">
                        <i class="fas fa-folder-open"
                            style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                        <p>No files uploaded yet. Start by dragging files above!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fullscreen Preview -->
    <div class="fullscreen-overlay" id="fullscreenOverlay">
        <div class="fullscreen-content">
            <button class="fullscreen-close" id="fullscreenClose">
                <i class="fas fa-times"></i>
            </button>
            <img class="fullscreen-image" id="fullscreenImage" src="" alt="Preview">
        </div>
    </div>

    <!-- Email Modal -->
    <div class="modal-overlay" id="emailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-envelope"></i>
                    Email Files
                </h3>
                <button class="modal-close" id="emailModalClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="emailForm">
                <div class="form-group">
                    <label class="form-label">To Email:</label>
                    <input type="email" class="form-input" id="toEmail" placeholder="recipient@example.com"
                        required>
                </div>
                <div class="form-group">
                    <label class="form-label">Subject:</label>
                    <input type="text" class="form-input" id="emailSubject" value="Shared Files from AirToShare">
                </div>
                <div class="form-group">
                    <label class="form-label">Message:</label>
                    <textarea class="form-input" id="emailMessage" rows="4" placeholder="Optional message..."></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" class="modern-btn" id="sendEmailBtn">
                        <i class="fas fa-paper-plane"></i>
                        <span id="sendEmailText">Send Email</span>
                        <div class="loading-spinner" id="emailLoader" style="display: none;"></div>
                    </button>
                    <button class="download-btn danger-btn" id="removeAllBtn">
                        <i class="fas fa-trash-alt"></i>
                        Remove All
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Remove All Confirmation Modal -->
    <div class="modal-overlay" id="removeAllModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" style="color: var(--error-color);">
                    <i class="fas fa-exclamation-triangle"></i>
                    Remove All Files
                </h3>
                <button class="modal-close" id="removeAllModalClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="padding: 1rem 0;">
                <p style="color: var(--text-primary); font-size: 1.1rem; margin-bottom: 1rem;">
                    <strong>Are you sure you want to remove all files?</strong>
                </p>
                <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                    This action will permanently delete all <span id="totalFilesCount">0</span> files from your session.
                    This cannot be undone.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button class="modern-btn secondary" id="cancelRemoveAll">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button class="modern-btn danger" id="confirmRemoveAll">
                        <i class="fas fa-trash-alt"></i>
                        <span id="removeAllText">Remove All Files</span>
                        <div class="loading-spinner" id="removeAllLoader" style="display: none;"></div>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            initializeApp();
        });

        let selectedFiles = new Set();

        function initializeApp() {
            loadIpInfo();
            fetchText();
            fetchMedia();
            setupEventListeners();
        }

        function setupEventListeners() {
            // Tab switching
            $('.modern-tab').click(function() {
                const tabName = $(this).data('tab');
                switchTab(tabName);
            });

            // Text input events
            $('#textInput').on('input', handleTextInput);
            $('#saveBtn').off('click.save').on('click.save', handleSaveText);
            $('#clearBtn').off('click').on('click', handleClearText);

            // File upload events
            setupFileUpload();

            // File selection events
            setupFileSelection();

            // Email modal events
            setupEmailModal();
        }

        function switchTab(tabName) {
            $('.modern-tab').removeClass('active');
            $(`.modern-tab[data-tab="${tabName}"]`).addClass('active');

            $('.tab-content').removeClass('active');
            $(`#${tabName}-tab`).addClass('active');
        }

        function loadIpInfo() {
            $.ajax({
                url: '/api/v1/ip-info',
                method: 'GET',
                success: function(data) {
                    $('#userIp').text(data.ip);
                    $('#fileCount').text(data.files_count);
                    $('#maxFiles').text(data.max_files);
                    $('#maxFileSize').text(data.max_file_size);
                },
                error: function() {
                    console.error('Failed to load IP info');
                }
            });
        }

        function fetchText() {
            $.ajax({
                url: '{{ route('share.get.text') }}',
                method: 'GET',
                success: function(data) {
                    if (data.status === 'success') {
                        $('#textInput').val(data.text);
                        handleTextInput();
                        if (data.text && data.text.trim().length > 0) {
                            updateSaveButton('copy');
                        }
                    }
                },
                error: function() {
                    console.log('No existing text found');
                }
            });
        }

        function fetchMedia() {
            $.ajax({
                url: '{{ route('share.get.media') }}',
                method: 'GET',
                success: function(response) {
                    displayFiles(response.files || []);
                    $('#fileCount').text(Object.keys(response.files || {}).length);
                },
                error: function() {
                    console.error('Failed to fetch media');
                }
            });
        }

        function handleTextInput() {
            const text = $('#textInput').val();
            const length = text.length;

            // Update character counter
            updateCharCounter(length);

            // Show/hide clear button
            $('#clearBtn').toggle(length > 0);

            // Detect links
            detectLinks(text);

            // Update save button
            updateSaveButton('save');
        }

        function updateCharCounter(length) {
            const counter = $('#charCounter');
            counter.text(`${length.toLocaleString()} / 50,000 characters`);

            counter.removeClass('warning danger');
            if (length > 45000) {
                counter.addClass('danger');
            } else if (length > 40000) {
                counter.addClass('warning');
            }
        }

        function detectLinks(text) {
            const urlRegex = /(https?:\/\/[^\s]+)/g;
            const matches = text.match(urlRegex);
            const container = $('#linksContainer');
            const linksList = $('#linksList');

            if (matches && matches.length > 0) {
                linksList.empty();
                matches.forEach(url => {
                    const link = $('<a>', {
                        href: url,
                        target: '_blank',
                        class: 'detected-link',
                        text: url
                    });
                    linksList.append(link);
                });
                container.show();
            } else {
                container.hide();
            }
        }

        function updateSaveButton(mode) {
            const btn = $('#saveBtn');
            const text = $('#saveBtnText');

            if (mode === 'copy') {
                btn.removeClass('modern-btn').addClass('modern-btn success');
                text.html('<i class="fas fa-copy"></i> Copy');
                btn.off('click.save').on('click.save', handleCopyText);
            } else {
                btn.removeClass('success').addClass('modern-btn');
                text.html('<i class="fas fa-save"></i> Save');
                btn.off('click.save').on('click.save', handleSaveText);
            }
        }

        function handleSaveText() {
            const text = $('#textInput').val();

            if (text.length > 50000) {
                showMessage('textErrorMessage', 'Text is too long. Maximum 50,000 characters allowed.');
                return;
            }

            setButtonLoading(true);

            $.ajax({
                url: '{{ route('share.store.text') }}',
                method: 'POST',
                data: JSON.stringify({
                    text: text
                }),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    setButtonLoading(false);
                    showToast('success', 'Saved!', 'Text saved successfully and synced across devices');

                    if (text.trim().length > 0) {
                        updateSaveButton('copy');
                    }
                },
                error: function(xhr) {
                    setButtonLoading(false);
                    const message = xhr.responseJSON?.message || 'Error occurred while saving text.';
                    showToast('error', 'Save Failed!', message);
                }
            });
        }

        function handleCopyText() {
            const text = $('#textInput').val();

            navigator.clipboard.writeText(text).then(() => {
                showToast('success', 'Success!', 'Text copied to clipboard successfully');

                // Visual feedback
                const btn = $('#saveBtn');
                btn.addClass('success');
                setTimeout(() => btn.removeClass('success'), 1000);
            }).catch(() => {
                showToast('error', 'Error!', 'Failed to copy text to clipboard');
            });
        }

        function handleClearText() {
            if (confirm('Are you sure you want to clear all text?')) {
                $('#textInput').val('');
                handleTextInput();
                handleSaveText(); // Save empty text
            }
        }

        function setButtonLoading(loading) {
            const btn = $('#saveBtn');
            const text = $('#saveBtnText');
            const loader = $('#saveLoader');

            if (loading) {
                btn.prop('disabled', true);
                text.hide();
                loader.show();
            } else {
                btn.prop('disabled', false);
                text.show();
                loader.hide();
            }
        }

        function setupFileUpload() {
            const uploadZone = $('#uploadZone');
            const fileInput = $('#fileInput');

            // Prevent default drag behaviors globally
            $(document).on('dragenter dragover drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
            });

            // Fix click handler to prevent infinite loop
            uploadZone.off('click').on('click', function(e) {
                // Only trigger file input if not clicking on other elements
                if (e.target === this || $(e.target).hasClass('upload-icon') || $(e.target).hasClass(
                    'upload-text') || $(e.target).hasClass('upload-subtext')) {
                    fileInput.trigger('click');
                }
            });

            uploadZone.off('dragover').on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
            });

            uploadZone.off('dragleave').on('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                // Check if we're really leaving the upload zone
                const rect = this.getBoundingClientRect();
                const x = e.originalEvent.clientX;
                const y = e.originalEvent.clientY;

                if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
                    $(this).removeClass('dragover');
                }
            });

            uploadZone.off('drop').on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
                const files = e.originalEvent.dataTransfer.files;
                handleFileUpload(files);
            });

            fileInput.off('change').on('change', function() {
                handleFileUpload(this.files);
            });
        }

        function setupFileSelection() {
            $('#selectAllBtn').off('click').on('click', function() {
                const allSelected = selectedFiles.size === $('.file-item').length;
                if (allSelected) {
                    selectedFiles.clear();
                    $('.file-item').removeClass('selected');
                    $('.file-checkbox').prop('checked', false);
                    $(this).html('<i class="fas fa-check-square"></i> Select All');
                } else {
                    $('.file-item').each(function() {
                        const uuid = $(this).data('uuid');
                        selectedFiles.add(uuid);
                        $(this).addClass('selected');
                        $(this).find('.file-checkbox').prop('checked', true);
                    });
                    $(this).html('<i class="fas fa-minus-square"></i> Deselect All');
                }
                updateSelectionUI();
            });

            $('#downloadSelectedBtn').off('click').on('click', downloadSelectedFiles);
            $('#emailSelectedBtn').off('click').on('click', showEmailModal);
           $('#removeAllBtn').off('click').on('click', showRemoveAllModal);
        }

        function setupEmailModal() {
            $('#emailModalClose').off('click').on('click', hideEmailModal);
            $('#emailModal').off('click').on('click', function(e) {
                if (e.target === this) {
                    hideEmailModal();
                }
            });

            $('#emailForm').off('submit').on('submit', function(e) {
                e.preventDefault();
                sendEmailWithFiles();
            });

            // Remove All Modal events
            $('#removeAllModalClose, #cancelRemoveAll').off('click').on('click', hideRemoveAllModal);
            $('#removeAllModal').off('click').on('click', function(e) {
                if (e.target === this) {
                    hideRemoveAllModal();
                }
            });
            $('#confirmRemoveAll').off('click').on('click', removeAllFiles);
        }

        function handleFileUpload(files) {
            if (files.length === 0) return;

            const maxFiles = parseInt($('#maxFiles').text());
            const currentFiles = parseInt($('#fileCount').text());

            if (currentFiles + files.length > maxFiles) {
                showMessage('fileErrorMessage', `You can only upload up to ${maxFiles} files total.`);
                return;
            }

            Array.from(files).forEach(uploadFile);
        }

        function uploadFile(file) {
            const maxSize = 10 * 1024 * 1024; // 10MB

            if (file.size > maxSize) {
                showMessage('fileErrorMessage', `${file.name} exceeds the 10MB limit.`);
                return;
            }

            const formData = new FormData();
            formData.append('file', file);

            showProgress(true);

            $.ajax({
                url: '{{ route('share.store.media') }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const percent = (e.loaded / e.total) * 100;
                            updateProgress(percent);
                        }
                    });
                    return xhr;
                },
                success: function(data) {
                    showProgress(false);
                    showToast('success', 'Upload Complete!', `${file.name} uploaded successfully`);
                    fetchMedia();
                    loadIpInfo();
                },
                error: function(xhr) {
                    showProgress(false);
                    const message = xhr.responseJSON?.message || 'Upload failed.';
                    showToast('error', 'Upload Failed!', message);
                }
            });
        }

        function showProgress(show) {
            $('#progressContainer').toggle(show);
            if (!show) {
                $('#progressFill').css('width', '0%');
            }
        }

        function updateProgress(percent) {
            $('#progressFill').css('width', percent + '%');
            $('#progressText').text(`Uploading... ${Math.round(percent)}%`);
        }

        function displayFiles(files) {
            const grid = $('#fileGrid');
            const controls = $('#fileControls');
            grid.empty();
            selectedFiles.clear();

            if (!files || Object.keys(files).length === 0) {
                grid.addClass('empty').html(`
                    <div class="empty-state">
                        <i class="fas fa-folder-open" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                        <p>No files uploaded yet. Start by dragging files above!</p>
                    </div>
                `);
                controls.hide();
                return;
            }

            grid.removeClass('empty');
            controls.show();

            Object.values(files).forEach(file => {
                const fileItem = createFileItem(file);
                grid.append(fileItem);
            });

            updateSelectionUI();
           updateRemoveAllButton(Object.keys(files).length);
        }

        function updateRemoveAllButton(fileCount) {
            const removeAllBtn = $('#removeAllBtn');
            if (fileCount > 0) {
                removeAllBtn.show();
                $('#totalFilesCount').text(fileCount);
            } else {
                removeAllBtn.hide();
            }
        }

        function showRemoveAllModal() {
            const fileCount = $('.file-item').length;
            if (fileCount === 0) {
                showToast('info', 'No Files', 'No files to remove');
                return;
            }
            $('#totalFilesCount').text(fileCount);
            $('#removeAllModal').addClass('show');
        }

        function hideRemoveAllModal() {
            $('#removeAllModal').removeClass('show');
        }

        function removeAllFiles() {
            const btn = $('#confirmRemoveAll');
            const text = $('#removeAllText');
            const loader = $('#removeAllLoader');

            // Show loading state
            btn.prop('disabled', true);
            text.hide();
            loader.show();

            // Get all file UUIDs
            const allUuids = [];
            $('.file-item').each(function() {
                allUuids.push($(this).data('uuid'));
            });

            if (allUuids.length === 0) {
                hideRemoveAllModal();
                showToast('info', 'No Files', 'No files to remove');
                return;
            }

            // Delete all files one by one
            let deletedCount = 0;
            let totalFiles = allUuids.length;

            const deleteNext = () => {
                if (deletedCount >= totalFiles) {
                    // All files deleted
                    btn.prop('disabled', false);
                    text.show();
                    loader.hide();
                    hideRemoveAllModal();

                    showToast('success', 'All Files Removed!', `Successfully removed ${totalFiles} files`);
                    fetchMedia();
                    loadIpInfo();
                    selectedFiles.clear();
                    return;
                }

                const uuid = allUuids[deletedCount];

                $.ajax({
                    url: '{{ route('share.delete.media') }}',
                    method: 'DELETE',
                    data: { uuid: uuid },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function() {
                        deletedCount++;
                        deleteNext();
                    },
                    error: function() {
                        // Continue with next file even if one fails
                        deletedCount++;
                        deleteNext();
                    }
                });
            };

            deleteNext();
        }

        function createFileItem(file) {
            const isImage = file.mime_type.startsWith('image/');

            const item = $(`
                <div class="file-item" data-uuid="${file.uuid}">
                    <input type="checkbox" class="file-checkbox">
                    <div class="file-preview">
                        ${isImage ?
                            `<img src="${file.original_url}" alt="${file.name}">` :
                            `<i class="fas fa-file file-icon"></i>`
                        }
                    </div>
                    <div class="file-info">
                        <div class="file-name" title="${file.name}">${file.name}</div>
                        <div class="file-size">${file.size}</div>
                    </div>
                    <div class="file-actions">
                        <button class="action-btn download" title="Download">
                            <i class="fas fa-download"></i>
                        </button>
                        <button class="action-btn delete" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `);

            // Checkbox selection
            item.find('.file-checkbox').change(function() {
                const uuid = item.data('uuid');
                if (this.checked) {
                    selectedFiles.add(uuid);
                    item.addClass('selected');
                } else {
                    selectedFiles.delete(uuid);
                    item.removeClass('selected');
                }
                updateSelectionUI();
            });

            // Click to preview images
            if (isImage) {
                item.find('.file-preview').off('click').on('click', function() {
                    showFullscreen(file.original_url);
                });
            }

            // Download button
            item.find('.download').off('click').on('click', function() {
                downloadSingleFile(file);
            });

            // Delete button
            item.find('.delete').off('click').on('click', function() {
                deleteFile(file.uuid);
            });

            return item;
        }

        function updateSelectionUI() {
            const count = selectedFiles.size;
            const total = $('.file-item').length;

            $('#selectionCount').text(`${count} files selected`);
            $('#downloadSelectedBtn').prop('disabled', count === 0);
            $('#emailSelectedBtn').prop('disabled', count === 0);

            const selectAllBtn = $('#selectAllBtn');
            if (count === total && total > 0) {
                selectAllBtn.html('<i class="fas fa-minus-square"></i> Deselect All');
            } else {
                selectAllBtn.html('<i class="fas fa-check-square"></i> Select All');
            }
        }

        function downloadSingleFile(file) {
            const link = document.createElement('a');
            link.href = file.original_url;
            link.download = file.name;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function downloadSelectedFiles() {
            if (selectedFiles.size === 0) return;

            if (selectedFiles.size === 1) {
                // Single file download
                const uuid = Array.from(selectedFiles)[0];
                const fileItem = $(`.file-item[data-uuid="${uuid}"]`);
                const fileName = fileItem.find('.file-name').text();
                const fileUrl = fileItem.find('.file-preview img').attr('src') ||
                    fileItem.find('.action-btn.download').data('url');

                downloadSingleFile({
                    original_url: fileUrl,
                    name: fileName
                });
            } else {
                // Multiple files - create zip
                downloadAsZip();
            }
        }

        function downloadAsZip() {
            const selectedUuids = Array.from(selectedFiles);

            showToast('info', 'Preparing Download', 'Creating zip file for multiple files...');

            $.ajax({
                url: '/api/v1/download-zip',
                method: 'POST',
                data: JSON.stringify({
                    uuids: selectedUuids
                }),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                xhrFields: {
                    responseType: 'blob'
                },
                success: function(blob) {
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `shared-files-${Date.now()}.zip`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);

                    showToast('success', 'Download Complete!', 'Files downloaded as zip archive');
                },
                error: function() {
                    showToast('error', 'Download Failed!', 'Failed to create zip file');
                }
            });
        }

        function showEmailModal() {
            if (selectedFiles.size === 0) return;
            $('#emailModal').addClass('show');
        }

        function hideEmailModal() {
            $('#emailModal').removeClass('show');
            $('#emailForm')[0].reset();
        }

        function sendEmailWithFiles() {
            const toEmail = $('#toEmail').val();
            const subject = $('#emailSubject').val();
            const message = $('#emailMessage').val();
            const selectedUuids = Array.from(selectedFiles);

            const btn = $('#sendEmailBtn');
            const text = $('#sendEmailText');
            const loader = $('#emailLoader');

            btn.prop('disabled', true);
            text.hide();
            loader.show();

            $.ajax({
                url: '/api/v1/email-files',
                method: 'POST',
                data: JSON.stringify({
                    to_email: toEmail,
                    subject: subject,
                    message: message,
                    uuids: selectedUuids
                }),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    btn.prop('disabled', false);
                    text.show();
                    loader.hide();

                    hideEmailModal();
                    showToast('success', 'Email Sent!', 'Files sent successfully via email');
                },
                error: function(xhr) {
                    btn.prop('disabled', false);
                    text.show();
                    loader.hide();

                    const message = xhr.responseJSON?.message || 'Failed to send email.';
                    showToast('error', 'Email Failed!', message);
                }
            });
        }

        function deleteFile(uuid) {
            if (!confirm('Are you sure you want to delete this file?')) return;

            selectedFiles.delete(uuid);

            $.ajax({
                url: '{{ route('share.delete.media') }}',
                method: 'DELETE',
                data: {
                    uuid: uuid
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    showToast('success', 'Deleted!', 'File deleted successfully');
                    fetchMedia();
                    loadIpInfo();
                },
                error: function() {
                    showToast('error', 'Delete Failed!', 'Failed to delete file');
                }
            });
        }

        function showFullscreen(imageSrc) {
            $('#fullscreenImage').attr('src', imageSrc);
            $('#fullscreenOverlay').addClass('show');
        }

        $('#fullscreenClose, #fullscreenOverlay').click(function(e) {
            if (e.target === this) {
                $('#fullscreenOverlay').removeClass('show');
            }
        });

        function showMessage(elementId, message) {
            const element = $(`#${elementId}`);
            element.find('span').text(message);
            element.show();

            setTimeout(() => {
                element.hide();
            }, 5000);
        }
    </script>
@endsection
