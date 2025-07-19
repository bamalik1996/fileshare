@extends('layouts.app')

@section('title', 'Home Page')

@section('content')
    <style>
        .tabs {
            margin-bottom: 20px;
        }

        .tab-content {
            display: none;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
            min-height: 400px;
        }

        .tab-content.active {
            display: block;
        }

        .form-file-drop {
            height: 100%;
        }

        .cta-button {
            background-color: rgba(51, 51, 51, 0.05);
            border-radius: 8px;
            border-width: 0;
            color: #333333;
            cursor: pointer;
            font-family: "Haas Grot Text R Web", "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 24px;
            font-weight: 500;
            line-height: 20px;
            list-style: none;
            margin: 0;
            padding: 15px 35px;
            text-align: center;
            transition: all 200ms;
            vertical-align: baseline;
            white-space: nowrap;
            user-select: none;
            -webkit-user-select: none;
            touch-action: manipulation;
            justify-content: center;
            align-items: center;
            display: flex;
            position: relative;
        }

        .cta-button:hover {
            background-color: #1b3fab;
            color: #fff;
        }
        
        .cta-button:disabled {
            background-color: #ccc;
            color: #666;
            cursor: not-allowed;
        }

        .clear-button {
            background-color: #ff1d1d;
            color: white;
            padding: 15px 35px;
            font-size: 24px;
            font-weight: 500;
            line-height: 20px;
            text-align: center;
            margin-right: 10px;
            border-radius: 8px;
            border-width: 0;
            -webkit-user-select: none;
            touch-action: manipulation;
            cursor: pointer;
        }

        .clear-button:hover {
            background-color: #D10000;
        }

        .clear-button-container {
            display: none;
            text-align: center;
        }

        .loader {
            border: 4px solid #f3f3f3;
            /* Light gray */
            border-top: 4px solid #3498db;
            /* Blue */
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: none;
            /* Initially hidden */
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .loading-text {
            display: none;
            /* Initially hidden */
            color: #ffffff;
        }

        div#linksContainer {
            margin-top: 10px;
            word-wrap: break-word;
        }

        .cta-button.copied:hover,
        .copied {
            background: #48c78e;
            color: #ffff;
        }

        /* File Upload Box */
        .file-upload {
            border: 2px dashed #007bff;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            background: #f9f9f9;
            border-radius: 10px;
            transition: 0.3s;
            position: relative;
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .file-upload:hover {
            background: #eef5ff;
            border-color: #0056b3;
        }
        
        .file-upload.dragover {
            background: #e3f2fd;
            border-color: #1976d2;
            transform: scale(1.02);
        }
        
        .file-upload-text {
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .file-upload-subtext {
            font-size: 12px;
            color: #666;
        }

        /* File Preview Container */
        .file-preview-container {
            display: flex;
            flex-wrap: wrap;
            margin-top: 15px;
            gap: 15px;
            overflow-y: auto;
            max-height: 400px;
            min-height: 200px;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 8px;
            background: #fafafa;
        }

        /* Individual File Preview */
        .file-preview {
            width: 169px;
            height: 150px;
            border: 1px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            background: #f8f8f8;
            border-radius: 5px;
            font-size: 40px;
            cursor: pointer;
            transition: all 0.3s ease;
            flex-direction: column;
        }

        .file-preview:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border-color: #007bff;
        }

        /* Image Preview */
        .file-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .file-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px;
            font-size: 10px;
            text-align: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .file-preview:hover .file-info {
            opacity: 1;
        }

        /* Loader Animation */
        .file-preview .loader {
            position: absolute;
            width: 30px;
            height: 30px;
            border: 4px solid #ccc;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Full-Screen Image Preview */
        .full-screen-preview {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            visibility: hidden;
            opacity: 0;
            transition: 0.3s;
            z-index: 1000;
        }

        .full-screen-preview img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 10px;
        }

        .full-screen-preview.show {
            visibility: visible;
            opacity: 1;
        }

        /* Close Button */
        .close-preview {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 30px;
            color: white;
            cursor: pointer;
        }


        .download-btn {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }

        .download-btn:hover {
            background: #0056b3;
        }

        .info-text {
            margin-top: 15px;
            padding: 10px;
            background: #e8f4fd;
            color: #1976d2;
            text-align: center;
            font-weight: bold;
            border-radius: 5px;
            font-size: 14px;
        }

        .delete-btn {
            position: absolute;
            top: 0;
            right: 0;
            border-radius: 100%;
            cursor: pointer;
            margin-top: 5px;
            padding: 5px 10px;
            background: red;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 12px;
            border-radius: 5px;
            margin-right: 5px;
            transition: all 0.3s;
        }
        
        .delete-btn:hover {
            background: #d32f2f;
            transform: scale(1.1);
        }

        .wrapper-loader {
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            position: absolute;
            background: rgb(10 10 10 / 26%);
            z-index: 999;
            border-radius: 5px;
        }

        div#text-tab div#loader {
            display: none;
            position: absolute;
        }
        
        .character-count {
            position: absolute;
            bottom: 10px;
            right: 15px;
            font-size: 12px;
            color: #666;
        }
        
        .progress-bar {
            width: 100%;
            height: 4px;
            background: #eee;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: #007bff;
            transition: width 0.3s ease;
        }
        
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            display: none;
        }
        
        .success-message {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            display: none;
        }
    </style>

    <h1 class="title is-2 has-text-centered has-text-primary">Welcome to AirForShare</h1>
    
    <!-- IP Info Display -->
    <div class="notification is-info is-light" id="ipInfo" style="margin-bottom: 20px;">
        <strong>Your IP:</strong> <span id="userIp">Loading...</span> | 
        <strong>Files:</strong> <span id="fileCount">0</span>/<span id="maxFiles">20</span> | 
        <strong>Max File Size:</strong> <span id="maxFileSize">10 MB</span>
    </div>

    <!-- Tabs for Navigation -->
    <div class="tabs is-centered is-boxed">
        <ul>
            <li class="is-active" data-tab-id='text-tab'>
                <a>Text</a>
            </li>
            <li data-tab-id="file-tab">
                <a>File</a>
            </li>
        </ul>
    </div>

    <!-- Tab Contents -->
    <div class="tab-content active" id="text-tab" style="padding: 0;">
        <div class="form-box">
            <!-- Text Area for Type Something -->
            <div class="field">
                <div class="control">
                    <textarea class="textarea" id="textInput" placeholder="Type something..." rows="8"
                        style="border: 0; font-size: 25px; overflow: hidden; overflow-wrap: break-word; resize: none;"
                        oninput="checkText()" maxlength="50000"></textarea>
                    <div class="character-count">
                        <span id="charCount">0</span>/50000
                    </div>
                    <div id="linksContainer" style="margin-top: 10px;"></div>
                </div>
            </div>
            
            <!-- Messages -->
            <div id="textErrorMessage" class="error-message"></div>
            <div id="textSuccessMessage" class="success-message"></div>
            
            <!-- Save and Clear Buttons -->
            <div class="has-text-right is-flex is-flex-direction-row-reverse"
                style="padding-bottom:10px;padding-right:10px">
                <button class="cta-button" id="saveBtn">
                    <span id="saveTextBtn">Save</span>
                    <div id="loader" class="loader"></div>
                    <div id="loadingText" class="loading-text">Saving...</div>
                </button>
                <div class="clear-button-container">
                    <button class="clear-button" onclick="clearText()">Clear</button>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-content" id="file-tab">
        <div class="form-box form-file-drop">
            <h3 class="title is-3 has-text-centered">Upload Files</h3>

            <!-- Drag and Drop File Upload Section -->
            <div class="file-upload" id="dropZone">
                <div class="file-upload-text">🚀 Drag and drop files here, or click to browse</div>
                <div class="file-upload-subtext">Supported: Images, PDF, DOC, TXT, ZIP (Max: 10MB each)</div>
                <input type="file" id="fileInput" class="file-input" multiple accept="image/*,.pdf,.docx,.txt" hidden>
                <div class="progress-bar" id="uploadProgress" style="display: none;">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
            </div>
            
            <!-- Messages -->
            <div id="fileErrorMessage" class="error-message"></div>
            <div id="fileSuccessMessage" class="success-message"></div>

            <!-- File Previews -->
            <div class="file-preview-container" id="filePreviewContainer"></div>

            <!-- File Info Text -->
            <div class="info-text has-text-centered">
                <p>📁 Upload up to <strong>20 files, 10MB each</strong></p>
                <p>🔒 Files are automatically deleted after 6 hours or 1 hour of inactivity</p>
            </div>
        </div>
    </div>

    <div class="full-screen-preview" id="fullScreenPreview">
        <span class="close-preview" id="closePreview">&times;</span>
        <img id="previewImage" src="" alt="Full Preview">
        <a id="downloadPreview" class="download-btn" download>
            ⬇ Download
        </a>
    </div>

    <script>
        $(document).ready(function() {
            loadIpInfo();
            fetchText();
            fetchMedia(); // auto-load media

            $(".tabs ul li").click(function() {
                // Remove 'is-active' class from all tabs
                $(".tabs ul li").removeClass("is-active");
                $(this).addClass("is-active");

                // Hide all tab content
                $(".tab-content").hide();

                // Show the clicked tab's content
                let targetTab = $(this).attr('data-tab-id').toLowerCase();
                $("#" + targetTab).show();
            });

        });

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

        function showMessage(elementId, message, isError = false) {
            const element = $(elementId);
            element.text(message).show();
            
            setTimeout(() => {
                element.hide();
            }, 5000);
        }

        function fetchText() {
            $.ajax({
                url: '{{ route('share.get.text') }}',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#textInput').text(data.text);
                    checkText();
                    detectLinks();
                    updateCharCount();
                    if (data && data.text && data.text.trim().length > 0) {
                        showCopyButton(); // Agar pehle se text hai to copy button show karein
                    }
                },
                error: function() {
                    showMessage('#textErrorMessage', 'Error fetching the text.', true);
                }
            });
        }

        function saveText() {
            var text = $('#textInput').val();
            
            if (text.length > 50000) {
                showMessage('#textErrorMessage', 'Text is too long. Maximum 50,000 characters allowed.', true);
                return;
            }

            $('#loader').show();
            $('#loadingText').show();
            $('#saveTextBtn').hide();
            $('#saveBtn').prop('disabled', true);

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
                    $('#loader').hide();
                    $('#loadingText').hide();
                    $('#saveTextBtn').show();
                    $('#saveBtn').prop('disabled', false);
                    
                    showMessage('#textSuccessMessage', 'Text saved successfully!');
                    
                    if (text) {
                        showCopyButton();
                    } else {
                        showSaveButton()
                        $('#linksContainer').html('')
                    }
                },
                error: function() {
                    $('#loader').hide();
                    $('#loadingText').hide();
                    $('#saveTextBtn').show();
                    $('#saveBtn').prop('disabled', false);
                    
                    let errorMsg = 'Error occurred while saving the text.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    showMessage('#textErrorMessage', errorMsg, true);
                }
            });
        }

        function fetchMedia() {
            $.ajax({
                url: '{{ route('share.get.media') }}',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    let files = Object.values(response.files); // 👈 fix here

                    let container = $('#filePreviewContainer');
                    container.empty();

                    if (files.length === 0) {
                        container.append('<p style="text-align: center; color: #666; padding: 20px;">No files uploaded yet. Drag and drop files above to get started!</p>');
                        return;
                    }

                    // Update file count
                    $('#fileCount').text(files.length);

                    files.forEach(file => {
                        let preview = $('<div>', {
                            class: 'file-preview',
                            title: 'View: ' + file.name,
                            'data-uuid': file.uuid
                        });

                        if (file.mime_type.startsWith('image')) {
                            preview.append($('<img>', {
                                src: file.original_url,
                                alt: file.name
                            }));
                        } else if (file.mime_type.startsWith('video')) {
                            preview.append(
                                `<video width="200" controls><source src="${file.original_url}" type="${file.file_type}"></video>`
                            );
                        } else {
                            preview.append(
                                `<div style="font-size: 40px;">📄</div>`
                            );
                        }
                        
                        // Add file info
                        let fileInfo = $('<div>', {
                            class: 'file-info',
                            html: `${file.name}<br>${file.size}`
                        });
                        preview.append(fileInfo);

                        // Add delete button
                        let deleteBtn = $('<button>', {
                            class: 'delete-btn',
                            title: 'Delete',
                            text: 'X',
                            click: function() {
                                deleteMedia(file.uuid);
                            }
                        });

                        preview.append(deleteBtn);
                        container.append(preview);
                    });
                },
                error: function() {
                    console.error('Failed to fetch media.');
                    showMessage('#fileErrorMessage', 'Failed to load files.', true);
                }
            });
        }

        function deleteMedia(uuid) {
            if (!confirm('Are you sure you want to delete this file?')) return;

            $.ajax({
                url: '{{ route('share.delete.media') }}',
                method: 'delete',
                data: {
                    uuid: uuid
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    fetchMedia(); // Reload media list
                    loadIpInfo(); // Update IP info
                    showMessage('#fileSuccessMessage', 'File deleted successfully!');
                },
                error: function() {
                    showMessage('#fileErrorMessage', 'Failed to delete file.', true);
                }
            });
        }

        function copyText() {
            var text = $('#textInput').val();

            if (navigator.clipboard && navigator.clipboard.writeText) {
                // Modern browsers
                navigator.clipboard.writeText(text)
                    .then(function() {
                        jQuery('#saveBtn').addClass('copied')
                        showMessage('#textSuccessMessage', 'Text copied to clipboard!');
                        setTimeout(() => {
                            jQuery('#saveBtn').removeClass('copied')
                        }, 1000)
                    })
                    .catch(function(err) {
                        fallbackCopyText(text);
                    });
            } else {
                // Fallback for older browsers
                fallbackCopyText(text);
            }
        }

        function fallbackCopyText(text) {
            var tempTextArea = document.createElement("textarea");
            tempTextArea.value = text;
            document.body.appendChild(tempTextArea);
            tempTextArea.select();
            document.execCommand("copy"); // Fallback method
            document.body.removeChild(tempTextArea);
            jQuery('#saveBtn').addClass('copied')
            showMessage('#textSuccessMessage', 'Text copied to clipboard!');
            setTimeout(() => {
                jQuery('#saveBtn').removeClass('copied')
            }, 1000)
        }

        function detectLinks() {
            var text = $('#textInput').val(); // Get text from textarea
            var urlRegex = /(https?:\/\/[^\s]+)/g; // Regular expression to detect URLs
            var linksContainer = $('#linksContainer'); // Div where links will be shown

            linksContainer.empty(); // Clear old links

            var matches = text.match(urlRegex); // Find all URLs
            if (matches) {
                matches.forEach(function(url) {
                    var linkElement = $('<a></a>')
                        .attr('href', url)
                        .attr('target', '_blank')
                        .text(url)
                        .css({
                            'display': 'block',
                            'color': '#1b3fab',
                            'margin-top': '5px'
                        }); // Styling links

                    linksContainer.append(linkElement); // Append new link
                });
            }
        }

        function showCopyButton() {
            $('#saveBtn').html(`
        <span id="copyTextBtn">Copy</span>
        <div id="loader" class="loader"></div>
        <div id="loadingText" class="loading-text">Saving...</div>
    `);
            $('#saveBtn').off('click').on('click', copyText); // Copy button click event bind karein
        }

        function showSaveButton() {
            $('#saveBtn').html(`
        <span id="saveTextBtn">Save</span>
        <div id="loader" class="loader"></div>
        <div id="loadingText" class="loading-text">Saving...</div>
    `);
            $('#saveBtn').off('click').on('click', saveText); // Save button click event bind karein
        }

        function updateCharCount() {
            const text = $('#textInput').val();
            const count = text.length;
            $('#charCount').text(count);
            
            if (count > 45000) {
                $('#charCount').css('color', '#c62828');
            } else if (count > 40000) {
                $('#charCount').css('color', '#f57c00');
            } else {
                $('#charCount').css('color', '#666');
            }
        }

        function checkText() {
            var text = $('#textInput').val();
            if (text.length > 0) {
                $('.clear-button-container').show();
            } else {
                $('.clear-button-container').hide();
            }
            showSaveButton(); // Jaise hi text change ho, Save button wapas aajaye
            updateCharCount();
        }

        function clearText() {
            $('#textInput').val('');
            $('.clear-button-container').hide();
            updateCharCount();
            saveText();
        }

        $('#textInput').on('input', function() {
            showSaveButton();
            detectLinks(); // Call function when user types
            updateCharCount();
        });

        $('#saveBtn').on('click', saveText);

        document.addEventListener("DOMContentLoaded", function() {
            const dropZone = document.getElementById("dropZone");
            const fileInput = document.getElementById("fileInput");
            const previewContainer = document.getElementById("filePreviewContainer");
            const fullScreenPreview = document.getElementById("fullScreenPreview");
            const previewImage = document.getElementById("previewImage");
            const closePreview = document.getElementById("closePreview");
            const uploadProgress = document.getElementById("uploadProgress");
            const progressFill = document.getElementById("progressFill");

            const maxFiles = 20;
            const maxFileSize = 10 * 1024 * 1024; // 10MB
            let uploadedFiles = []; // Store uploaded files
            let isUploading = false;

            dropZone.addEventListener("click", () => fileInput.click());

            dropZone.addEventListener("dragover", (e) => {
                e.preventDefault();
                dropZone.classList.add("dragover");
            });

            dropZone.addEventListener("dragleave", () => {
                dropZone.classList.remove("dragover");
            });

            dropZone.addEventListener("drop", (e) => {
                e.preventDefault();
                dropZone.classList.remove("dragover");
                handleFiles(e.dataTransfer.files);
            });

            fileInput.addEventListener("change", (e) => {
                handleFiles(e.target.files);
            });

            function handleFiles(files) {
                if (isUploading) {
                    showMessage('#fileErrorMessage', 'Please wait for current upload to complete.', true);
                    return;
                }
                
                if (uploadedFiles.length + files.length > maxFiles) {
                    showMessage('#fileErrorMessage', `You can only upload up to ${maxFiles} files.`, true);
                    return;
                }

                [...files].forEach((file) => {
                    if (file.size > maxFileSize) {
                        showMessage('#fileErrorMessage', `${file.name} exceeds the 10MB limit.`, true);
                        return;
                    }
                    uploadFile(file);
                });
            }

            function uploadFile(file) {
                isUploading = true;
                uploadProgress.style.display = 'block';
                
                const reader = new FileReader();
                const previewDiv = document.createElement("div");
                previewDiv.classList.add("file-preview");

                const parentLoader = document.createElement("div");
                parentLoader.classList.add("wrapper-loader");
                const loader = document.createElement("div");
                loader.classList.add("loader");
                parentLoader.appendChild(loader);

                previewDiv.appendChild(parentLoader);
                previewContainer.appendChild(previewDiv);

                reader.onload = function(e) {
                    if (file.type.startsWith("image/")) {
                        const img = document.createElement("img");
                        img.src = e.target.result;
                        previewDiv.title = `View: ${file.name}`; // 🏷 Tooltip
                        previewDiv.appendChild(img);
                        previewDiv.addEventListener("click", () => openFullScreenPreview(e.target.result));
                    } else {
                        const fileIcon = document.createElement("span");
                        fileIcon.textContent = "📄"; // Non-image file icon
                        fileIcon.style.fontSize = "40px";
                        previewDiv.appendChild(fileIcon);
                        previewDiv.title = `Download: ${file.name}`; // 🏷 Tooltip
                        previewDiv.addEventListener("click", () => downloadFile(file));
                    }
                    
                    // Add file info
                    const fileInfo = document.createElement("div");
                    fileInfo.classList.add("file-info");
                    fileInfo.innerHTML = `${file.name}<br>${formatFileSize(file.size)}`;
                    previewDiv.appendChild(fileInfo);

                    const deleteBtn = document.createElement("button");
                    deleteBtn.textContent = "X";
                    deleteBtn.classList.add("delete-btn");
                    deleteBtn.title = 'Delete';

                    deleteBtn.addEventListener("click", (e) => {
                        e.stopPropagation(); // Prevents triggering full-screen preview on click

                        const fileUUID = previewDiv.getAttribute("data-uuid");
                        if (fileUUID) {
                            deleteFileFromServer(fileUUID, previewDiv);
                        } else {
                            previewDiv.remove(); // Directly remove if not uploaded
                            uploadedFiles = uploadedFiles.filter(f => f !== file);
                        }
                    });

                    previewDiv.appendChild(deleteBtn);
                    uploadedFiles.push(file); // Add file to uploaded list

                    sendToServer(file, previewDiv, parentLoader);
                };
                reader.readAsDataURL(file);
            }

            function deleteFileFromServer(uuid, previewDiv) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                fetch(`/api/v1/media/${uuid}`, {
                        method: "DELETE",
                        headers: {
                            "X-CSRF-TOKEN": csrfToken,
                            "Content-Type": "application/json"
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            previewDiv.remove(); // Remove preview only if delete is successful
                            uploadedFiles = uploadedFiles.filter(f => f.uuid !== uuid);
                            loadIpInfo(); // Update IP info
                            showMessage('#fileSuccessMessage', 'File deleted successfully!');
                        } else {
                            showMessage('#fileErrorMessage', 'Failed to delete file.', true);
                        }
                    })
                    .catch(error => {
                        console.error("Delete Failed:", error);
                        showMessage('#fileErrorMessage', 'Failed to delete file.', true);
                    });
            }


            function sendToServer(file, previewDiv, parentLoader) {
                const formData = new FormData();
                formData.append("file", file);

                // CSRF token get karne ke liye meta tag se value le rahe hain
                const csrfToken = $('meta[name="csrf-token"]').attr('content');

                $.ajax({
                    url: "{{ route('share.store.media') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        "X-CSRF-TOKEN": csrfToken // CSRF token header mein send kar rahe hain
                    },
                    xhr: function() {
                        const xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener("progress", function(evt) {
                            if (evt.lengthComputable) {
                                const percentComplete = (evt.loaded / evt.total) * 100;
                                progressFill.style.width = percentComplete + '%';
                            }
                        }, false);
                        return xhr;
                    },
                    success: function(data) {
                        parentLoader.remove();
                        isUploading = false;
                        uploadProgress.style.display = 'none';
                        progressFill.style.width = '0%';
                        
                        if (data?.uuid) {
                            previewDiv.setAttribute("data-uuid", data.uuid);
                            uploadedFiles.push(data?.uuid); // Add file to uploaded list
                        }
                        
                        loadIpInfo(); // Update IP info
                        showMessage('#fileSuccessMessage', 'File uploaded successfully!');
                    },
                    error: function(xhr, status, error) {
                        parentLoader.remove();
                        isUploading = false;
                        uploadProgress.style.display = 'none';
                        progressFill.style.width = '0%';

                        let errorMessage = "Something went wrong!";
                        if (xhr.status === 404) {
                            errorMessage = "Error 404: File not found!";
                        } else if (xhr.status === 429) {
                            errorMessage = "Error 429: Too many requests! Please slow down.";
                        } else if (xhr.status === 500) {
                            errorMessage = "Error 500: Internal server error! Please try again later.";
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else {
                            errorMessage = `Error ${xhr.status}: ${xhr.responseText}`;
                        }

                        showMessage('#fileErrorMessage', errorMessage, true);
                        console.error("Upload Failed:", errorMessage);
                    }
                });
            }

            function formatFileSize(bytes) {
                if (bytes >= 1073741824) {
                    return (bytes / 1073741824).toFixed(2) + ' GB';
                } else if (bytes >= 1048576) {
                    return (bytes / 1048576).toFixed(2) + ' MB';
                } else if (bytes >= 1024) {
                    return (bytes / 1024).toFixed(2) + ' KB';
                } else {
                    return bytes + ' bytes';
                }
            }

            function openFullScreenPreview(imageSrc) {
                const previewImage = document.getElementById("previewImage");
                const fullScreenPreview = document.getElementById("fullScreenPreview");
                const downloadPreview = document.getElementById("downloadPreview");

                previewImage.src = imageSrc;
                downloadPreview.href = imageSrc; // Set download link
                downloadPreview.download = "downloaded-image"; // Default name

                fullScreenPreview.classList.add("show");
            }

            closePreview.addEventListener("click", () => {
                fullScreenPreview.classList.remove("show");
            });

            function downloadFile(file) {
                const url = URL.createObjectURL(file);
                const a = document.createElement("a");
                a.href = url;
                a.download = file.name;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }
        });
    </script>


@endsection
