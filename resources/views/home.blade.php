@extends('layouts.app')

@section('title', 'AirToShare – Instant, Secure File Sharing Across Devices')
@section('description',
    'Experience instant, secure peer-to-peer file and text sharing across all your devices on the
    same local Wi-Fi network. No cloud uploads, no logins required, and completely free. Fast, private, and simple.')

@section('keywords',
    'file sharing, instant sharing, share text online, share text online free, text share online, online text share, share text file online, online share text, how to share large text files online, how to share text online')

@section('schema')
    <script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "WebApplication",
  "name": "AirToShare",
  "description": "Instant file sharing across devices on the same network",
  "url": "{{ url('/') }}",
  "applicationCategory": "UtilitiesApplication",
  "operatingSystem": "All",
  "image": "{{ url('/logo.svg') }}",
  "offers": {
    "@@type": "Offer",
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
    "@@type": "Organization",
    "name": "AirToShare"
  }
}
</script>
@endsection

@section('content')

    <div id="airtoshare-app"
        @if (isset($share) && $share)
            data-airtoshare-share-id="{{ $share->id }}"
            data-airtoshare-share-uuid="{{ $share->uuid }}"
        @endif
        @if (isset($room) && $room)
            data-airtoshare-room-id="{{ $room->id }}"
            data-airtoshare-room-code="{{ $room->code }}"
        @endif
        @if (isset($share) && $share && $share->is_e2ee)
            data-airtoshare-e2ee="1"
        @endif
        @if (isset($viewingShare) && $viewingShare)
            data-airtoshare-viewing="1"
        @endif
        @if (isset($share) && $share && $share->hasPassword())
            data-airtoshare-has-password="1"
        @endif
    >

    @if (session('status'))
        <div class="message success" style="margin-bottom: 1rem;">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Hero -->
    <header class="home-hero">
        <span class="home-hero-badge"><i class="fas fa-bolt" aria-hidden="true"></i> Free · No install · Any browser</span>
        <h1 class="home-hero-title">
            <img src="/icon.svg" class="home-hero-logo" alt="" width="40" height="40" />
            Share text &amp; files instantly
        </h1>
        <p class="home-hero-lead">Save or upload on this device, then copy your link — or scan QR — to open it anywhere.</p>
    </header>

    <div class="home-workspace">

    <!-- Status chips -->
    <div class="home-status-bar" aria-label="Session status">
        <div class="home-chip home-chip--device device-nickname-container">
            <i class="fas fa-laptop" aria-hidden="true"></i>
            <span class="device-nickname" id="deviceNickname" title="Click to edit">My Device</span>
            <input type="text" class="nickname-input hidden" id="nicknameInput" placeholder="Device name">
            <button class="nickname-edit-btn" id="editNicknameBtn" type="button" title="Edit name" aria-label="Edit device name">
                <i class="fas fa-pencil-alt"></i>
            </button>
        </div>
        <div class="home-chip">
            <i class="fas fa-network-wired" aria-hidden="true"></i>
            <span class="home-chip-label">IP</span>
            <span id="userIp">…</span>
        </div>
        <div class="home-chip">
            <i class="fas fa-folder" aria-hidden="true"></i>
            <span class="home-chip-label">Files</span>
            <span><span id="fileCount">0</span>/<span id="maxFiles">20</span></span>
        </div>
        <div class="home-chip">
            <i class="fas fa-weight-hanging" aria-hidden="true"></i>
            <span class="home-chip-label">Max</span>
            <span id="maxFileSize">25 MB</span>
        </div>
        <div class="home-chip home-chip--expiry hidden" id="expiryCountdown">
            <i class="fas fa-hourglass-half" aria-hidden="true"></i>
            <span class="home-chip-label">Expires</span>
            <span id="countdownTimer" class="countdown-badge">--:--:--</span>
        </div>
        <div class="home-chip home-chip--sync hidden" id="lastActivity">
            <i class="fas fa-clock" aria-hidden="true"></i>
            <span class="home-chip-label">Sync</span>
            <span id="lastActivityTime">Just now</span>
        </div>
        @if (isset($room) && $room)
            <div class="home-chip home-chip--room">
                <i class="fas fa-users" aria-hidden="true"></i>
                <span class="home-chip-label">Room</span>
                <span>{{ $room->code }}</span>
            </div>
        @endif
    </div>

    <!-- Actions toolbar -->
    <div class="home-toolbar" role="toolbar" aria-label="Share actions">
        <div class="home-toolbar-group">
            <button class="home-tool-btn home-tool-btn--primary" id="showQRBtn" type="button" title="Share link and QR code">
                <i class="fas fa-share-nodes" aria-hidden="true"></i>
                <span>Share link</span>
            </button>
        </div>
        <span class="home-toolbar-divider" aria-hidden="true"></span>
        <div class="home-toolbar-group">
            <button class="home-tool-btn" id="joinRoomBtn" type="button">
                <i class="fas fa-door-open" aria-hidden="true"></i>
                <span>Join room</span>
            </button>
            <button class="home-tool-btn" id="createRoomBtn" type="button">
                <i class="fas fa-plus-circle" aria-hidden="true"></i>
                <span>New room</span>
            </button>
        </div>
        <span class="home-toolbar-divider" aria-hidden="true"></span>
        <div class="home-toolbar-group">
            <button class="home-tool-btn" id="sharePasswordBtn" type="button" title="Password-protect this share">
                <i class="fas fa-key" aria-hidden="true"></i>
                <span id="sharePasswordBtnLabel">Password</span>
            </button>
            <label class="home-tool-btn home-tool-btn--toggle" id="e2eeToggleWrap"
                title="End-to-end encryption (key in URL # only)">
                <input type="checkbox" id="e2eeToggle" @if(isset($share) && $share && $share->is_e2ee) checked @endif>
                <i class="fas fa-lock" aria-hidden="true"></i>
                <span>E2EE</span>
            </label>
        </div>
        <span class="home-toolbar-spacer"></span>
        <button class="home-tool-btn home-tool-btn--icon" id="soundToggle" type="button" title="Notification sounds">
            <i class="fas fa-volume-up" id="soundIcon" aria-hidden="true"></i>
        </button>
    </div>

    <p class="e2ee-hint hidden" id="e2eeHint" role="status"></p>

    {{-- Share link panel: appears once the user has saved text or uploaded files --}}
    @php
        $initialShareUrl = (isset($share) && $share && empty($viewingShare))
            ? url('/s/' . $share->uuid)
            : '';
        $showShareLinkPanel = $initialShareUrl !== '';
    @endphp
    <div class="share-link-panel{{ $showShareLinkPanel ? '' : ' hidden' }}" id="shareLinkPanel" role="region"
        aria-label="Share link">
        <div class="share-link-panel-top">
            <div class="share-link-panel-title">
                <i class="fas fa-share-nodes" aria-hidden="true"></i>
                <strong>Your share link</strong>
                <span class="share-link-badge hidden" id="shareLinkPasswordBadge">
                    <i class="fas fa-key" aria-hidden="true"></i> Password on
                </span>
            </div>
            <p class="share-link-panel-hint" id="shareLinkPanelHint">
                Copy this link and send it to anyone. They can open your text and files in any browser.
            </p>
        </div>
        <div class="share-link-panel-row">
            <input type="text" class="share-link-panel-input" id="sharePageLinkInput" readonly
                value="{{ $initialShareUrl }}"
                aria-label="Share link URL">
            <button type="button" class="modern-btn share-link-copy-btn" id="copySharePageLinkBtn"
                title="Copy share link">
                <i class="fas fa-copy" aria-hidden="true"></i>
                <span>Copy link</span>
            </button>
            <button type="button" class="modern-btn secondary share-link-qr-btn" id="openShareLinkQrBtn"
                title="Show QR code for this link">
                <i class="fas fa-qrcode" aria-hidden="true"></i>
                <span>QR</span>
            </button>
        </div>
    </div>

    <!-- Modern Tabs -->
    <div class="home-editor">
    <div class="modern-tabs home-tabs">
        <button class="modern-tab active" data-tab="text" type="button">
            <i class="fas fa-align-left" aria-hidden="true"></i>
            Text
        </button>
        <button class="modern-tab" data-tab="file" type="button">
            <i class="fas fa-file-upload" aria-hidden="true"></i>
            Files
        </button>
    </div>

    <!-- Tab Contents -->
    <div class="modern-card home-editor-card">
        <!-- Text Tab -->
        <div class="tab-content active" id="text-tab">
            <div class="text-container">
                <textarea class="modern-textarea" id="textInput" data-airtoshare-clipboard-target
                    placeholder="Type or paste your text here…"
                    maxlength="500000"></textarea>

                <div class="textarea-footer">
                    <div class="char-counter" id="charCounter">0 / 500,000 characters</div>
                    <div class="button-group">
                        <button class="modern-btn clipboard-sync-btn" id="clipboardSyncBtn" title="Paste from Clipboard">
                            <i class="fas fa-clipboard"></i>
                            <span>Paste Clipboard</span>
                        </button>
                        <button class="modern-btn danger hidden" id="clearBtn">
                            <i class="fas fa-trash"></i>
                            Clear
                        </button>
                        {{--
                            Server-rendered Copy button (task 9.2, Requirement 5.1).

                            Acceptance criterion 5.1 mandates that a "Copy"
                            button be displayed next to the text panel iff
                            the Share contains at least one character of
                            text. The visibility decision is therefore made
                            on the server using the resolved $share
                            aggregate, not in JavaScript: the button is
                            simply absent from the DOM when the share has
                            no text, so the markup itself is the contract.

                            The element is data-attribute driven via
                            `data-copy="#textInput"` so the clipboard
                            module from task 9.1 handles activation,
                            tri-strategy fallback, in-flight disabling, and
                            the success indicator (Reqs 5.2-5.6) without
                            any per-page JavaScript here.
                        --}}
                        @if (isset($share) && $share && strlen((string) ($share->text_content ?? '')) >= 1)
                            <button
                                class="modern-btn"
                                type="button"
                                id="copyTextBtn"
                                data-copy="#textInput"
                                title="Copy shared text to clipboard"
                                aria-label="Copy shared text to clipboard"
                            >
                                <i class="fas fa-copy"></i>
                                <span>Copy</span>
                            </button>
                        @endif
                        <button class="modern-btn" id="saveBtn">
                            {{-- <i class="fas fa-save"></i> --}}
                            <span id="saveBtnText">Save</span>
                            <div class="loading-spinner hidden" id="saveLoader"></div>
                        </button>
                    </div>
                </div>

                <div class="links-container hidden" id="linksContainer">
                    <strong><i class="fas fa-link"></i> Detected links &amp; emails:</strong>
                    <div id="linksList" class="detected-links-list"></div>
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
                <div class="upload-zone" id="uploadZone"
                    data-upload-manager
                    data-upload-endpoint="{{ route('media.store') }}"
                    data-upload-field="file"
                    data-upload-max-files="50"
                    data-upload-active-files="0"
                    data-upload-max-size="26214400"
                    data-chunked-start="{{ url('/api/v1/chunked-upload/start') }}"
                    data-chunked-chunk="{{ url('/api/v1/chunked-upload/chunk') }}"
                    data-chunked-status="{{ url('/api/v1/chunked-upload/status') }}"
                    data-chunked-complete="{{ url('/api/v1/chunked-upload/complete') }}">
                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                    <div class="upload-text">Drag & Drop Files Here</div>
                    <div class="upload-subtext">
                        or click to browse • Large files use resumable chunked upload
                        <br>
                        <small>Supported: Images, PDF, DOC, TXT, ZIP, Videos</small>
                    </div>
                    <input type="file" id="fileInput" data-upload-input multiple
                        accept="image/*,
  application/pdf,
  application/msword,
  application/vnd.openxmlformats-officedocument.wordprocessingml.document,
  text/plain,
  application/zip,
  video/*,
  audio/*"
                        class="hidden">

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
                <div class="file-controls hidden" id="fileControls">
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
                        {{-- <button class="download-btn email-btn" id="emailSelectedBtn" disabled>
                            <i class="fas fa-envelope"></i>
                            Email
                        </button> --}}
                        <button class="modern-btn danger hidden" id="removeAllBtn">
                            <i class="fas fa-trash-alt"></i>
                            Remove All Files
                        </button>
                    </div>
                </div>
                <div class="file-grid" id="fileGrid">
                    <div class="empty-state">
                        <i class="fas fa-folder-open empty-state-icon"></i>
                        <p>No files uploaded yet. Start by dragging files above!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>{{-- .home-editor --}}
    </div>{{-- .home-workspace --}}

    <!-- Fullscreen Preview -->
    <div class="preview-modal-overlay" id="previewModal">
        <div class="preview-modal-content">
            <div class="preview-modal-header">
                <div class="preview-file-info">
                    <h3 id="previewFileName">File Name</h3>
                    <span id="previewFileSize">File Size</span>
                </div>
                <div class="preview-modal-actions">
                    <button class="preview-action-btn share-btn" id="previewShareBtn" title="Share Link">
                        <i class="fas fa-share-alt"></i>
                    </button>
                    <button class="preview-action-btn" id="previewDownloadBtn" title="Download">
                        <i class="fas fa-download"></i>
                    </button>
                    <button class="preview-action-btn" id="previewCloseBtn" title="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="preview-modal-body">
                <button class="preview-nav-btn prev" id="previewPrevBtn">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <div class="preview-container" id="previewContainer">
                    <!-- Image Preview -->
                    <img class="preview-image hidden" id="previewImage" src="" alt="Preview">

                    <!-- Video Preview -->
                    <video class="preview-video hidden" id="previewVideo" controls>
                        Your browser does not support the video tag.
                    </video>

                    <!-- Audio Preview -->
                    <audio class="preview-audio hidden" id="previewAudio" controls>
                        Your browser does not support the audio tag.
                    </audio>

                    <!-- PDF Preview -->
                    <iframe class="preview-pdf hidden" id="previewPdf"></iframe>

                    <!-- Text/Code Preview -->
                    <div class="preview-text hidden" id="previewText">
                        <div class="preview-text-header">
                            <span class="preview-language" id="previewLanguage">Text</span>
                            <button class="preview-copy-btn" id="previewCopyCodeBtn" title="Copy to clipboard">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <pre class="preview-code"><code id="previewCodeContent"></code></pre>
                    </div>

                    <!-- Document/Other Files Preview -->
                    <div class="preview-document hidden" id="previewDocument">
                        <i class="fas fa-file-alt preview-doc-icon"></i>
                        <p>Preview not available for this file type</p>
                        <button class="modern-btn" id="previewDocDownloadBtn">
                            <i class="fas fa-download"></i>
                            Download to View
                        </button>
                    </div>
                </div>

                <button class="preview-nav-btn next" id="previewNextBtn">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <div class="preview-modal-footer">
                <span id="previewCounter">1 / 10</span>
            </div>
        </div>
    </div>

    <!-- Email Modal -->
    <div class="modal-overlay" id="emailModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fas fa-envelope"></i>
                    Email Files
                </div>
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
                        <div class="loading-spinner hidden" id="emailLoader"></div>
                    </button>
                    <button class="download-btn danger-btn" id="removeAllBtn">
                        <i class="fas fa-trash-alt"></i>
                        Remove All
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Join Room Modal -->
    <div class="modal-overlay" id="roomModal" role="dialog" aria-modal="true" aria-labelledby="roomModalTitle">
        <div class="modal-content room-modal">
            <div class="modal-header">
                <div class="modal-title room-modal-title" id="roomModalTitle">
                    <i class="fas fa-door-open" aria-hidden="true"></i>
                    Join Room
                </div>
                <button class="modal-close" id="roomModalClose" type="button" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="room-modal-body">
                <p class="room-modal-subtitle">
                    Enter the 6-character code shared with you to sync clipboard and files in a private room.
                </p>
                <label class="form-label" for="roomCodeInput">Room code</label>
                <input type="text" id="roomCodeInput" class="form-input room-code-input"
                    maxlength="6" placeholder="ABC234" autocomplete="off"
                    inputmode="text" autocapitalize="characters" spellcheck="false"
                    aria-describedby="roomCodeError">
                <p class="room-code-hint">Letters A–Z and numbers 2–9 (no O, I, 0, 1)</p>
                <label class="form-label" for="roomJoinPasswordInput">Room password <span class="form-label-optional">(optional)</span></label>
                <input type="password" id="roomJoinPasswordInput" class="form-input"
                    autocomplete="current-password" minlength="6" maxlength="128"
                    placeholder="Only if the room is protected">
                <p class="room-code-error hidden" id="roomCodeError" role="alert"></p>
                <div class="room-modal-actions">
                    <button type="button" class="modern-btn secondary" id="roomJoinCancel">Cancel</button>
                    <button type="button" class="modern-btn" id="roomJoinConfirm">
                        <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                        Join room
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Room Modal -->
    <div class="modal-overlay" id="createRoomModal" role="dialog" aria-modal="true" aria-labelledby="createRoomModalTitle">
        <div class="modal-content room-modal">
            <div class="modal-header">
                <div class="modal-title room-modal-title" id="createRoomModalTitle">
                    <i class="fas fa-plus-circle" aria-hidden="true"></i>
                    Create Room
                </div>
                <button class="modal-close" id="createRoomModalClose" type="button" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="room-modal-body">
                <p class="room-modal-subtitle">
                    Create a collaborative room and share the 6-character code with others.
                </p>
                <label class="form-label" for="createRoomExpiry">Expiry</label>
                <select id="createRoomExpiry" class="form-input">
                    <option value="1h">1 hour</option>
                    <option value="6h">6 hours</option>
                    <option value="24h" selected>24 hours</option>
                    <option value="7d">7 days</option>
                </select>
                <label class="form-label" for="createRoomPassword">Room password <span class="form-label-optional">(optional)</span></label>
                <input type="password" id="createRoomPassword" class="form-input"
                    autocomplete="new-password" minlength="6" maxlength="128"
                    placeholder="6–128 characters to protect the room">
                <p class="room-code-error hidden" id="createRoomError" role="alert"></p>
                <div class="room-modal-actions">
                    <button type="button" class="modern-btn secondary" id="createRoomCancel">Cancel</button>
                    <button type="button" class="modern-btn" id="createRoomConfirm">
                        <i class="fas fa-check" aria-hidden="true"></i>
                        Create room
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Share Password Modal -->
    <div class="modal-overlay" id="sharePasswordModal" role="dialog" aria-modal="true" aria-labelledby="sharePasswordModalTitle">
        <div class="modal-content room-modal">
            <div class="modal-header">
                <div class="modal-title room-modal-title" id="sharePasswordModalTitle">
                    <i class="fas fa-key" aria-hidden="true"></i>
                    Share password
                </div>
                <button class="modal-close" id="sharePasswordModalClose" type="button" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="room-modal-body">
                <p class="room-modal-subtitle" id="sharePasswordModalHint">
                    Add a password so only people with the secret can open your share link.
                </p>
                <label class="form-label" for="sharePasswordInput">Password</label>
                <input type="password" id="sharePasswordInput" class="form-input"
                    autocomplete="new-password" minlength="6" maxlength="128"
                    placeholder="6–128 characters">
                <p class="room-code-error hidden" id="sharePasswordError" role="alert"></p>
                <div class="room-modal-actions">
                    <button type="button" class="modern-btn secondary hidden" id="sharePasswordClear">Remove password</button>
                    <button type="button" class="modern-btn secondary" id="sharePasswordCancel">Cancel</button>
                    <button type="button" class="modern-btn" id="sharePasswordSave">
                        <i class="fas fa-save" aria-hidden="true"></i>
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Remove All Confirmation Modal -->
    <div class="modal-overlay" id="removeAllModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title modal-title-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Remove All Files
                </div>
                <button class="modal-close" id="removeAllModalClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="modal-text-primary">
                    <strong>Are you sure you want to remove all files?</strong>
                </p>
                <p class="modal-text-secondary">
                    This action will permanently delete all <span id="totalFilesCount">0</span> files from your session.
                    This cannot be undone.
                </p>
                <div class="modal-actions">
                    <button class="modern-btn secondary" id="cancelRemoveAll">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button class="modern-btn danger" id="confirmRemoveAll">
                        <i class="fas fa-trash-alt"></i>
                        <span id="removeAllText">Remove All Files</span>
                        <div class="loading-spinner hidden" id="removeAllLoader"></div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Share Link Modal -->
    <div class="modal-overlay" id="shareModal">
        <div class="modal-content share-modal">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fas fa-share-alt"></i>
                    <span>Share File</span>
                </div>
                <button class="modal-close-btn" id="shareModalClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="share-modal-body">
                <div class="share-file-name" id="shareFileName">file.jpg</div>

                <div class="share-option one-time-option">
                    <label class="toggle-switch">
                        <input type="checkbox" id="oneTimeToggle">
                        <span class="toggle-slider"></span>
                    </label>
                    <div class="share-option-info">
                        <strong>One-Time Download</strong>
                        <small>File will be deleted after first download</small>
                    </div>
                    <i class="fas fa-lock one-time-badge hidden" id="oneTimeBadge"></i>
                </div>

                <div class="share-link-container">
                    <input type="text" class="share-link-input" id="shareLinkInput" readonly>
                    <button class="modern-btn copy-share-btn" id="copyShareLink">
                        <i class="fas fa-copy"></i>
                        <span>Copy</span>
                    </button>
                </div>

                <div class="share-link-note hidden" id="oneTimeNote">
                    <i class="fas fa-info-circle"></i>
                    This link will expire after a single download
                </div>
            </div>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div class="modal-overlay" id="qrModal">
        <div class="modal-content qr-modal">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fas fa-qrcode"></i>
                    Share link &amp; QR
                </div>
                <button class="modal-close" id="qrModalClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="qr-container">
                @if (isset($share) && $share)
                    {{--
                        Server-rendered QR slot (task 7.3, design.md > Component 1 frontend).

                        Acceptance criteria:
                          1.4 - clicking the QR offers the PNG as a download
                                via a native anchor `download` attribute, so
                                the click-to-download path works without JS.
                          1.5 - if /qr/{slug} fails (HTTP 5xx or network
                                error), the inline `error` listener replaces
                                the slot with the share URL text and an
                                error banner. No PNG download is offered in
                                that branch.

                        The slot also renders a generic <div id="qrcode"> for
                        the legacy client-side QRCode.js library so the
                        existing `generateQRCode()` flow keeps working as a
                        belt-and-braces second copy. The two are visually
                        equivalent; the server-rendered image is the
                        canonical Requirement-1 surface.
                    --}}
                    <div class="qr-slot" data-qr-slot data-share-uuid="{{ $share->uuid }}">
                        @php
                            $qrShowUrl = route('qr.show', ['slug' => $share->uuid]);
                            $qrDownloadUrl = $qrShowUrl . '?download=1';
                            $shareUrl = $share->public_slug
                                ? url('/p/' . $share->public_slug)
                                : url('/s/' . $share->uuid);
                        @endphp
                        <a
                            href="{{ $qrDownloadUrl }}"
                            download="share-{{ $share->uuid }}.png"
                            class="qr-slot-link"
                            data-qr-download
                            title="Download QR code as PNG"
                            aria-label="Download QR code as PNG"
                        >
                            <img
                                src="{{ $qrShowUrl }}"
                                alt="QR code for this share. Click to download as PNG."
                                width="200"
                                height="200"
                                loading="lazy"
                                decoding="async"
                                data-qr-image
                                data-qr-share-url="{{ $shareUrl }}"
                            >
                        </a>
                        <div
                            class="qr-slot-fallback hidden"
                            role="alert"
                            aria-live="polite"
                            data-qr-fallback
                        >
                            <p class="qr-slot-fallback-banner">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>QR code unavailable.</strong>
                                <span>We could not generate a QR image for this share right now. Use the link below to open it.</span>
                            </p>
                            <p class="qr-slot-fallback-url" data-qr-fallback-url>
                                {{ $shareUrl }}
                            </p>
                        </div>
                    </div>
                @endif
                <div id="qrcode"></div>
                <p class="qr-share-url-line hidden" id="qrShareUrlLine">
                    <code id="qrShareUrlDisplay"></code>
                </p>
                <p class="qr-instructions" id="qrInstructions">
                    <strong>Scan this QR code</strong> with your phone camera to open your shared content.
                </p>
                <button class="modern-btn" id="copyUrlBtn">
                    <i class="fas fa-link"></i>
                    <span id="copyUrlText">Copy share link</span>
                </button>
            </div>
        </div>
    </div>

    <!-- SEO Content (collapsed by default — expand for details) -->
    <details class="home-learn-more">
        <summary class="home-learn-more-summary">
            <i class="fas fa-book-open" aria-hidden="true"></i>
            Learn more about AirToShare
        </summary>
    <div class="seo-content">
        
        <!-- What is AirToShare Section -->
        <section class="seo-section" id="what-is-airtoshare">
            <div class="seo-section-inner">
                <h2 class="seo-heading">
                    <i class="fas fa-info-circle"></i>
                    What is AirToShare?
                </h2>
                <div class="seo-text">
                    <p>
                        <strong>AirToShare</strong> is a free, instant file and text sharing platform designed to make transferring content between your devices effortless. If you want to <strong>share text online free</strong> or need a quick <strong>online text share</strong> tool, we've got you covered. Whether you're moving photos from your phone to your laptop, sharing documents with a colleague, or looking to <strong>share text file online</strong> securely, AirToShare provides the fastest and simplest solution. Learn <strong>how to share large text files online</strong>, understand how to <strong>text share online</strong> seamlessly, or simply <strong>share text online</strong> between devices without requiring any account registration, app installation, or cloud uploads.
                    </p>
                    <p>
                        Built with privacy at its core, AirToShare operates on your local network, meaning your files never leave your Wi-Fi environment. This approach ensures lightning-fast transfers while keeping your sensitive data secure. Simply open AirToShare on any device with a web browser, and you're ready to share instantly. No complicated setup, no waiting for uploads to complete – just seamless, peer-to-peer sharing that works the way it should.
                    </p>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section class="seo-section" id="how-it-works-section">
            <div class="seo-section-inner">
                <h2 class="seo-heading">
                    <i class="fas fa-cogs"></i>
                    How AirToShare Works
                </h2>
                <div class="seo-steps">
                    <div class="seo-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3>Open on Any Device</h3>
                            <p>Visit AirToShare on your phone, tablet, laptop, or desktop. No downloads or installations required – it works directly in your web browser on any platform including Windows, macOS, Linux, iOS, and Android.</p>
                        </div>
                    </div>
                    <div class="seo-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3>Connect via Same Network</h3>
                            <p>Devices on the same Wi-Fi network are automatically connected using your IP address. Use the QR code feature to quickly open AirToShare on your mobile device without typing any URL.</p>
                        </div>
                    </div>
                    <div class="seo-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3>Share Instantly</h3>
                            <p>Drag and drop files or paste text, and your content is immediately available on all connected devices. Download files individually or as a ZIP archive. It's that simple – no accounts, no cloud uploads, no waiting.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Use Cases Section -->
        <section class="seo-section" id="use-cases">
            <div class="seo-section-inner">
                <h2 class="seo-heading">
                    <i class="fas fa-users"></i>
                    Who Uses AirToShare?
                </h2>
                <div class="use-cases-grid">
                    <div class="use-case-card">
                        <div class="use-case-icon">
                            <i class="fas fa-code"></i>
                        </div>
                        <h3>Developers & Designers</h3>
                        <p>Quickly share code snippets, design files, screenshots, and assets between your development machine and testing devices. Perfect for responsive testing and rapid prototyping workflows.</p>
                    </div>
                    <div class="use-case-card">
                        <div class="use-case-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <h3>Office Teams</h3>
                        <p>Share documents, presentations, and meeting notes with colleagues instantly during meetings. No need to email files or use complicated file sharing systems – just share and go.</p>
                    </div>
                    <div class="use-case-card">
                        <div class="use-case-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <h3>Home Users</h3>
                        <p>Transfer photos from your phone to your computer, share recipes with family members, or move files between your personal devices without any cables or Bluetooth pairing hassles.</p>
                    </div>
                    <div class="use-case-card">
                        <div class="use-case-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3>Students & Educators</h3>
                        <p>Share study materials, assignments, research papers, and lecture notes between devices during study sessions or classroom activities. Perfect for collaborative learning environments.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Security & Privacy Section -->
        <section class="seo-section" id="security-privacy">
            <div class="seo-section-inner">
                <h2 class="seo-heading">
                    <i class="fas fa-shield-alt"></i>
                    Security & Privacy
                </h2>
                <div class="security-features">
                    <div class="security-item">
                        <i class="fas fa-network-wired"></i>
                        <div>
                            <h3>Local Network Only</h3>
                            <p>Your files stay within your local Wi-Fi network and are never uploaded to external cloud servers. This provides both faster transfer speeds and enhanced privacy protection.</p>
                        </div>
                    </div>
                    <div class="security-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h3>Automatic Expiration</h3>
                            <p>All shared files are automatically deleted after a set period, ensuring your data doesn't linger on servers. You're always in control of your content lifecycle.</p>
                        </div>
                    </div>
                    <div class="security-item">
                        <i class="fas fa-lock"></i>
                        <div>
                            <h3>One-Time Downloads</h3>
                            <p>Enable one-time download links for sensitive files. Once downloaded, the link becomes invalid, providing an extra layer of security for confidential documents.</p>
                        </div>
                    </div>
                    <div class="security-item">
                        <i class="fas fa-user-shield"></i>
                        <div>
                            <h3>No Account Required</h3>
                            <p>We don't collect personal information or require account registration. Your privacy is protected by design – no tracking, no data harvesting, no strings attached.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </div>{{-- .seo-content --}}
    </details>{{-- .home-learn-more --}}
    </div>{{-- #airtoshare-app --}}

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeApp();
        });

        let selectedFiles = new Set();
        var shareHasPassword = @json(isset($share) && $share && $share->hasPassword());

        function getShareUuid() {
            var app = document.getElementById('airtoshare-app');
            if (!app) return null;
            var uuid = app.getAttribute('data-airtoshare-share-uuid');
            return uuid && uuid.length > 0 ? uuid : null;
        }

        function setShareUuid(uuid) {
            if (!uuid) return;
            var app = document.getElementById('airtoshare-app');
            if (app) app.setAttribute('data-airtoshare-share-uuid', uuid);
        }

        function getSharePageUrl() {
            var uuid = getShareUuid();
            if (!uuid) return null;
            return window.location.origin + '/s/' + uuid;
        }

        function copyToClipboard(text, onSuccess) {
            if (!text || !navigator.clipboard) {
                showToast('error', 'Copy failed', 'Could not copy to clipboard.');
                return;
            }
            navigator.clipboard.writeText(text).then(function () {
                if (typeof onSuccess === 'function') onSuccess();
                else showToast('success', 'Copied!', 'Link copied to clipboard.');
            }).catch(function () {
                showToast('error', 'Copy failed', 'Could not copy to clipboard.');
            });
        }

        var SHARE_LINK_HINT_OPEN = 'Copy this link and send it to anyone. They can open your text and files in any browser — no password needed.';
        var SHARE_LINK_HINT_PROTECTED = 'Send this link to others. They must enter your password first — share the password separately (WhatsApp, SMS, etc.), not in the link.';

        function updateShareLinkPanel() {
            var panel = document.getElementById('shareLinkPanel');
            var input = document.getElementById('sharePageLinkInput');
            if (!panel || !input) return;

            var app = document.getElementById('airtoshare-app');
            if (app && app.getAttribute('data-airtoshare-viewing') === '1') {
                panel.classList.add('hidden');
                return;
            }

            var url = getSharePageUrl();
            if (!url) {
                panel.classList.add('hidden');
                return;
            }

            input.value = url;
            panel.classList.remove('hidden');

            var badge = document.getElementById('shareLinkPasswordBadge');
            var hint = document.getElementById('shareLinkPanelHint');

            if (shareHasPassword) {
                if (badge) badge.classList.remove('hidden');
                if (hint) hint.textContent = SHARE_LINK_HINT_PROTECTED;
                if (app) app.setAttribute('data-airtoshare-has-password', '1');
                panel.classList.add('share-link-panel--protected');
            } else {
                if (badge) badge.classList.add('hidden');
                if (hint) hint.textContent = SHARE_LINK_HINT_OPEN;
                if (app) app.removeAttribute('data-airtoshare-has-password');
                panel.classList.remove('share-link-panel--protected');
            }
        }

        function refreshQrModalContent() {
            var shareUrl = getSharePageUrl();
            var display = document.getElementById('qrShareUrlDisplay');
            var line = document.getElementById('qrShareUrlLine');
            var instructions = document.getElementById('qrInstructions');
            var qrcodeDiv = document.getElementById('qrcode');
            var uuid = getShareUuid();

            if (shareUrl && display && line) {
                display.textContent = shareUrl;
                line.classList.remove('hidden');
            } else if (line) {
                line.classList.add('hidden');
            }

            if (instructions) {
                instructions.innerHTML = shareUrl
                    ? '<strong>Scan this QR code</strong> to open your share link on another phone or computer.'
                    : '<strong>Scan this QR code</strong> to open AirToShare on another device on the same network.';
            }

            if (!qrcodeDiv) return;

            var slot = document.querySelector('[data-qr-slot]');
            var qrImg = document.querySelector('[data-qr-image]');

            if (slot && qrImg && uuid) {
                qrImg.src = '{{ url('/qr') }}/' + encodeURIComponent(uuid);
                slot.classList.remove('hidden');
                qrcodeDiv.innerHTML = '';
                qrcodeDiv.style.display = 'none';
                return;
            }

            qrcodeDiv.style.display = '';
            qrcodeDiv.innerHTML = '';

            if (typeof QRCode !== 'undefined') {
                new QRCode(qrcodeDiv, {
                    text: shareUrl || window.location.origin + '/',
                    width: 200,
                    height: 200,
                    colorDark: '#0ea5e9',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });
            } else {
                qrcodeDiv.innerHTML = '<p class="qr-loading-text">QR Code loading...</p>';
            }
        }

        function openShareQrModal() {
            $('#qrModal').addClass('show');
            refreshQrModalContent();
        }

        function setupShareLinkPanel() {
            $('#copySharePageLinkBtn').on('click', function () {
                var url = getSharePageUrl();
                if (!url) {
                    showToast('info', 'No link yet', 'Save text or upload a file first to get your share link.');
                    return;
                }
                var btn = $(this);
                copyToClipboard(url, function () {
                    btn.find('span').text('Copied!');
                    btn.addClass('success');
                    showToast('success', 'Link copied!', 'Send this link to open your share.');
                    setTimeout(function () {
                        btn.find('span').text('Copy link');
                        btn.removeClass('success');
                    }, 2000);
                });
            });

            $('#openShareLinkQrBtn, #showQRBtn').on('click', function () {
                if (!getSharePageUrl()) {
                    showToast('info', 'No share yet', 'Save text or upload a file first — then share the link or QR code.');
                    openShareQrModal();
                    return;
                }
                openShareQrModal();
            });
        }

        function initializeApp() {
            var app = document.getElementById('airtoshare-app');
            var isViewing = app && app.getAttribute('data-airtoshare-viewing') === '1';

            if (isViewing) {
                setupViewerMode();
            } else {
                loadIpInfo();
                loadUploadLimits();
                fetchText();
                fetchMedia();
            }

            setupEventListeners();
            setupFaqAccordion();
            setupRealtimeBridge();

            // Restore saved active tab from localStorage
            const savedTab = localStorage.getItem('airtoshare-active-tab');
            if (savedTab && (savedTab === 'text' || savedTab === 'file')) {
                switchTab(savedTab);
            }
        }

        function setupViewerMode() {
            @if (isset($viewingShare) && $viewingShare && isset($share) && $share)
            var viewerText = @json(is_string($share->markdown_source) && $share->markdown_source !== '' ? $share->markdown_source : strip_tags((string) ($share->text_content ?? '')));
            var viewerMedia = @json($shareMedia ?? []);
            @else
            var viewerText = '';
            var viewerMedia = [];
            @endif

            $('#textInput').val(viewerText).prop('readonly', true);
            handleTextInput();
            if (viewerText.trim().length > 0) {
                updateSaveButton('copy');
            }
            $('#saveBtn, #clearBtn, #clipboardSyncBtn').addClass('hidden');
            $('#uploadZone').addClass('viewer-disabled').css('pointer-events', 'none').css('opacity', '0.55');
            $('#joinRoomBtn, #createRoomBtn, #sharePasswordBtn').prop('disabled', true).css('opacity', '0.5');
            $('#e2eeToggleWrap').css('opacity', '0.5');
            $('#shareLinkPanel').addClass('hidden');

            var filesMap = {};
            (viewerMedia || []).forEach(function (file) {
                filesMap[file.uuid] = {
                    uuid: file.uuid,
                    name: file.name,
                    size: file.size,
                    mime_type: file.mime_type,
                    original_url: file.url,
                    preview_url: file.preview_url || file.url,
                };
            });
            displayFiles(filesMap);
            $('#fileCount').text(Object.keys(filesMap).length);

            if (viewerText.trim().length || Object.keys(filesMap).length) {
                showToast('info', 'Protected share', 'You are viewing a password-protected share (read-only).');
            }
        }

        // FAQ Accordion Functionality
        function setupFaqAccordion() {
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                const answer = item.querySelector('.faq-answer');
                
                // Initially hide all answers
                answer.style.maxHeight = '0';
                answer.style.overflow = 'hidden';
                answer.style.transition = 'max-height 0.3s ease, padding 0.3s ease';
                answer.style.paddingTop = '0';
                answer.style.paddingBottom = '0';
                
                question.style.cursor = 'pointer';
                
                question.addEventListener('click', function() {
                    const isOpen = item.classList.contains('active');
                    
                    // Close all other items
                    faqItems.forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.classList.remove('active');
                            const otherAnswer = otherItem.querySelector('.faq-answer');
                            otherAnswer.style.maxHeight = '0';
                            otherAnswer.style.paddingTop = '0';
                            otherAnswer.style.paddingBottom = '0';
                        }
                    });
                    
                    // Toggle current item
                    if (isOpen) {
                        item.classList.remove('active');
                        answer.style.maxHeight = '0';
                        answer.style.paddingTop = '0';
                        answer.style.paddingBottom = '0';
                    } else {
                        item.classList.add('active');
                        answer.style.maxHeight = answer.scrollHeight + 20 + 'px';
                        answer.style.paddingTop = '0.5rem';
                        answer.style.paddingBottom = '0';
                    }
                });
            });
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

            // Clipboard sync
            setupClipboardSync();

            // File upload events
            setupFileUpload();

            // File selection events
            setupFileSelection();

            // Email modal events
            setupEmailModal();

            // QR Code Modal events
            setupQRModal();

            // Sound notifications
            setupSoundNotifications();

            // Auto-refresh for real-time sync
            setupAutoRefresh();

            // Device nickname
            setupDeviceNickname();

            // Share link modal
            setupShareModal();

            setupRoomUi();
            setupSharePasswordUi();
            setupShareLinkPanel();
            updateShareLinkPanel();
            setupE2eeToggle();
        }

        function setupRoomUi() {
            function openRoomModal() {
                $('#roomModal').addClass('show');
                $('#roomCodeError').addClass('hidden').text('');
                $('#roomCodeInput').val('').focus();
                $('#roomJoinPasswordInput').val('');
            }

            function closeRoomModal() {
                $('#roomModal').removeClass('show');
                $('#roomCodeError').addClass('hidden').text('');
            }

            function openCreateRoomModal() {
                $('#createRoomModal').addClass('show');
                $('#createRoomError').addClass('hidden').text('');
                $('#createRoomPassword').val('');
                $('#createRoomExpiry').val('24h');
            }

            function closeCreateRoomModal() {
                $('#createRoomModal').removeClass('show');
                $('#createRoomError').addClass('hidden').text('');
            }

            function showRoomError(msg) {
                $('#roomCodeError').text(msg).removeClass('hidden');
            }

            function showCreateRoomError(msg) {
                $('#createRoomError').text(msg).removeClass('hidden');
            }

            function submitRoomJoin() {
                var code = ($('#roomCodeInput').val() || '').trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
                var roomPassword = ($('#roomJoinPasswordInput').val() || '').trim();
                $('#roomCodeInput').val(code);
                if (code.length !== 6) {
                    showRoomError('Room codes are exactly 6 letters or numbers.');
                    showToast('error', 'Invalid code', 'Room codes are 6 characters.');
                    return;
                }
                if (roomPassword !== '' && roomPassword.length < 6) {
                    showRoomError('Room password must be at least 6 characters.');
                    return;
                }

                function goToRoom() {
                    window.location.href = '/r/' + encodeURIComponent(code);
                }

                if (roomPassword === '') {
                    goToRoom();
                    return;
                }

                $.ajax({
                    url: '/api/v1/rooms/' + encodeURIComponent(code) + '/verify-password',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ password: roomPassword }),
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: goToRoom,
                    error: function (xhr) {
                        var msg = xhr.responseJSON?.message || 'Password required';
                        showRoomError(msg);
                        showToast('error', 'Access denied', msg);
                    }
                });
            }

            function submitCreateRoom() {
                var expiry = $('#createRoomExpiry').val() || '24h';
                var password = ($('#createRoomPassword').val() || '').trim();
                if (password !== '' && password.length < 6) {
                    showCreateRoomError('Password must be at least 6 characters.');
                    return;
                }
                var payload = { expiry: expiry };
                if (password !== '') {
                    payload.password = password;
                }
                $('#createRoomConfirm').prop('disabled', true);
                $.ajax({
                    url: '{{ route('room.store') }}',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(payload),
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (data) {
                        closeCreateRoomModal();
                        if (data.code) {
                            showToast('success', 'Room created', 'Code: ' + data.code);
                            window.location.href = data.url || ('/r/' + data.code);
                        }
                    },
                    error: function (xhr) {
                        var msg = xhr.responseJSON?.message || xhr.responseJSON?.errors?.password?.[0] || 'Could not create room.';
                        showCreateRoomError(msg);
                        showToast('error', 'Room failed', msg);
                    },
                    complete: function () {
                        $('#createRoomConfirm').prop('disabled', false);
                    }
                });
            }

            $('#joinRoomBtn').on('click', openRoomModal);
            $('#roomModalClose, #roomJoinCancel').on('click', closeRoomModal);
            $('#roomModal').on('click', function (e) {
                if (e.target === this) closeRoomModal();
            });
            $('#roomModal .modal-content').on('click', function (e) {
                e.stopPropagation();
            });
            $('#roomJoinConfirm').on('click', submitRoomJoin);
            $('#roomCodeInput').on('input', function () {
                var cleaned = (this.value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
                if (this.value !== cleaned) this.value = cleaned;
                $('#roomCodeError').addClass('hidden').text('');
            });
            $('#roomCodeInput').on('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    submitRoomJoin();
                }
                if (e.key === 'Escape') {
                    closeRoomModal();
                }
            });
            $(document).on('keydown.roomModal', function (e) {
                if (e.key === 'Escape' && $('#roomModal').hasClass('show')) {
                    closeRoomModal();
                }
            });

            $('#createRoomBtn').on('click', openCreateRoomModal);
            $('#createRoomModalClose, #createRoomCancel').on('click', closeCreateRoomModal);
            $('#createRoomModal').on('click', function (e) {
                if (e.target === this) closeCreateRoomModal();
            });
            $('#createRoomConfirm').on('click', submitCreateRoom);
        }

        function updateSharePasswordUi() {
            var label = shareHasPassword ? 'Password on' : 'Password';
            $('#sharePasswordBtnLabel').text(label);
            $('#sharePasswordBtn').toggleClass('is-active', shareHasPassword);
            $('#sharePasswordClear').toggleClass('hidden', !shareHasPassword);
            $('#sharePasswordModalHint').text(
                shareHasPassword
                    ? 'Change the password or remove protection from your share link.'
                    : 'Add a password so only people with the secret can open your share link.'
            );
        }

        function setupSharePasswordUi() {
            var app = document.getElementById('airtoshare-app');
            if (app && app.getAttribute('data-airtoshare-viewing') === '1') {
                $('#sharePasswordBtn').hide();
                return;
            }

            function openSharePasswordModal() {
                $('#sharePasswordModal').addClass('show');
                $('#sharePasswordError').addClass('hidden').text('');
                $('#sharePasswordInput').val('').focus();
            }

            function closeSharePasswordModal() {
                $('#sharePasswordModal').removeClass('show');
                $('#sharePasswordError').addClass('hidden').text('');
            }

            function saveSharePassword(clear) {
                var payload = { password: clear ? '' : ($('#sharePasswordInput').val() || '').trim() };
                if (!clear && payload.password.length < 6) {
                    $('#sharePasswordError').text('Password must be at least 6 characters.').removeClass('hidden');
                    return;
                }
                $('#sharePasswordSave').prop('disabled', true);
                $.ajax({
                    url: '{{ route('share.password.update') }}',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(payload),
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (data) {
                        shareHasPassword = data.has_password === true;
                        updateSharePasswordUi();
                        updateShareLinkPanel();
                        closeSharePasswordModal();
                        var msg = data.message || (shareHasPassword ? 'Password protection enabled.' : 'Password removed.');
                        showToast('success', shareHasPassword ? 'Password on' : 'Password off', msg);
                    },
                    error: function (xhr) {
                        var msg = xhr.responseJSON?.errors?.password?.[0]
                            || xhr.responseJSON?.message
                            || 'Could not save password.';
                        $('#sharePasswordError').text(msg).removeClass('hidden');
                        showToast('error', 'Password failed', msg);
                    },
                    complete: function () {
                        $('#sharePasswordSave').prop('disabled', false);
                    }
                });
            }

            $('#sharePasswordBtn').on('click', openSharePasswordModal);
            $('#sharePasswordModalClose, #sharePasswordCancel').on('click', closeSharePasswordModal);
            $('#sharePasswordModal').on('click', function (e) {
                if (e.target === this) closeSharePasswordModal();
            });
            $('#sharePasswordSave').on('click', function () { saveSharePassword(false); });
            $('#sharePasswordClear').on('click', function () { saveSharePassword(true); });

            updateSharePasswordUi();
        }

        function setupE2eeToggle() {
            var toggle = document.getElementById('e2eeToggle');
            var wrap = document.getElementById('e2eeToggleWrap');
            var hint = document.getElementById('e2eeHint');
            var app = document.getElementById('airtoshare-app');
            if (!toggle) return;

            function disableE2ee(message) {
                toggle.checked = false;
                toggle.disabled = true;
                if (app) app.removeAttribute('data-airtoshare-e2ee');
                if (wrap) wrap.classList.add('e2ee-unavailable');
                if (hint) {
                    hint.textContent = message;
                    hint.classList.remove('hidden');
                }
            }

            function enableE2eeUi() {
                toggle.disabled = false;
                if (wrap) wrap.classList.remove('e2ee-unavailable');
                if (hint) {
                    hint.textContent = '';
                    hint.classList.add('hidden');
                }
            }

            if (!window.__airtoshareE2ee || !window.__airtoshareE2ee.isSupported()) {
                var msg = window.__airtoshareE2ee && window.__airtoshareE2ee.supportMessage
                    ? window.__airtoshareE2ee.supportMessage()
                    : 'E2EE is not available in this browser.';
                disableE2ee(msg);
                return;
            }

            enableE2eeUi();

            if (toggle.checked && app) {
                app.setAttribute('data-airtoshare-e2ee', '1');
                window.__airtoshareE2ee.ensureShareKey().catch(function (err) {
                    disableE2ee(err && err.message ? err.message : 'Could not generate encryption key.');
                    showToast('warning', 'E2EE', err && err.message ? err.message : 'Could not generate encryption key in this browser.');
                });
            }

            $('#e2eeToggle').on('change', function () {
                if (!app) return;
                if (this.checked) {
                    app.setAttribute('data-airtoshare-e2ee', '1');
                    if (window.__airtoshareE2ee && window.__airtoshareE2ee.ensureShareKey) {
                        window.__airtoshareE2ee.ensureShareKey().catch(function (err) {
                            toggle.checked = false;
                            app.removeAttribute('data-airtoshare-e2ee');
                            showToast('warning', 'E2EE', err && err.message ? err.message : 'Could not generate encryption key in this browser.');
                        });
                    }
                } else {
                    app.removeAttribute('data-airtoshare-e2ee');
                }
            });
        }

        function setupQRModal() {
            $('#qrModalClose').off('click').on('click', function() {
                $('#qrModal').removeClass('show');
            });

            $('#qrModal').off('click').on('click', function(e) {
                if (e.target === this) {
                    $(this).removeClass('show');
                }
            });

            $('#copyUrlBtn').off('click').on('click', function() {
                var url = getSharePageUrl() || window.location.origin + '/';
                var btn = $(this);
                copyToClipboard(url, function () {
                    btn.find('#copyUrlText').text('Copied!');
                    btn.addClass('success');
                    showToast('success', 'Link copied!', shareHasPassword
                        ? 'Send the link and password separately.'
                        : 'Share this link with anyone.');
                    setTimeout(function () {
                        btn.find('#copyUrlText').text('Copy share link');
                        btn.removeClass('success');
                    }, 2000);
                });
            });
        }

        // Device Nickname Functions
        function setupDeviceNickname() {
            // Load saved nickname from localStorage
            const savedNickname = localStorage.getItem('airtoshare-device-nickname');
            if (savedNickname) {
                $('#deviceNickname').text(savedNickname);
            }

            // Edit button click
            $('#editNicknameBtn').off('click').on('click', function() {
                toggleNicknameEdit(true);
            });

            // Click on nickname to edit
            $('#deviceNickname').off('click').on('click', function() {
                toggleNicknameEdit(true);
            });

            // Save on Enter, cancel on Escape
            $('#nicknameInput').off('keydown').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    saveNickname();
                } else if (e.key === 'Escape') {
                    toggleNicknameEdit(false);
                }
            });

            // Save on blur (click outside)
            $('#nicknameInput').off('blur').on('blur', function() {
                setTimeout(saveNickname, 100);
            });
        }

        function toggleNicknameEdit(editing) {
            const nicknameSpan = $('#deviceNickname');
            const nicknameInput = $('#nicknameInput');
            const editBtn = $('#editNicknameBtn');

            if (editing) {
                nicknameSpan.hide();
                editBtn.hide();
                nicknameInput.val(nicknameSpan.text()).show().focus().select();
            } else {
                nicknameInput.hide();
                nicknameSpan.show();
                editBtn.show();
            }
        }

        function saveNickname() {
            const nicknameInput = $('#nicknameInput');
            const nicknameSpan = $('#deviceNickname');
            const newNickname = nicknameInput.val().trim();

            if (newNickname && newNickname.length > 0 && newNickname.length <= 30) {
                nicknameSpan.text(newNickname);
                localStorage.setItem('airtoshare-device-nickname', newNickname);
                showToast('success', 'Device Renamed!', `This device is now "${newNickname}"`);
            }

            toggleNicknameEdit(false);
        }

        // Share Modal Functions
        let currentShareFile = null;
        let oneTimeLinks = JSON.parse(localStorage.getItem('airtoshare-onetime-links') || '{}');

        function setupShareModal() {
            // Close modal
            $('#shareModalClose').off('click').on('click', function() {
                $('#shareModal').removeClass('show');
            });

            // Click outside to close
            $('#shareModal').off('click').on('click', function(e) {
                if (e.target === this) {
                    $(this).removeClass('show');
                }
            });

            // Preview share button click
            $('#previewShareBtn').off('click').on('click', function() {
                if (allFiles.length > 0 && currentPreviewIndex >= 0) {
                    currentShareFile = allFiles[currentPreviewIndex];
                    openShareModal(currentShareFile);
                }
            });

            // One-time toggle change
            $('#oneTimeToggle').off('change').on('change', function() {
                const isOneTime = $(this).is(':checked');
                updateShareLink(isOneTime);
                $('#oneTimeNote').toggle(isOneTime);
                $('#oneTimeBadge').toggle(isOneTime);
            });

            // Copy share link
            $('#copyShareLink').off('click').on('click', function() {
                const link = $('#shareLinkInput').val();
                navigator.clipboard.writeText(link).then(() => {
                    const btn = $(this);
                    btn.find('span').text('Copied!');
                    btn.addClass('success');
                    showToast('success', 'Link Copied!', 'Share link copied to clipboard');
                    setTimeout(() => {
                        btn.find('span').text('Copy');
                        btn.removeClass('success');
                    }, 2000);
                });
            });
        }

        function openShareModal(file) {
            $('#shareFileName').text(file.name);
            $('#oneTimeToggle').prop('checked', false);
            $('#oneTimeNote').hide();
            $('#oneTimeBadge').hide();
            updateShareLink(false);
            $('#shareModal').addClass('show');
        }

        function updateShareLink(isOneTime) {
            if (!currentShareFile) return;

            let shareUrl = currentShareFile.original_url;

            if (isOneTime) {
                // Generate unique one-time token
                const token = generateToken();
                const uuid = currentShareFile.uuid;

                // Store one-time link info
                oneTimeLinks[token] = {
                    uuid: uuid,
                    fileName: currentShareFile.name,
                    createdAt: new Date().toISOString(),
                    used: false
                };
                localStorage.setItem('airtoshare-onetime-links', JSON.stringify(oneTimeLinks));

                // Create one-time link with token parameter
                shareUrl = `${window.location.origin}/download/${uuid}?onetime=${token}`;
            }

            $('#shareLinkInput').val(shareUrl);
        }

        function generateToken() {
            return 'ot_' + Math.random().toString(36).substring(2, 15) + Date.now().toString(36);
        }

        function generateQRCode() {
            refreshQrModalContent();
        }

        // Clipboard Sync Functions
        function setupClipboardSync() {
            $('#clipboardSyncBtn').off('click').on('click', async function() {
                const btn = $(this);
                const originalHtml = btn.html();

                try {
                    // Check if clipboard API is available
                    if (!navigator.clipboard || !navigator.clipboard.readText) {
                        showToast('warning', 'Not Supported', 'Clipboard access not available in this browser');
                        return;
                    }

                    // Show loading state
                    btn.html('<i class="fas fa-spinner fa-spin"></i> <span>Reading...</span>');
                    btn.prop('disabled', true);

                    // Read clipboard content
                    const clipboardText = await navigator.clipboard.readText();

                    if (!clipboardText || clipboardText.trim().length === 0) {
                        showToast('info', 'Empty Clipboard', 'No text content in clipboard');
                        btn.html(originalHtml);
                        btn.prop('disabled', false);
                        return;
                    }

                    // Get current text and append or replace
                    const currentText = $('#textInput').val();
                    const newText = currentText ? currentText + '\n\n' + clipboardText : clipboardText;

                    $('#textInput').val(newText);
                    handleTextInput(); // Update character counter and detect links

                    // Auto-save the pasted content
                    btn.html('<i class="fas fa-check"></i> <span>Pasted!</span>');
                    showToast('success', 'Clipboard Synced!',
                        `Added ${clipboardText.length} characters from clipboard`);

                    safeGtag('event', 'paste_clipboard', {
                        'clipboard_length': clipboardText.length
                    });

                    // Auto-save after small delay
                    setTimeout(() => {
                        handleSaveText();
                    }, 500);

                    // Reset button after delay
                    setTimeout(() => {
                        btn.html(originalHtml);
                        btn.prop('disabled', false);
                    }, 2000);

                } catch (err) {
                    console.error('Clipboard read failed:', err);

                    if (err.name === 'NotAllowedError') {
                        showToast('warning', 'Permission Denied',
                            'Please allow clipboard access when prompted');
                    } else {
                        showToast('error', 'Clipboard Error', 'Could not read clipboard content');
                    }

                    btn.html(originalHtml);
                    btn.prop('disabled', false);
                }
            });
        }

        // Sound Notification System
        let soundEnabled = localStorage.getItem('airtoshare-sound') !== 'false';
        let notificationSound = null;
        let previousFileCount = 0;
        let previousTextHash = '';

        function setupSoundNotifications() {
            // Create notification sound using Web Audio API
            try {
                const audioContext = new(window.AudioContext || window.webkitAudioContext)();
                notificationSound = {
                    play: function() {
                        if (!soundEnabled) return;

                        const oscillator = audioContext.createOscillator();
                        const gainNode = audioContext.createGain();

                        oscillator.connect(gainNode);
                        gainNode.connect(audioContext.destination);

                        oscillator.frequency.value = 800;
                        oscillator.type = 'sine';

                        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

                        oscillator.start(audioContext.currentTime);
                        oscillator.stop(audioContext.currentTime + 0.3);
                    }
                };
            } catch (e) {
                console.log('Web Audio not supported');
            }

            // Update sound icon based on saved preference
            updateSoundIcon();

            // Toggle sound on click
            $('#soundToggle').off('click').on('click', function() {
                soundEnabled = !soundEnabled;
                localStorage.setItem('airtoshare-sound', soundEnabled);
                updateSoundIcon();
                showToast('info', soundEnabled ? 'Sound On' : 'Sound Off',
                    soundEnabled ? 'You will hear alerts for new content' : 'Notification sounds disabled');
            });
        }

        function updateSoundIcon() {
            const icon = $('#soundIcon');
            icon.removeClass('fa-volume-up fa-volume-mute');
            icon.addClass(soundEnabled ? 'fa-volume-up' : 'fa-volume-mute');
            $('#soundToggle').toggleClass('muted', !soundEnabled);
        }

        function playNotificationSound() {
            if (notificationSound && soundEnabled) {
                notificationSound.play();
            }
        }

        // Auto-refresh for real-time sync
        let autoRefreshInterval = null;
        let lastActivityTime = new Date();

        // Handle visibility change to resume/pause updates efficiently
        document.addEventListener("visibilitychange", () => {
            if (!document.hidden) {
                // When tab becomes visible, we don't immediately fetch to avoid "flash".
                // We just ensure the interval is running (which it is, but paused effectively by the check).
                // Optionally, we could reset the timer to space out the next check.
            }
        });

        // Track self-uploads to avoid notifying the sender
        let pendingSelfUpload = false;
        let pendingSelfText = false;

        function markSelfUpload() {
            pendingSelfUpload = true;
            // Reset after 15 seconds (enough time for polling to catch up)
            setTimeout(() => {
                pendingSelfUpload = false;
            }, 15000);
        }

        function markSelfTextSave() {
            pendingSelfText = true;
            setTimeout(() => {
                pendingSelfText = false;
            }, 15000);
        }

        function setupAutoRefresh() {
            // Initial state
            updateLastActivityDisplay();

            // Start polling every 10 seconds for new content
            autoRefreshInterval = setInterval(() => {
                if (!document.hidden) {
                    checkForUpdates();
                }
            }, 10000);
        }

        function checkForUpdates() {
            // Check for new files
            $.ajax({
                url: '/api/v1/media/ip-info',
                method: 'GET',
                success: function(data) {
                    const currentFileCount = parseInt(data.files_count) || 0;

                    // If file count changed and increased, and NOT from self-upload
                    if (currentFileCount > previousFileCount && previousFileCount > 0 && !pendingSelfUpload) {
                        const newFiles = currentFileCount - previousFileCount;
                        showToast('info', 'New Files!', `${newFiles} new file(s) received from another device`);
                        playNotificationSound();
                        updateLastActivity();
                        fetchMedia(); // Refresh file list
                    } else if (currentFileCount !== previousFileCount) {
                        // Self upload or file removed - just refresh without notification
                        fetchMedia();
                        updateLastActivity();
                    }

                    previousFileCount = currentFileCount;
                }
            });

            // Check for text changes
            $.ajax({
                url: '{{ route('share.get.text') }}',
                method: 'GET',
                success: function(data) {
                    if (data.status === 'success' && data.text) {
                        const currentHash = hashCode(data.text);

                        // Only notify if changed and NOT from self
                        if (previousTextHash && currentHash !== previousTextHash && !pendingSelfText) {
                            showToast('info', 'Text Updated!', 'Shared text updated from another device');
                            playNotificationSound();
                            updateLastActivity();
                            $('#textInput').val(data.text);
                            handleTextInput();
                        }

                        previousTextHash = currentHash;
                    }
                }
            });
        }

        function hashCode(str) {
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                const char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash;
            }
            return hash;
        }

        function updateLastActivity() {
            lastActivityTime = new Date();
            updateLastActivityDisplay();
        }

        function updateLastActivityDisplay() {
            $('#lastActivity').removeClass('hidden');

            const updateDisplay = () => {
                const now = new Date();
                const diff = Math.floor((now - lastActivityTime) / 1000);

                let text;
                if (diff < 5) text = 'Just now';
                else if (diff < 60) text = `${diff}s ago`;
                else if (diff < 3600) text = `${Math.floor(diff / 60)}m ago`;
                else text = `${Math.floor(diff / 3600)}h ago`;

                $('#lastActivityTime').text(text);
            };

            updateDisplay();
            setInterval(updateDisplay, 10000); // Update every 10 seconds
        }

        // Expiry Countdown Timer
        let expiryTime = null;
        let countdownInterval = null;

        function startCountdown(expiresAt) {
            if (!expiresAt) return;

            expiryTime = new Date(expiresAt).getTime();
            $('#expiryCountdown').removeClass('hidden');

            if (countdownInterval) clearInterval(countdownInterval);
            countdownInterval = setInterval(updateCountdown, 1000);
            updateCountdown();
        }

        function updateCountdown() {
            if (!expiryTime) return;

            const now = new Date().getTime();
            const distance = expiryTime - now;

            if (distance < 0) {
                $('#countdownTimer').text('Expired');
                $('#expiryCountdown').addClass('expired');
                clearInterval(countdownInterval);
                return;
            }

            const hours = Math.floor(distance / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            const timeStr =
                `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            $('#countdownTimer').text(timeStr);

            // Visual warning when time is low (less than 1 hour)
            if (hours < 1) {
                $('#expiryCountdown').addClass('warning');
            } else {
                $('#expiryCountdown').removeClass('warning');
            }
        }

        function switchTab(tabName) {
            $('.modern-tab').removeClass('active');
            $(`.modern-tab[data-tab="${tabName}"]`).addClass('active');

            $('.tab-content').removeClass('active');
            $(`#${tabName}-tab`).addClass('active');

            // Save active tab to localStorage for persistence
            localStorage.setItem('airtoshare-active-tab', tabName);
        }

        function loadUploadLimits() {
            fetch('{{ route('limits.index') }}', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.status !== 'success' || !data.limits) return;
                    var zone = document.getElementById('uploadZone');
                    if (!zone) return;
                    zone.setAttribute('data-upload-max-files', String(data.limits.active_files_limit));
                    zone.setAttribute('data-upload-max-size', String(data.limits.legacy_upload_max_bytes));
                    zone.setAttribute('data-upload-chunked-max-size', String(data.limits.chunked_upload_max_bytes));
                    if (window.__airtoshareUploadManager && window.__airtoshareUploadManager.init) {
                        window.__airtoshareUploadManager.init();
                    }
                })
                .catch(function () { /* ignore */ });
        }

        function setupRealtimeBridge() {
            document.addEventListener('airtoshare:media.added', function () {
                fetchMedia();
                loadIpInfo();
            });
            document.addEventListener('airtoshare:media.deleted', function () {
                fetchMedia();
                loadIpInfo();
            });
            document.addEventListener('airtoshare:text.updated', function () {
                fetchText();
            });
            document.addEventListener('airtoshare:state', function () {
                fetchMedia();
                fetchText();
            });
            var uploadZone = document.getElementById('uploadZone');
            if (uploadZone) {
                uploadZone.addEventListener('upload:summary', function () {
                    fetchMedia();
                    loadIpInfo();
                    if (window.showToast) {
                        window.showToast('success', 'Upload complete', 'File queue finished');
                    }
                });
            }
        }

        function loadIpInfo() {
            $.ajax({
                url: '/api/v1/media/ip-info',
                method: 'GET',
                success: function(data) {
                    $('#userIp').text(data.ip);
                    $('#fileCount').text(data.files_count);
                    $('#maxFiles').text(data.max_files);
                    $('#maxFileSize').text(data.max_file_size);

                    // Start countdown timer if there are files
                    if (data.expires_at && data.files_count > 0) {
                        startCountdown(data.expires_at);
                    }

                    if (typeof data.has_password === 'boolean') {
                        shareHasPassword = data.has_password === true;
                        updateSharePasswordUi();
                    }
                    if (data.share_uuid) {
                        setShareUuid(data.share_uuid);
                    }
                    updateShareLinkPanel();
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
                        var source = (data.markdown_source && data.markdown_source.length)
                            ? data.markdown_source
                            : (data.text || '');
                        $('#textInput').val(source);
                        handleTextInput();
                        if (source.trim().length > 0) {
                            updateSaveButton('copy');
                        }
                        if (data.share_id) {
                            var app = document.getElementById('airtoshare-app');
                            if (app) {
                                app.setAttribute('data-airtoshare-share-id', data.share_id);
                            }
                        }
                        if (data.share_uuid) {
                            setShareUuid(data.share_uuid);
                        }
                        if (typeof data.has_password === 'boolean') {
                            shareHasPassword = data.has_password === true;
                            updateSharePasswordUi();
                        }
                        updateShareLinkPanel();
                    }
                },
                error: function() {
                    console.log('No existing text found');
                }
            });
        }

        function fetchMedia() {
            $.ajax({
                url: '{{ route('media.index') }}',
                method: 'GET',
                success: function(response) {
                    displayFiles(response.files || []);
                    $('#fileCount').text(Object.keys(response.files || {}).length);
                    if (response.share_uuid) {
                        setShareUuid(response.share_uuid);
                    }
                    if (typeof response.has_password === 'boolean') {
                        shareHasPassword = response.has_password === true;
                        updateSharePasswordUi();
                    }
                    updateShareLinkPanel();
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
            counter.text(`${length.toLocaleString()} / 500,000 characters`);

            counter.removeClass('warning danger');
            if (length > 450000) {
                counter.addClass('danger');
            } else if (length > 400000) {
                counter.addClass('warning');
            }
        }

        function detectLinks(text) {
            var items = [];
            var seen = Object.create(null);
            var m;

            function add(type, value, href) {
                var key = type + '|' + value.toLowerCase();
                if (seen[key]) return;
                seen[key] = true;
                items.push({ type: type, label: value, href: href });
            }

            function trimTrail(s) {
                return s.replace(/[.,;:!?)>\]]+$/g, '');
            }

            // Emails
            var emailRegex = /\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/g;
            while ((m = emailRegex.exec(text)) !== null) {
                add('email', m[0], 'mailto:' + m[0]);
            }

            // http(s) URLs
            var urlRegex = /https?:\/\/[^\s<>"']+/gi;
            while ((m = urlRegex.exec(text)) !== null) {
                var url = trimTrail(m[0]);
                add('url', url, url);
            }

            // www. without protocol
            var wwwRegex = /\bwww\.[^\s<>"']+/gi;
            while ((m = wwwRegex.exec(text)) !== null) {
                var www = trimTrail(m[0]);
                add('url', www, 'https://' + www);
            }

            // Bare domains e.g. dev.fileshare.test/path
            var domainRegex = /\b(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+(?:[a-z]{2,})(?:\/[^\s]*)?/gi;
            while ((m = domainRegex.exec(text)) !== null) {
                var bare = trimTrail(m[0]);
                if (bare.indexOf('@') !== -1) continue;
                var overlaps = items.some(function (i) {
                    if (i.type !== 'url') return false;
                    var normalized = i.label.replace(/^https?:\/\//i, '');
                    return normalized === bare || normalized.indexOf(bare) !== -1 || bare.indexOf(normalized) !== -1;
                });
                if (overlaps) continue;
                add('url', bare, 'http://' + bare);
            }

            var container = $('#linksContainer');
            var linksList = $('#linksList');

            if (items.length > 0) {
                linksList.empty();
                items.forEach(function (item) {
                    var icon = item.type === 'email' ? 'fa-envelope' : 'fa-external-link-alt';
                    var $link = $('<a>', {
                        href: item.href,
                        class: 'detected-link detected-link--' + item.type,
                        text: item.label
                    });
                    if (item.type === 'url') {
                        $link.attr({ target: '_blank', rel: 'noopener noreferrer' });
                    }
                    $link.prepend($('<i>', { class: 'fas ' + icon, 'aria-hidden': 'true' }));
                    linksList.append($link);
                });
                container.removeClass('hidden');
            } else {
                linksList.empty();
                container.addClass('hidden');
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

            if (text.length > 500000) {
                showMessage('textErrorMessage', 'Text is too long. Maximum 500,000 characters allowed.');
                return;
            }

            setButtonLoading(true);

            // Mark self-save to prevent notification on this browser
            markSelfTextSave();

            $.ajax({
                url: '{{ route('share.store.text') }}',
                method: 'POST',
                data: JSON.stringify({
                    text: text,
                    is_e2ee: $('#e2eeToggle').is(':checked') ? 1 : 0
                }),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    setButtonLoading(false);
                    showToast('success', 'Saved!', 'Text saved. Copy your share link from the panel above.');
                    loadIpInfo(); // Refresh timer and expiry info

                    if (data.share_id) {
                        var app = document.getElementById('airtoshare-app');
                        if (app) app.setAttribute('data-airtoshare-share-id', data.share_id);
                    }
                    if (data.share_uuid) {
                        setShareUuid(data.share_uuid);
                        updateShareLinkPanel();
                    }
                    if (window.__airtoshareRealtime && window.__airtoshareRealtime.init) {
                        window.__airtoshareRealtime.init();
                    }

                    safeGtag('event', 'save_text', {
                        'text_length': text.length
                    });

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
            if (navigator.clipboard && navigator.clipboard.writeText) {


                navigator.clipboard.writeText(text).then(() => {
                    showToast('success', 'Success!', 'Text copied to clipboard successfully');

                    safeGtag('event', 'copy_text', {
                        'text_length': text.length
                    });

                    // Visual feedback
                    const btn = $('#saveBtn');
                    btn.addClass('success');
                    setTimeout(() => btn.removeClass('success'), 1000);
                }).catch(() => {
                    showToast('error', 'Error!', 'Failed to copy text to clipboard');
                });
            } else {
                alert("Clipboard API not supported in this browser.");

            }
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
            const uploadZoneEl = document.getElementById('uploadZone');
            if (uploadZoneEl && uploadZoneEl.hasAttribute('data-upload-manager')) {
                // Handled by public/assets/js/upload-manager.js
                return;
            }

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
                markSelfUpload(); // Mark self-upload to prevent notification
                handleFileUpload(files);
            });

            fileInput.off('change').on('change', function() {
                markSelfUpload(); // Mark self-upload to prevent notification
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

        document.addEventListener("paste", function(event) {
            let items = (event.clipboardData || event.originalEvent.clipboardData).items;

            for (let index in items) {
                let item = items[index];

                // If clipboard contains image
                if (item.kind === 'file' && item.type.startsWith('image/')) {
                    let file = item.getAsFile();

                    // Auto-switch to File Upload tab

                    switchTab('file');
                    // Set file into input for preview/upload
                    const fileInput = document.getElementById("fileInput");

                    // Create DataTransfer to assign file programmatically
                    let dt = new DataTransfer();
                    dt.items.add(file);
                    fileInput.files = dt.files;

                    // Trigger your preview/upload function
                    markSelfUpload(); // Mark self-upload to prevent notification
                    handleFileUpload(dt.files);

                    showToast("success", "Image Detected", "Pasted image added to upload.");
                }
            }
        });

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
            const maxSize = 25 * 1024 * 1024; // 10MB

            if (file.size > maxSize) {
                showMessage('fileErrorMessage', `${file.name} exceeds the 25MB limit.`);
                return;
            }

            const formData = new FormData();
            formData.append('file', file);

            showProgress(true);

            $.ajax({
                url: '{{ route('media.store') }}',
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
                    safeGtag('event', 'file_upload', {
                        'file_name': file.name,
                        'file_size': file.size,
                        'file_type': file.type
                    });
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

            // Store all files for preview navigation
            allFiles = Object.values(files);

            if (!files || Object.keys(files).length === 0) {
                grid.addClass('empty').html(`
            <div class="empty-state">
                <i class="fas fa-folder-open empty-state-icon"></i>
                <p>No files uploaded yet. Start by dragging files above!</p>
            </div>
        `);
                controls.hide();
                return;
            }

            grid.removeClass('empty');
            controls.show();

            Object.values(files).forEach(file => {
                if (!file.original_url || !file.preview_url) {
                    console.warn('File missing URLs:', file);
                    return;
                }

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
                    url: '{{ route('media.destroy.all') }}',
                    method: 'DELETE',
                    data: {
                        uuid: uuid
                    },
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
        let allFiles = [];
        let currentPreviewIndex = 0;

        function createFileItem(file) {
            const isImage = file.mime_type.startsWith('image/');
            const isVideo = file.mime_type.startsWith('video/');
            const isPreviewable = isImage || isVideo;

            const item = $(`
        <div class="column is-12 preview-row file-item" data-uuid="${file.uuid}"
             data-preview-uuid="${file.uuid}"
             data-preview-mime="${file.mime_type}"
             data-preview-size="${file.size}"
             data-preview-url="${file.original_url}"
             data-preview-name="${file.name.replace(/"/g, '&quot;')}">
            <input type="checkbox" class="file-checkbox">
            <div class="file-preview preview-trigger" data-uuid="${file.uuid}">
                ${isPreviewable ? '' : '<i class="fas fa-file file-icon"></i>'}
            </div>
            <div class="file-info">
                <div class="file-name" title="${file.name}">${file.name}</div>
                <div class="file-size">${file.size}</div>
            </div>
            <div class="file-actions">
                <a class="action-btn download preview-download" href="${file.original_url}" download title="Download">
                    <span class="icon"><i class="fas fa-download"></i></span>
                </a>
                <button class="action-btn delete" title="Delete">
                    <span class="icon"><i class="fas fa-trash"></i></span>
                </button>
            </div>
        </div>
    `);

            // Checkbox selection
            item.find('.file-checkbox').change(function() {
                const uuid = item.data('uuid');
                if (this.checked) {
                    selectedFiles.add(uuid);
                    item.addClass('is-selected');
                } else {
                    selectedFiles.delete(uuid);
                    item.removeClass('is-selected');
                }
                updateSelectionUI();
            });

            // Preview click handler
            item.find('.preview-trigger').off('click').on('click', function() {
                const uuid = $(this).data('uuid');
                openPreviewModal(uuid);
            });

            // Download button (anchor — preview-renderer also exposes .preview-download)
            item.find('.download').off('click').on('click', function(e) {
                e.stopPropagation();
            });

            // Delete button
            item.find('.delete').off('click').on('click', function(e) {
                e.stopPropagation();
                deleteFile(file.uuid);
            });

            return item;
        }

        // Preview Modal Functions
        function openPreviewModal(uuid) {
            const index = allFiles.findIndex(f => f.uuid === uuid);
            if (index === -1) return;

            currentPreviewIndex = index;
            showPreview(currentPreviewIndex);
            $('#previewModal').addClass('show');
        }

        function hideAllPreviewElements() {
            $('#previewImage, #previewVideo, #previewAudio, #previewPdf, #previewText, #previewDocument')
                .addClass('hidden');
        }

        function showPreviewElement($el) {
            $el.removeClass('hidden');
        }

        function showPreview(index) {
            if (index < 0 || index >= allFiles.length) return;

            const file = allFiles[index];
            currentPreviewIndex = index;

            // Update header info
            $('#previewFileName').text(file.name);
            $('#previewFileSize').text(file.size);
            $('#previewCounter').text(`${index + 1} / ${allFiles.length}`);

            hideAllPreviewElements();

            // Stop any playing media
            const video = $('#previewVideo')[0];
            const audio = $('#previewAudio')[0];
            if (video) video.pause();
            if (audio) audio.pause();

            const mediaUrl = file.preview_url || file.original_url;
            const mimeType = (file.mime_type || '').toLowerCase();
            const fileName = (file.name || '').toLowerCase();

            if (mimeType.startsWith('image/')) {
                const img = $('#previewImage');
                img.off('error.preview').on('error.preview', function () {
                    if (file.original_url && img.attr('src') !== file.original_url) {
                        img.attr('src', file.original_url);
                    }
                });
                img.attr('src', mediaUrl);
                showPreviewElement(img);
            } else if (mimeType.startsWith('video/')) {
                video.src = mediaUrl;
                video.load();
                showPreviewElement($('#previewVideo'));
            } else if (mimeType.startsWith('audio/')) {
                audio.src = mediaUrl;
                audio.load();
                showPreviewElement($('#previewAudio'));
            } else if (mimeType === 'application/pdf') {
                showPreviewElement($('#previewPdf').attr('src', mediaUrl));
            } else if (isTextFile(mimeType, fileName)) {
                loadTextPreview(file);
            } else {
                const icon = getFileIcon(mimeType);
                showPreviewElement(
                    $('#previewDocument').find('i').attr('class', `fas ${icon} preview-doc-icon`).end()
                );
            }

            // Update navigation buttons
            $('#previewPrevBtn').prop('disabled', index === 0);
            $('#previewNextBtn').prop('disabled', index === allFiles.length - 1);
        }

        // Check if file is a text/code file
        function isTextFile(mimeType, fileName) {
            const textMimes = ['text/', 'application/json', 'application/javascript', 'application/xml',
                'application/x-httpd-php'
            ];
            const codeExtensions = ['.js', '.ts', '.jsx', '.tsx', '.py', '.php', '.html', '.css', '.scss', '.sass',
                '.json', '.xml', '.yaml', '.yml', '.md', '.txt', '.log', '.sql', '.sh', '.bash',
                '.c', '.cpp', '.h', '.java', '.rb', '.go', '.rs', '.swift', '.kt', '.vue', '.svelte'
            ];

            if (textMimes.some(m => mimeType.includes(m))) return true;
            return codeExtensions.some(ext => fileName.endsWith(ext));
        }

        // Get language from file extension
        function getLanguageFromFile(fileName) {
            const ext = fileName.substring(fileName.lastIndexOf('.')).toLowerCase();
            const langMap = {
                '.js': 'JavaScript',
                '.ts': 'TypeScript',
                '.jsx': 'React JSX',
                '.tsx': 'React TSX',
                '.py': 'Python',
                '.php': 'PHP',
                '.html': 'HTML',
                '.css': 'CSS',
                '.scss': 'SCSS',
                '.json': 'JSON',
                '.xml': 'XML',
                '.yaml': 'YAML',
                '.yml': 'YAML',
                '.md': 'Markdown',
                '.txt': 'Text',
                '.log': 'Log',
                '.sql': 'SQL',
                '.sh': 'Shell',
                '.bash': 'Bash',
                '.c': 'C',
                '.cpp': 'C++',
                '.h': 'C Header',
                '.java': 'Java',
                '.rb': 'Ruby',
                '.go': 'Go',
                '.rs': 'Rust',
                '.swift': 'Swift',
                '.kt': 'Kotlin',
                '.vue': 'Vue',
                '.svelte': 'Svelte',
                '.env': 'Environment'
            };
            return langMap[ext] || 'Text';
        }

        // Load text file content for preview
        function loadTextPreview(file) {
            showPreviewElement($('#previewText'));
            $('#previewCodeContent').text('Loading...');
            $('#previewLanguage').text(getLanguageFromFile(file.name));

            fetch(file.preview_url || file.original_url)
                .then(response => response.text())
                .then(text => {
                    // Limit preview to first 10000 characters
                    const truncated = text.length > 10000 ? text.substring(0, 10000) + '\n\n... (truncated)' : text;
                    $('#previewCodeContent').text(truncated);
                })
                .catch(err => {
                    $('#previewCodeContent').text('Failed to load file content');
                });
        }

        // Setup copy code button
        document.addEventListener('DOMContentLoaded', function() {
            $(document).on('click', '#previewCopyCodeBtn', function() {
                const code = $('#previewCodeContent').text();
                navigator.clipboard.writeText(code).then(() => {
                    const btn = $(this);
                    btn.html('<i class="fas fa-check"></i>');
                    showToast('success', 'Copied!', 'Code copied to clipboard');
                    setTimeout(() => btn.html('<i class="fas fa-copy"></i>'), 2000);
                });
            });
        });

        function getFileIcon(mimeType) {
            if (mimeType.includes('pdf')) return 'fa-file-pdf';
            if (mimeType.includes('word') || mimeType.includes('document')) return 'fa-file-word';
            if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return 'fa-file-excel';
            if (mimeType.includes('zip') || mimeType.includes('rar')) return 'fa-file-archive';
            if (mimeType.includes('text')) return 'fa-file-alt';
            return 'fa-file';
        }

        function closePreviewModal() {
            $('#previewModal').removeClass('show');
            hideAllPreviewElements();

            // Stop any playing media
            const video = $('#previewVideo')[0];
            const audio = $('#previewAudio')[0];
            if (video) {
                video.pause();
                video.src = '';
            }
            if (audio) {
                audio.pause();
                audio.src = '';
            }
        }

        function showNextPreview() {
            if (currentPreviewIndex < allFiles.length - 1) {
                showPreview(currentPreviewIndex + 1);
            }
        }

        function showPrevPreview() {
            if (currentPreviewIndex > 0) {
                showPreview(currentPreviewIndex - 1);
            }
        }

        function downloadCurrentPreview() {
            if (currentPreviewIndex >= 0 && currentPreviewIndex < allFiles.length) {
                downloadSingleFile(allFiles[currentPreviewIndex]);
            }
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
                const fileUrl = fileItem.attr('data-preview-url') ||
                    fileItem.find('.preview-download').attr('href') ||
                    fileItem.find('.file-preview img.preview-media').attr('src');

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
                url: '/api/v1/media/download-zip',
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
            $('#emailModal').addClass('is-active');
        }

        function hideEmailModal() {
            $('#emailModal').removeClass('is-active');
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
                url: '{{ route('media.destroy.all') }}',
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
            $('#fullscreenOverlay').addClass('is-active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            $('#fullscreenClose, #fullscreenOverlay').click(function(e) {
                if (e.target === this) {
                    $('#fullscreenOverlay').removeClass('is-active');
                }
            });
        });

        function showMessage(elementId, message) {
            const element = $(`#${elementId}`);
            element.find('span').text(message);
            element.show();

            setTimeout(() => {
                element.hide();
            }, 5000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Close modal
            $('#previewCloseBtn').click(function(e) {

                closePreviewModal();

            });

            // Navigation
            $('#previewNextBtn').click(showNextPreview);
            $('#previewPrevBtn').click(showPrevPreview);

            // Download
            $('#previewDownloadBtn, #previewDocDownloadBtn').click(downloadCurrentPreview);

            // Keyboard navigation
            $(document).keydown(function(e) {
                if ($('#previewModal').hasClass('show')) {
                    if (e.key === 'Escape') {
                        closePreviewModal();
                    } else if (e.key === 'ArrowRight') {
                        showNextPreview();
                    } else if (e.key === 'ArrowLeft') {
                        showPrevPreview();
                    }
                }
            });
        });

        // QR slot fallback wiring (task 7.3, design.md > Component 1 frontend).
        //
        // Acceptance criterion 1.5: when /qr/{slug} fails (the controller
        // returns HTTP 503 with the fallback HTML), the surrounding <img>
        // fires its `error` event. We replace the broken image and its
        // download anchor with the share URL text plus an error banner so
        // the user can still copy the link manually. The download
        // affordance is removed entirely in this branch (criterion 1.5
        // forbids offering a download when generation failed).
        //
        // Wired with addEventListener so the handler is independent of the
        // jQuery-based UI plumbing above; if jQuery fails to load the
        // fallback still works.
        (function () {
            var slot = document.querySelector('[data-qr-slot]');
            if (!slot) {
                return;
            }
            var img = slot.querySelector('[data-qr-image]');
            var anchor = slot.querySelector('[data-qr-download]');
            var fallback = slot.querySelector('[data-qr-fallback]');
            if (!img || !fallback) {
                return;
            }

            function revealFallback() {
                if (anchor) {
                    anchor.classList.add('hidden');
                    // Disable the click-to-download path entirely so a
                    // late-arriving click cannot trigger a 503 PNG load.
                    anchor.setAttribute('aria-hidden', 'true');
                    anchor.setAttribute('tabindex', '-1');
                    anchor.addEventListener('click', function (e) {
                        e.preventDefault();
                    });
                }
                fallback.classList.remove('hidden');
            }

            img.addEventListener('error', revealFallback);
        })();
    </script>
@endsection
