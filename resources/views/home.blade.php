@extends('layouts.app')

@section('title', 'AirForShare - Instant File Sharing Across Devices | Local Network File Transfer')
@section('description', 'Share files and text instantly across devices on the same Wi-Fi network. No accounts, no external servers - just secure peer-to-peer file sharing up to 10MB per file.')
@section('keywords', 'file sharing, instant sharing, local network, Wi-Fi sharing, cross-device, secure sharing, peer-to-peer, no account required')

@section('schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebApplication",
  "name": "AirForShare",
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
    "name": "AirForShare"
  }
}
</script>
@endsection

@section('content')
    <style>
        /* Text Tab Styles */
        .text-container {
            padding: 2rem;
        }

        .modern-textarea {
            width: 100%;
            min-height: 300px;
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            font-size: 1.1rem;
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            resize: vertical;
            transition: var(--transition);
            outline: none;
        }

        .modern-textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .textarea-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .char-counter {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .char-counter.warning {
            color: var(--warning-color);
        }

        .char-counter.danger {
            color: var(--error-color);
        }

        .button-group {
            display: flex;
            gap: 1rem;
        }

        .links-container {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }

        .detected-link {
            display: block;
            color: var(--primary-color);
            text-decoration: none;
            margin-bottom: 0.5rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .detected-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* File Tab Styles */
        .file-container {
            padding: 2rem;
        }

        .upload-zone {
            border: 3px dashed rgba(102, 126, 234, 0.3);
            border-radius: var(--border-radius);
            padding: 3rem 2rem;
            text-align: center;
            background: rgba(102, 126, 234, 0.02);
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .upload-zone:hover {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
            transform: scale(1.01);
        }

        .upload-zone.dragover {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.1);
            transform: scale(1.02);
            box-shadow: var(--shadow-lg);
            border-style: solid;
        }

        .upload-icon {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: block;
            transition: var(--transition);
        }

        .upload-zone.dragover .upload-icon {
            transform: scale(1.1);
            color: var(--primary-dark);
        }

        .upload-text {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .upload-subtext {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .upload-zone.dragover .upload-text {
            color: var(--primary-color);
        }
        .progress-container {
            margin-top: 1rem;
            display: none;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--bg-gradient);
            transition: width 0.3s ease;
            border-radius: 4px;
        }

        .progress-text {
            text-align: center;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        /* File Selection and Download Controls */
        .file-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding: 1rem;
            background: rgba(102, 126, 234, 0.05);
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary-color);
        }

        .selection-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .select-all-btn {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
        }

        .select-all-btn:hover {
            background: var(--primary-color);
            color: white;
        }

        .download-controls {
            display: flex;
            gap: 0.5rem;
        }

        .download-btn {
            background: var(--success-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .download-btn:hover {
            background: var(--success-color);
            transform: translateY(-1px);
        }

        .download-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .email-btn {
            background: var(--primary-color);
        }

        .email-btn:hover {
            background: var(--primary-dark);
        }
        /* File Preview Grid */
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(102, 126, 234, 0.02);
            border-radius: var(--border-radius);
            min-height: 200px;
        }

        .file-grid.empty {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            font-style: italic;
        }

        .file-item {
            background: var(--bg-primary);
            border-radius: var(--border-radius);
            padding: 1rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            position: relative;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .file-item:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .file-item.selected {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
        }

        .file-checkbox {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            width: 20px;
            height: 20px;
            cursor: pointer;
            z-index: 5;
        }
        .file-preview {
            width: 100%;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 1rem;
            background: var(--bg-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .file-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .file-icon {
            font-size: 3rem;
            color: var(--primary-color);
        }

        .file-info {
            text-align: center;
        }

        .file-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            font-size: 0.875rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .file-size {
            color: var(--text-secondary);
            font-size: 0.75rem;
        }

        .file-actions {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            display: flex;
            gap: 0.25rem;
            opacity: 0;
            transition: var(--transition);
        }

        .file-item:hover .file-actions {
            opacity: 1;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            transition: var(--transition);
        }

        .action-btn.delete {
            background: var(--error-color);
            color: white;
        }

        .action-btn.download {
            background: var(--success-color);
            color: white;
        }

        .action-btn:hover {
            transform: scale(1.1);
        }

        /* Email Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: var(--bg-primary);
            border-radius: var(--border-radius);
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        /* Messages */
        .message {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin: 1rem 0;
            display: none;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .message.success {
            background: rgba(72, 187, 120, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(72, 187, 120, 0.2);
        }

        .message.error {
            background: rgba(245, 101, 101, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(245, 101, 101, 0.2);
        }

        .message.warning {
            background: rgba(237, 137, 54, 0.1);
            color: var(--warning-color);
            border: 1px solid rgba(237, 137, 54, 0.2);
        }

        /* Full Screen Preview */
        .fullscreen-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .fullscreen-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .fullscreen-content {
            max-width: 90%;
            max-height: 90%;
            position: relative;
        }

        .fullscreen-image {
            max-width: 100%;
            max-height: 100%;
            border-radius: var(--border-radius);
        }

        .fullscreen-close {
            position: absolute;
            top: -50px;
            right: 0;
            background: var(--bg-primary);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: var(--text-primary);
        }

        /* Loading States */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--border-radius);
            z-index: 10;
        }

        .loading-spinner-large {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(102, 126, 234, 0.2);
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .text-container,
            .file-container {
                padding: 1rem;
            }

            .file-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1rem;
            }

            .button-group {
                flex-direction: column;
            }

            .textarea-footer {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .file-controls {
                flex-direction: column;
                gap: 1rem;
            }

            .download-controls {
                justify-content: center;
            }
        }
    </style>

    <!-- Hero Section -->
    <div class="hero-section">
        <h1 class="hero-title">
            <i class="fas fa-cloud-upload-alt"></i>
            AirForShare
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
                <textarea 
                    class="modern-textarea" 
                    id="textInput" 
                    placeholder="Type or paste your text here... Links will be automatically detected and made clickable."
                    maxlength="50000"
                ></textarea>
                
                <div class="textarea-footer">
                    <div class="char-counter" id="charCounter">0 / 50,000 characters</div>
                    <div class="button-group">
                        <button class="modern-btn danger" id="clearBtn" style="display: none;">
                            <i class="fas fa-trash"></i>
                            Clear
                        </button>
                        <button class="modern-btn" id="saveBtn">
                            <i class="fas fa-save"></i>
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
                    <input type="file" id="fileInput" multiple accept="image/*,.pdf,.docx,.txt,.zip" style="display: none;">
                    
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
                    </div>
                </div>
                <div class="file-grid" id="fileGrid">
                    <div class="empty-state">
                        <i class="fas fa-folder-open" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
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
                    <input type="email" class="form-input" id="toEmail" placeholder="recipient@example.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Subject:</label>
                    <input type="text" class="form-input" id="emailSubject" value="Shared Files from AirForShare">
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
                </div>
            </form>
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
                data: JSON.stringify({ text: text }),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    setButtonLoading(false);
                    showMessage('textSuccessMessage', 'Text saved successfully!');
                    
                    if (text.trim().length > 0) {
                        updateSaveButton('copy');
                    }
                },
                error: function(xhr) {
                    setButtonLoading(false);
                    const message = xhr.responseJSON?.message || 'Error occurred while saving text.';
                    showMessage('textErrorMessage', message);
                }
            });
        }

        function handleCopyText() {
            const text = $('#textInput').val();
            
            navigator.clipboard.writeText(text).then(() => {
                showMessage('textSuccessMessage', 'Text copied to clipboard!');
                
                // Visual feedback
                const btn = $('#saveBtn');
                btn.addClass('success');
                setTimeout(() => btn.removeClass('success'), 1000);
            }).catch(() => {
                showMessage('textErrorMessage', 'Failed to copy text to clipboard.');
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
                if (e.target === this || $(e.target).hasClass('upload-icon') || $(e.target).hasClass('upload-text') || $(e.target).hasClass('upload-subtext')) {
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
                    showMessage('fileSuccessMessage', 'File uploaded successfully!');
                    fetchMedia();
                    loadIpInfo();
                },
                error: function(xhr) {
                    showProgress(false);
                    const message = xhr.responseJSON?.message || 'Upload failed.';
                    showMessage('fileErrorMessage', message);
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
                
                downloadSingleFile({ original_url: fileUrl, name: fileName });
            } else {
                // Multiple files - create zip
                downloadAsZip();
            }
        }

        function downloadAsZip() {
            const selectedUuids = Array.from(selectedFiles);
            
            showMessage('fileSuccessMessage', 'Preparing zip file for download...');
            
            $.ajax({
                url: '/api/v1/download-zip',
                method: 'POST',
                data: JSON.stringify({ uuids: selectedUuids }),
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
                    
                    showMessage('fileSuccessMessage', 'Files downloaded successfully!');
                },
                error: function() {
                    showMessage('fileErrorMessage', 'Failed to create zip file.');
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
                    showMessage('fileSuccessMessage', 'Email sent successfully!');
                },
                error: function(xhr) {
                    btn.prop('disabled', false);
                    text.show();
                    loader.hide();
                    
                    const message = xhr.responseJSON?.message || 'Failed to send email.';
                    showMessage('fileErrorMessage', message);
                }
            });
        }
        function deleteFile(uuid) {
            if (!confirm('Are you sure you want to delete this file?')) return;
            
            selectedFiles.delete(uuid);
            
            $.ajax({
                url: '{{ route('share.delete.media') }}',
                method: 'DELETE',
                data: { uuid: uuid },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    showMessage('fileSuccessMessage', 'File deleted successfully!');
                    fetchMedia();
                    loadIpInfo();
                },
                error: function() {
                    showMessage('fileErrorMessage', 'Failed to delete file.');
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