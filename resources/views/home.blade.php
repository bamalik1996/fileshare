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

        /* .cta-button {
                                                                                                                                                                                                                                                        background-color: #00AEEF;
                                                                                                                                                                                                                                                        color: white;
                                                                                                                                                                                                                                                        padding: 12px 25px;
                                                                                                                                                                                                                                                        font-size: 16px;
                                                                                                                                                                                                                                                        border-radius: 6px;
                                                                                                                                                                                                                                                        text-align: center;
                                                                                                                                                                                                                                                        display: inline-block;
                                                                                                                                                                                                                                                        position: relative;
                                                                                                                                                                                                                                                    }

                                                                                                                                                                                                                                                    .cta-button:hover {
                                                                                                                                                                                                                                                        background-color: #007BB5;
                                                                                                                                                                                                                                                    } */


        /* CSS */

        /* CSS */
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
        }

        .cta-button:hover {
            background-color: #1b3fab;
            color: #fff;
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

        dev:not(.file-preview) .loader {
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
        }

        .file-upload:hover {
            background: #eef5ff;
            border-color: #0056b3;
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
            cursor: pointer
        }

        .file-preview:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Image Preview */
        .file-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
            border-radius: 10px;
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

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
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

        .upgrade-text {
            margin-top: 15px;
            padding: 10px;
            background: #ffe5e5;
            color: #b30000;
            text-align: center;
            font-weight: bold;
            display: none;
            border-radius: 5px;
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
        }

        div#text-tab div#loader {
            display: none;
            position: absolute;
        }
    </style>

    <h1 class="title is-2 has-text-centered has-text-primary">Welcome to AirForShare</h1>

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
                        oninput="checkText()"></textarea>
                    <div id="linksContainer" style="margin-top: 10px;"></div>
                </div>
            </div>
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
                <span>🚀 Drag and drop files here, or click to browse</span>
                <input type="file" id="fileInput" class="file-input" multiple accept="image/*,.pdf,.docx,.txt" hidden>
            </div>

            <!-- File Previews -->
            <div class="file-preview-container" id="filePreviewContainer"></div>

            <!-- File Size & Upgrade Text -->
            <div class="upgrade-text has-text-centered">
                <p>Files up to <strong>2 files, 5MB each</strong></p>
                <p><a href="/upgrade" class="cta-button">Upgrade to get more space</a></p>
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


        function fetchText() {
            $.ajax({
                url: '{{ route('share.get.text') }}',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#textInput').text(data.text);
                    checkText();
                    detectLinks();
                    if (data && data.text && data.text.trim().length > 0) {
                        showCopyButton(); // Agar pehle se text hai to copy button show karein

                    }
                },
                error: function() {
                    alert('Error fetching the text.');
                }
            });
        }

        function saveText() {
            var text = $('#textInput').val();

            $('#loader').show();
            $('#loadingText').show();
            $('#saveTextBtn').hide();

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
                    alert('Error occurred while saving the text.');
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
                        container.append('<p>No media files uploaded.</p>');
                        return;
                    }


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
                                `<a href="${file.original_url}" target="_blank">📄</a>`
                            );
                        }

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
                },
                error: function() {
                    alert('Failed to delete file.');
                }
            });
        }



        function copyText() {
            var text = $('#textInput').val();

            if (navigator.clipboard && navigator.clipboard.writeText) {
                // Modern browsers
                navigator.clipboard.writeText(text)
                    .then(function() {
                        //alert('Copied to clipboard!');
                        jQuery('#saveBtn').addClass('copied')
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


        function checkText() {
            var text = $('#textInput').val();
            if (text.length > 0) {
                $('.clear-button-container').show();
            } else {
                $('.clear-button-container').hide();
            }
            showSaveButton(); // Jaise hi text change ho, Save button wapas aajaye
        }

        function clearText() {
            $('#textInput').val('');
            $('.clear-button-container').hide();
            saveText();
        }

        $('#textInput').on('input', function() {
            showSaveButton();
            detectLinks(); // Call function when user types
        });

        $('#saveBtn').on('click', saveText);

        document.addEventListener("DOMContentLoaded", function() {
            const dropZone = document.getElementById("dropZone");
            const fileInput = document.getElementById("fileInput");
            const previewContainer = document.getElementById("filePreviewContainer");
            const fullScreenPreview = document.getElementById("fullScreenPreview");
            const previewImage = document.getElementById("previewImage");
            const closePreview = document.getElementById("closePreview");

            const maxFiles = 50;
            const maxFileSize = 5 * 1024 * 1024; // 5MB
            let uploadedFiles = []; // Store uploaded files

            dropZone.addEventListener("click", () => fileInput.click());

            dropZone.addEventListener("dragover", (e) => {
                e.preventDefault();
                dropZone.style.borderColor = "#0056b3";
            });

            dropZone.addEventListener("dragleave", () => {
                dropZone.style.borderColor = "#007bff";
            });

            dropZone.addEventListener("drop", (e) => {
                e.preventDefault();
                dropZone.style.borderColor = "#007bff";
                handleFiles(e.dataTransfer.files);
            });

            fileInput.addEventListener("change", (e) => {
                handleFiles(e.target.files);
            });

            function handleFiles(files) {
                if (uploadedFiles.length + files.length > maxFiles) {
                    alert(`You can only upload up to ${maxFiles} files.`);
                    return;
                }

                [...files].forEach((file) => {
                    if (file.size > maxFileSize) {
                        alert(`${file.name} exceeds the 5MB limit.`);
                        return;
                    }
                    uploadFile(file);
                });
            }

            function uploadFile(file) {
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
                        previewDiv.appendChild(fileIcon);
                        previewDiv.title = `Download: ${file.name}`; // 🏷 Tooltip
                        previewDiv.addEventListener("click", () => downloadFile(file));
                    }

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
                        } else {
                            alert("Failed to delete file.");
                        }
                    })
                    .catch(error => {
                        console.error("Delete Failed:", error);
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
                    success: function(data) {
                        parentLoader.remove();
                        if (data?.uuid) {
                            previewDiv.setAttribute("data-uuid", data.uuid);
                            uploadedFiles.push(data?.uuid); // Add file to uploaded list
                        }
                        console.log("uploadedFiles", uploadedFiles);
                    },
                    error: function(xhr, status, error) {
                        parentLoader.remove();

                        let errorMessage = "Something went wrong!";
                        if (xhr.status === 404) {
                            errorMessage = "Error 404: File not found!";
                        } else if (xhr.status === 429) {
                            errorMessage = "Error 429: Too many requests! Please slow down.";
                        } else if (xhr.status === 500) {
                            errorMessage = "Error 500: Internal server error! Please try again later.";
                        } else {
                            errorMessage = `Error ${xhr.status}: ${xhr.responseText}`;
                        }

                        // Show alert with error message
                        alert(errorMessage);
                        console.error("Upload Failed:", errorMessage);
                    }
                });
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
