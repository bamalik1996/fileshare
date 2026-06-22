# Requirements Document

## Introduction

This document specifies a bundle of twenty enhancements for **AirToShareA**, an existing Laravel 12 IP-based text and file sharing application. The application currently supports anonymous text sharing, media uploads (25 MB per file, 20 files per IP, 24-hour expiry), email delivery of files, ZIP downloads, and one-time download links, all without user accounts.

The bundle adds usability features (QR codes, password protection, custom expiry, dark mode, copy-to-clipboard, inline preview), collaboration features (room codes, drag-and-drop with progress, chunked/resumable uploads, clipboard sync, expiry notifications, rich text editor, larger uploads), and platform features (real-time sync via Reverb, end-to-end encryption, optional user accounts, public gallery, public API, PWA, virus scanning).

The following items are explicitly out of scope: multi-language/i18n support, admin analytics dashboards, and third-party cloud storage integration (S3, Google Drive, Dropbox).

## Glossary

- **AirToShareA**: The overall Laravel 12 application providing IP-based text and file sharing.
- **Share**: A logical container holding shared text and/or media files belonging to a single owner (an IP address, a Room, or an Account).
- **Owner**: The principal that created a Share. The owner is identified by IP address by default, by Room Code when a Room is used, or by Account ID when the user is logged in.
- **IP Owner**: An owner identified solely by IP address (the existing default mode).
- **Room**: A short-code-based session that lets devices on different networks join the same Share.
- **Room Code**: A 6-character alphanumeric code that identifies a Room.
- **Account**: An optional registered user with email, password, and persistent identifier.
- **Guest**: Any user who is not logged in to an Account.
- **Active file**: A file that has been successfully uploaded, not deleted, and not expired.
- **QR_Generator**: The component that produces QR code images for Share URLs.
- **Password_Manager**: The component that hashes, stores, and verifies Share passwords.
- **Expiry_Manager**: The component that records and enforces expiry timestamps on Shares and media.
- **Theme_Manager**: The client-side component that switches between light and dark themes.
- **Clipboard_Component**: The client-side component that copies text into the operating system clipboard.
- **Preview_Renderer**: The client-side component that renders inline previews of supported file types.
- **Room_Service**: The server-side component that creates Rooms, validates Room Codes, and authorises room membership.
- **Upload_Manager**: The client-side component that handles drag-and-drop selection, multi-file queueing, and progress reporting.
- **Chunked_Upload_Service**: The server-side component that accepts file chunks, tracks upload sessions, and assembles completed uploads.
- **Clipboard_Sync_Service**: The component that broadcasts and synchronises clipboard text between devices in the same Room.
- **Notification_Service**: The component that sends pre-expiry reminders by browser notification or email.
- **Rich_Text_Editor**: The client-side editor that lets users compose formatted text using Markdown syntax.
- **Realtime_Broadcaster**: The Laravel Reverb-based WebSocket service that pushes Share updates to subscribed clients.
- **Encryption_Module**: The client-side component that encrypts and decrypts Share content in the browser.
- **Account_Service**: The server-side component that registers, authenticates, and manages Accounts.
- **Public_Gallery**: The component that exposes opt-in public Shares through a shareable link.
- **API_Service**: The HTTP JSON API that exposes Share operations to external programs.
- **API_Key**: A long random token that authenticates an Account against the API_Service.
- **PWA_Module**: The Progressive Web App layer (manifest and service worker) that makes AirToShareA installable.
- **Virus_Scanner**: The component that scans uploaded files for malware before they become available for download.

## Requirements

### Requirement 1: QR Code Sharing

**User Story:** As a desktop user, I want a QR code for any Share, so that mobile devices can scan it and open the Share without typing a URL.

#### Acceptance Criteria

1. WHEN a Share is created or opened, THE QR_Generator SHALL produce a QR code image that encodes the Share's HTTPS URL within 2 seconds of the request.
2. THE QR_Generator SHALL render QR codes at a minimum size of 200 by 200 pixels.
3. IF the Share URL exceeds 256 characters, THEN THE QR_Generator SHALL still produce a QR code that, when scanned by a conformant QR decoder, decodes to the exact Share URL byte-for-byte.
4. WHEN a user clicks a QR code that has been rendered to the page, THE AirToShareA SHALL offer the QR code image as a PNG download.
5. IF QR generation fails, THEN THE AirToShareA SHALL display the Share URL as fallback text and SHALL display an error message instead of offering a download.
6. IF QR generation fails, THEN THE AirToShareA SHALL log the failure with the Share identifier and the error reason.

### Requirement 2: Password-Protected Shares

**User Story:** As an Owner, I want to set a password on a Share, so that only people who know the password can read the text or download the files.

#### Acceptance Criteria

1. WHEN an Owner saves a Share with a password between 6 and 128 characters in length, THE Password_Manager SHALL store a bcrypt hash of the password and SHALL NOT store the plaintext password in the database, application logs, or any other persistent storage.
2. IF an Owner saves a Share with a password whose length is less than 6 characters or more than 128 characters, THEN THE Password_Manager SHALL reject the save and THE AirToShareA SHALL display an error indicating the password length requirement.
3. WHEN a request retrieves a Share that has a stored password hash without supplying a password, THE AirToShareA SHALL return HTTP status 401 and SHALL NOT return the Share's text content, media URLs, or media file data.
4. WHEN a request retrieves a Share that has no stored password hash, THE AirToShareA SHALL return the Share content without prompting for or requiring a password.
5. WHEN a request submits a password whose bcrypt hash matches the stored hash, THE Password_Manager SHALL grant access and THE AirToShareA SHALL return the Share content.
6. IF a request submits a password whose bcrypt verification does not match the stored hash, THEN THE AirToShareA SHALL return HTTP status 401 with an error message that does not disclose whether the failure was due to an incorrect password or a non-existent Share.
7. IF five or more password verification failures for the same Share originate from the same IP within a rolling 15-minute window, THEN THE AirToShareA SHALL block further password verification attempts for that Share from that IP for 15 minutes by returning HTTP status 401 without performing bcrypt verification.
8. WHEN an Owner removes the password from a Share, THE Password_Manager SHALL delete the stored hash as part of the same save operation, and THE AirToShareA SHALL serve the Share without password challenges for every request received after the save operation completes.

### Requirement 3: Custom Expiry Time

**User Story:** As an Owner, I want to choose how long a Share lasts, so that short-lived data expires quickly and longer projects stay available.

#### Acceptance Criteria

1. WHEN an Owner creates or updates a Share, THE AirToShareA SHALL accept exactly one of the following expiry options: 1 hour, 6 hours, 24 hours, or 7 days, computed relative to the time the create or update is processed.
2. WHEN no expiry option is supplied on a create or update request, THE Expiry_Manager SHALL default the expiry to 24 hours computed relative to the time the request is processed.
3. THE Expiry_Manager SHALL store the absolute expiry timestamp on the Share record in UTC with at least second-level precision.
4. WHEN a request reads a Share whose expiry timestamp is at or before the current time, THE Expiry_Manager SHALL treat the Share as expired and THE AirToShareA SHALL return HTTP status 404, regardless of any other validation issues in the same request.
5. IF a create or update request supplies an expiry option that is not in the allowed set, THEN THE AirToShareA SHALL return HTTP status 422 with a validation error AND SHALL NOT modify any existing Share record nor create a new Share record.
6. THE Expiry_Manager SHALL run a scheduled cleanup at least once every 1 hour.
7. WHEN scheduled cleanup runs, THE Expiry_Manager SHALL delete Share records and associated media whose expiry timestamp is more than 1 hour before the current time.
8. WHEN any request reads a Share whose expiry timestamp is at or before the current time, THE Expiry_Manager SHALL delete the expired Share and its associated media as a fallback to the scheduled cleanup before the response is returned.

### Requirement 4: Dark Mode Toggle

**User Story:** As any user, I want to switch between light and dark themes, so that I can use AirToShareA comfortably in different lighting.

#### Acceptance Criteria

1. THE Theme_Manager SHALL provide a toggle control on every page that switches the active theme between "light" and "dark".
2. WHEN a user activates the toggle control, THE Theme_Manager SHALL switch the active theme to the opposite value within 100 milliseconds.
3. WHEN the page first loads and `localStorage` does not contain the key `airtoshare_theme`, THE Theme_Manager SHALL apply the theme indicated by the browser's `prefers-color-scheme` media query.
4. WHEN the page first loads and `localStorage` does not contain the key `airtoshare_theme` and `prefers-color-scheme` returns `no-preference` or is unavailable, THE Theme_Manager SHALL apply the light theme as the default.
5. WHEN a user selects a theme using the toggle, THE Theme_Manager SHALL persist the selected theme as the string `"light"` or `"dark"` in `localStorage` under the key `airtoshare_theme`.
6. WHEN any page loads and `localStorage` contains a value of `"light"` or `"dark"` under the key `airtoshare_theme`, THE Theme_Manager SHALL apply that preference before the page becomes visible to the user, with a maximum delay of 200 milliseconds before visibility.
7. IF reading or writing `localStorage` fails for any reason, THEN THE Theme_Manager SHALL fall back to the `prefers-color-scheme` value or the light theme default and SHALL NOT block page rendering.
8. IF `localStorage` contains a value other than `"light"` or `"dark"` under the key `airtoshare_theme`, THEN THE Theme_Manager SHALL ignore the stored value, apply the `prefers-color-scheme` default, and overwrite the stored value with the applied theme.
9. WHILE the dark theme is active, THE Theme_Manager SHALL ensure that all text smaller than 18-point regular or 14-point bold has a contrast ratio of at least 4.5:1 against its background, and that all text at or above those sizes has a contrast ratio of at least 3:1, in compliance with WCAG 2.1 AA.
10. IF an automated contrast check determines that the dark theme fails the contrast ratios in criterion 9 for any text element, THEN THE Theme_Manager SHALL disable the dark theme option in the toggle and SHALL keep the light theme active.

### Requirement 5: Copy-to-Clipboard for Shared Text

**User Story:** As a user viewing shared text, I want a one-click copy button, so that I can paste the text into another application without manually selecting it.

#### Acceptance Criteria

1. IF a Share contains text of at least 1 character, THEN THE AirToShareA SHALL display a "Copy" button next to the text panel.
2. WHEN the user activates the "Copy" button by mouse click or by pressing Enter or Space while the button has keyboard focus, THE Clipboard_Component SHALL write the full text content of the Share to the operating system clipboard using the Clipboard API.
3. WHILE a copy operation is in progress, THE Clipboard_Component SHALL disable the "Copy" button and SHALL ignore any further activation attempts on it.
4. WHEN the clipboard write succeeds, THE Clipboard_Component SHALL display a confirmation indicator for between 2 and 5 seconds inclusive and SHALL remove the indicator after that duration elapses.
5. IF the Clipboard API is unavailable or the user has not granted clipboard permission, THEN THE Clipboard_Component SHALL fall back to selecting the text in a hidden textarea and using `document.execCommand('copy')`.
6. IF both the Clipboard API path and the `document.execCommand('copy')` fallback fail, THEN THE Clipboard_Component SHALL display an error message instructing the user to copy manually, and SHALL keep that error message visible until the user dismisses it or navigates away from the page.

### Requirement 6: Inline File Preview

**User Story:** As a user receiving a Share, I want to preview images, PDFs, and videos inline, so that I can decide whether to download them.

#### Acceptance Criteria

1. WHEN a Share contains a file with a MIME type that starts with `image/` and whose file size is at most 25 MB, THE Preview_Renderer SHALL display the file inline using an `<img>` element within 5 seconds of the file row entering the viewport.
2. WHEN a Share contains a file with MIME type `application/pdf` and whose file size is at most 25 MB, THE Preview_Renderer SHALL display the file inline using a PDF viewer that exposes user-accessible controls for navigating to the next page, the previous page, and a specific page number.
3. WHEN a Share contains a file with a MIME type that starts with `video/` and whose file size is at most 200 MB, THE Preview_Renderer SHALL display the file inline using an HTML5 `<video>` element with visible play, pause, seek, and volume controls, and SHALL render the first video frame on screen within 5 seconds before treating the preview as displayed.
4. WHEN a Share contains a file whose MIME type does not match the categories in criteria 1-3, or whose file size exceeds the size limit defined for its category, THE Preview_Renderer SHALL display a generic file icon and the file name without inline preview.
5. WHILE a preview is displayed, THE AirToShareA SHALL also display a "Download" button that retrieves the original file.
6. THE Preview_Renderer SHALL lazy-load preview content so that preview content is requested only after the corresponding file row enters the viewport, and SHALL release the loaded preview content from memory once the row has been outside the viewport for at least 5 seconds.
7. IF preview content fails to load within 10 seconds or the load returns an error response, THEN THE Preview_Renderer SHALL replace the inline preview area with an error indicator showing the file name and a retry control, and SHALL keep the "Download" button available for that file.

### Requirement 7: Room Codes for Cross-Network Sharing

**User Story:** As a user, I want to share content across different networks using a 6-character code, so that recipients on other IPs can join my Share without me sending a long URL.

#### Acceptance Criteria

1. WHEN a user requests a new Room, THE Room_Service SHALL generate a 6-character Room Code drawn from the alphabet `A-Z` and digits `2-9`, excluding the characters `O`, `I`, `0`, and `1`.
2. WHEN a generated Room Code collides with an existing non-expired Room Code, THE Room_Service SHALL retry generation up to 5 times before returning an error indicating that a Room Code could not be allocated.
3. WHEN a user submits a 6-character string that matches the format defined in criterion 1, THE Room_Service SHALL look up the Room using a case-insensitive match against existing Room Codes and SHALL grant access to the Room's Share if the Room exists and is not expired.
4. IF a user submits a Room Code that does not match the format in criterion 1, that does not exist after case-insensitive lookup, or that has expired, THEN THE Room_Service SHALL return an error indicating the Room was not found and SHALL NOT modify any Room state.
5. WHEN a Room is created, THE Room_Service SHALL apply the same expiry options as Requirement 3 to the Room.
6. WHEN every device that previously joined a Room has either explicitly disconnected or has been inactive for at least 60 seconds, AND the Room's expiry timestamp is at or before the current time, THE Room_Service SHALL delete the Room record and any associated Share content.
7. WHERE a Room is also password-protected, WHEN a user submits a valid Room Code, THE Room_Service SHALL require the Room password before granting access, applying the rules of Requirement 2.
8. IF 10 or more invalid Room Code submissions originate from the same IP within a rolling 60-second window, THEN THE Room_Service SHALL reject further Room Code submissions from that IP for the next 5 minutes by returning an error indicating rate-limited access without performing Room Code lookup.

### Requirement 8: Drag-and-Drop Upload with Progress

**User Story:** As an Owner uploading files, I want to drag multiple files onto the page and see real-time progress, so that I know each file's upload status.

#### Acceptance Criteria

1. THE Upload_Manager SHALL accept files dropped onto a designated drop zone on the upload page.
2. WHILE the user drags one or more files over the drop zone, THE Upload_Manager SHALL display a visual indicator on the drop zone that distinguishes it from its idle state.
3. WHEN files are dropped, THE Upload_Manager SHALL queue every dropped file for upload up to the per-IP file count limit defined in Requirement 13.
4. IF dropped files would exceed the per-IP file count limit, THEN THE Upload_Manager SHALL queue files in drop order up to the remaining capacity, SHALL reject the rest, and SHALL display an error indicating how many files were rejected and the reason.
5. WHILE a file is uploading, THE Upload_Manager SHALL display a per-file progress bar that updates at least once per 250 milliseconds.
6. WHILE a file is uploading, THE Upload_Manager SHALL display the percentage complete, the bytes uploaded, and the total bytes for that file.
7. WHEN a file upload completes successfully, THE Upload_Manager SHALL display a success indicator next to that file's row within 1 second of completion.
8. IF a file upload fails, THEN THE Upload_Manager SHALL display an error message next to that file's row identifying the failure reason and SHALL provide a "Retry" button for that file.
9. WHEN a user activates the "Retry" button on a failed file row, THE Upload_Manager SHALL re-attempt the upload of that file, allowing up to 3 retry attempts per failed file before disabling the "Retry" button for that file.
10. WHEN all queued uploads have completed (successfully or as terminal failures), THE Upload_Manager SHALL display the total number of successful uploads and the total number of failed uploads.

### Requirement 9: Chunked and Resumable Uploads

**User Story:** As an Owner uploading large files, I want uploads to resume after a network interruption, so that I do not have to restart from zero.

#### Acceptance Criteria

1. IF a file's size is greater than 5 megabytes and at most 5,000 megabytes, THEN THE Upload_Manager SHALL split the file into chunks of at most 5 megabytes each before transmission.
2. THE Chunked_Upload_Service SHALL accept chunks identified by a session identifier of 16 to 64 characters, a chunk index between 0 and the total chunk count minus 1, and a total chunk count between 1 and 1,000.
3. WHEN the Chunked_Upload_Service receives a chunk whose metadata is valid and whose server-computed hash matches the client-supplied hash, THE Chunked_Upload_Service SHALL persist the chunk to disk and SHALL record the chunk index against the session within 5 seconds.
4. WHEN the Chunked_Upload_Service has received chunks at every index from 0 through the total chunk count minus 1 for a session, THE Chunked_Upload_Service SHALL assemble the chunks in ascending index order into the final file and SHALL register the file with the Spatie Media Library.
5. WHEN the Upload_Manager queries the status of an existing, non-expired session by its session identifier, THE Chunked_Upload_Service SHALL respond within 2 seconds with the list of chunk indexes already received.
6. WHEN resuming an upload, THE Upload_Manager SHALL transmit only chunks whose indexes are not present in the list returned by criterion 5.
7. IF a received chunk fails an integrity check (server-computed hash differs from client-supplied hash), THEN THE Chunked_Upload_Service SHALL discard the chunk, SHALL preserve all previously persisted chunks for the same session, and SHALL return an error response indicating the integrity failure.
8. IF an upload session has not received its final chunk within 24 hours of receiving its first chunk, THEN THE Chunked_Upload_Service SHALL delete all stored chunks for that session and SHALL delete the session metadata.
9. IF a request references a session identifier that does not exist or has been expired or completed, THEN THE Chunked_Upload_Service SHALL return an error response indicating the session is not found and SHALL NOT create a new session as a side effect.
10. IF a chunk request is missing required metadata, contains an out-of-range chunk index, or references a total chunk count that does not match the session's recorded total, THEN THE Chunked_Upload_Service SHALL reject the request with an error response indicating the metadata problem and SHALL NOT persist the chunk.

### Requirement 10: Clipboard Sync Across Devices

**User Story:** As a user with multiple devices in the same Room, I want clipboard text to sync automatically, so that I can paste between devices without manual transfer.

#### Acceptance Criteria

1. WHEN a device joins a Room, THE Clipboard_Sync_Service SHALL subscribe that device to the Room's clipboard channel within 2 seconds of the join completing.
2. WHEN a device updates the shared clipboard text in a Room, THE Clipboard_Sync_Service SHALL broadcast the new text to every other subscribed device in that Room within 2 seconds of receiving the update.
3. WHEN a device receives a broadcast clipboard update, THE Clipboard_Sync_Service SHALL replace the displayed clipboard text in that device's UI within 1 second of receipt without requiring user interaction.
4. WHERE the Room is password-protected, THE Clipboard_Sync_Service SHALL deliver clipboard updates only to devices whose current session has passed password verification, and SHALL exclude all other devices from receiving the update.
5. WHEN a device leaves a Room, voluntarily or due to disconnection lasting more than 30 seconds, THE Clipboard_Sync_Service SHALL unsubscribe that device from the Room's clipboard channel and stop delivering further updates to it.
6. IF a clipboard update submission contains more than 500,000 characters, THEN THE Clipboard_Sync_Service SHALL reject the update, retain the previously synchronised text unchanged, and return an error response indicating the size limit has been exceeded.
7. IF two or more devices submit clipboard updates for the same Room within the same 2 second broadcast window, THEN THE Clipboard_Sync_Service SHALL apply a last-write-wins policy based on server receipt timestamp and broadcast only the winning update to all subscribed devices.
8. IF broadcasting a clipboard update to a subscribed device fails, THEN THE Clipboard_Sync_Service SHALL retry delivery up to 3 times at 1 second intervals before marking that device as out of sync for the affected update.

### Requirement 11: Pre-Expiry Notifications

**User Story:** As an Owner, I want a reminder before my Share expires, so that I can extend it or download a copy in time.

#### Acceptance Criteria

1. WHERE an Owner has opted in to expiry notifications and granted browser notification permission, THE Notification_Service SHALL deliver a browser notification within a window of 60 minutes ± 60 seconds before the Share's expiry timestamp.
2. WHERE an Owner has opted in to email notifications and supplied an email address, THE Notification_Service SHALL send an email reminder within a window of 60 minutes ± 60 seconds before the Share's expiry timestamp, independently of whether browser notification delivery succeeds.
3. THE Notification_Service SHALL send at most one reminder per Share per channel per expiry cycle, where an expiry cycle begins when a Share's expiry timestamp is set or changed and ends when that expiry timestamp is reached or the Share is deleted.
4. WHEN a Share's expiry timestamp is changed to a value more than 60 minutes after the current time, THE Notification_Service SHALL begin a new expiry cycle for the Share and SHALL re-arm the reminder for the new expiry on every channel for which the Owner has opted in.
5. WHEN a Share's expiry timestamp is changed to a value within the next 60 minutes from the current time, THE Notification_Service SHALL begin a new expiry cycle for the Share and SHALL deliver the reminder on every opted-in channel within 60 seconds.
6. IF a notification delivery fails on a given channel, THEN THE Notification_Service SHALL log the failure, SHALL retry that channel exactly once between 5 and 6 minutes after the failure, and SHALL NOT block deliveries on the other channel.
7. IF the retry attempt described in criterion 6 also fails, THEN THE Notification_Service SHALL log the second failure, SHALL NOT make further retry attempts on that channel for that expiry cycle, and SHALL count the channel's reminder as sent for the purposes of the once-per-cycle rule in criterion 3.
8. WHEN a Share is deleted before its expiry, THE Notification_Service SHALL cancel any pending reminders for that Share within 60 seconds of the deletion.

### Requirement 12: Markdown Rich Text Editor

**User Story:** As an Owner sharing text, I want to format it with bold, lists, headings, and code blocks, so that recipients see structured content.

#### Acceptance Criteria

1. THE Rich_Text_Editor SHALL accept input in CommonMark Markdown syntax.
2. THE Rich_Text_Editor SHALL provide toolbar controls for bold, italic, headings (H1 through H3), unordered list, ordered list, inline code, and fenced code block.
3. WHEN the user clicks a toolbar control with a non-empty current selection, THE Rich_Text_Editor SHALL wrap the selected text with the corresponding Markdown syntax for that control.
4. WHEN the user clicks a toolbar control with no current selection, THE Rich_Text_Editor SHALL insert the corresponding Markdown syntax at the cursor position and place the cursor between the inserted markers.
5. WHILE the user is typing in the editor, THE Rich_Text_Editor SHALL update the live preview within 200 milliseconds of the last keystroke for Markdown sources up to 50,000 characters, and within 1,000 milliseconds of the last keystroke for Markdown sources between 50,001 and 500,000 characters.
6. WHEN a Share with Markdown content is viewed, THE AirToShareA SHALL render the Markdown to HTML on the server using a CommonMark library.
7. THE AirToShareA SHALL sanitise the rendered HTML to remove `<script>`, `<iframe>`, `<object>`, `<embed>`, and inline event handler attributes before sending it to the client.
8. THE Rich_Text_Editor SHALL enforce a maximum of 500,000 characters of Markdown source per Share.
9. WHEN the user pastes formatted text from another source, THE Rich_Text_Editor SHALL convert the supported formatting (bold, italic, headings H1 through H3, ordered lists, unordered lists, inline code, and fenced code blocks) into equivalent CommonMark Markdown, and SHALL strip unsupported formatting while preserving the underlying text content.
10. IF the user attempts to type or paste content that would cause the Markdown source to exceed 500,000 characters, THEN THE Rich_Text_Editor SHALL reject input beyond the limit, preserve the existing content within the limit, and display an error message indicating that the maximum length has been reached.
11. IF the server fails to render the Markdown to HTML, THEN THE AirToShareA SHALL display an error message indicating that the content could not be rendered and SHALL preserve the raw Markdown source so that the Owner can edit and resubmit.

### Requirement 13: Increased File Size and Count Limits

**User Story:** As an Owner, I want to upload larger files and more of them, so that I can share substantial datasets.

#### Acceptance Criteria

1. WHEN an Owner submits a file through the Chunked_Upload_Service whose total size is between 1 byte and 500 megabytes inclusive, THE AirToShareA SHALL accept the upload subject to all other applicable limits.
2. WHEN an Owner submits a file through the legacy single-request upload endpoint whose total size is between 1 byte and 25 megabytes inclusive, THE AirToShareA SHALL accept the upload subject to all other applicable limits.
3. THE AirToShareA SHALL allow each Owner up to 50 active files at any one time.
4. IF an upload would cause the Owner to exceed the 50 active files limit, THEN THE AirToShareA SHALL reject the upload with an error response indicating the active files limit, SHALL NOT persist the file, and SHALL leave the Owner's existing active files unchanged.
5. IF an upload through the Chunked_Upload_Service has a declared total size greater than 500 megabytes, THEN THE AirToShareA SHALL reject the upload with an error response indicating the chunked size limit and SHALL discard any chunks already received for that upload session.
6. IF an upload through the legacy single-request upload endpoint has a size greater than 25 megabytes, THEN THE AirToShareA SHALL reject the upload with an error response indicating the legacy size limit and SHALL NOT persist the file.
7. WHEN the upload page is loaded, THE AirToShareA SHALL display the configured per-upload size limits for both the chunked and the legacy endpoints, and the active files limit.
8. WHERE the Owner is logged in to an Account, THE Account_Service SHALL apply the per-Account limits defined in Requirement 16 instead of the per-IP limits in this requirement.

### Requirement 14: Real-Time Share Updates via WebSockets

**User Story:** As a recipient viewing a Share, I want updates to appear without refreshing, so that I see new files and text as soon as the Owner adds them.

#### Acceptance Criteria

1. WHEN a client opens a Share view, THE AirToShareA SHALL subscribe the client to the Share's broadcast channel via Laravel Reverb within 5 seconds; if subscription does not complete within 5 seconds the AirToShareA SHALL display a non-blocking indicator that real-time updates are unavailable and SHALL continue to render the Share.
2. WHEN media is added to a Share, THE Realtime_Broadcaster SHALL broadcast a `media.added` event with the new media's metadata to the Share's channel within 2 seconds of the media being persisted.
3. WHEN media is deleted from a Share, THE Realtime_Broadcaster SHALL broadcast a `media.deleted` event identifying the deleted media's UUID to the Share's channel within 2 seconds of the deletion being persisted.
4. WHEN the text content of a Share is updated, THE Realtime_Broadcaster SHALL broadcast a `text.updated` event containing the new text length (a non-negative integer between 0 and 500,000) to the Share's channel within 2 seconds of the update being persisted.
5. WHEN a client receives a `media.added`, `media.deleted`, or `text.updated` event for the Share it has open, THE client SHALL update the displayed Share within 1 second of receipt without performing a full page reload, by appending the added media, removing the deleted media, or refreshing the displayed text length respectively.
6. WHERE the Share is password-protected, THE Realtime_Broadcaster SHALL authorise channel subscription only for clients whose current session has passed password verification.
7. WHERE the Share is password-protected, THE Realtime_Broadcaster SHALL drop any broadcast destined for a client that is not currently authenticated against the Share's password, even if a subscription was previously established.
8. IF the WebSocket connection drops, THEN THE AirToShareA SHALL attempt to reconnect with exponential backoff starting at 1 second and capped at 30 seconds, for at most 10 attempts, after which the AirToShareA SHALL display a user-visible indicator that real-time updates are unavailable.
9. WHEN reconnection succeeds after a drop, THE AirToShareA SHALL request the current Share state from the server and SHALL replace the displayed media list and text length with that current state, ensuring events missed during disconnection are reconciled.

### Requirement 15: End-to-End Encryption

**User Story:** As a privacy-conscious Owner, I want my Share content to be encrypted in the browser, so that the server cannot read it.

#### Acceptance Criteria

1. WHERE the Owner enables end-to-end encryption for a Share, THE Encryption_Module SHALL generate a 256-bit AES-GCM key and a 96-bit initialisation vector in the browser using a cryptographically secure random source, SHALL place the key into the URL fragment of the Share's link, and SHALL NOT transmit the key in any HTTP request body, query string, or header.
2. WHEN the Encryption_Module submits an encrypted payload to the server, THE AirToShareA SHALL store only the ciphertext, the 96-bit initialisation vector, and the 128-bit authentication tag, and SHALL reject any submission that includes a key field.
3. WHEN a recipient opens a Share with end-to-end encryption, THE Encryption_Module SHALL retrieve the encryption key from the URL fragment (the portion after `#`), SHALL decrypt the ciphertext in the browser, and SHALL NOT transmit the URL fragment to the server in any subsequent request.
4. THE AirToShareA SHALL NOT log, store, or otherwise persist any URL fragment received from clients in application logs, databases, request traces, or analytics records.
5. WHEN a file is uploaded with end-to-end encryption enabled, THE Encryption_Module SHALL encrypt the file in the browser before chunked upload, and THE Chunked_Upload_Service SHALL store the resulting ciphertext verbatim without parsing, decoding, or transcoding it.
6. IF decryption fails because of an incorrect key, a corrupted ciphertext, or a failed authentication tag verification, THEN THE Encryption_Module SHALL display a decryption error, SHALL discard any partial plaintext from the failed decryption attempt from memory, and SHALL NOT render, download, or otherwise expose any portion of the partial plaintext to the user.
7. WHERE end-to-end encryption is enabled for a Share, THE Preview_Renderer SHALL decrypt media in the browser before rendering previews, SHALL NOT request server-side previews, and SHALL NOT request server-side thumbnails or transcodes for that Share's media.
8. WHERE end-to-end encryption is enabled for a Share, THE Virus_Scanner SHALL be skipped for that Share's media, and THE AirToShareA SHALL display a notice to the Owner at enablement time and to recipients before download stating that the media has not been scanned for malware because end-to-end encryption is enabled.
9. IF a recipient opens a Share that has end-to-end encryption enabled and the URL has no fragment, has an empty fragment, or has a fragment that is not a valid 256-bit AES-GCM key, THEN THE Encryption_Module SHALL display an error indicating that the decryption key is missing or invalid and SHALL NOT request the ciphertext from the server.

### Requirement 16: Optional User Accounts

**User Story:** As a frequent user, I want an optional Account, so that I keep a history of my Shares and get higher limits.

#### Acceptance Criteria

1. THE Account_Service SHALL allow registration with a syntactically valid email address and a password between 8 and 128 characters in length.
2. THE Account_Service SHALL store passwords using bcrypt with a cost factor of at least 12.
3. IF registration is attempted with an email address already associated with an existing Account, THEN THE Account_Service SHALL reject the registration and display an error indicating the email address is already in use.
4. WHEN a user submits credentials that match a registered Account, THE Account_Service SHALL create an authenticated session and THE AirToShareA SHALL associate every Share created during that session with the Account in addition to the IP address.
5. IF a user submits login credentials that do not match any registered Account or the password does not match the stored hash, THEN THE Account_Service SHALL reject the login attempt, display an error indicating invalid credentials, and SHALL NOT create an authenticated session.
6. WHILE a user is logged in, THE AirToShareA SHALL display a "My Shares" page that lists every Share owned by the Account in reverse chronological order of creation, including Shares whose expiry date occurred within the previous 30 days.
7. WHILE a user is logged in, THE Account_Service SHALL allow the user to mark up to 50 Shares as favourites, and favourite Shares SHALL NOT be subject to auto-expiry while the favourite mark remains.
8. IF a logged-in user attempts to mark a Share as favourite while 50 favourites already exist on the Account, THEN THE Account_Service SHALL reject the action and display an error indicating the favourites limit of 50 has been reached.
9. WHERE a Share's Owner is an Account, THE AirToShareA SHALL enforce per-Account limits of 100 active files, 1 gigabyte total storage, and a maximum selectable expiry option of 30 days, regardless of whether the Account user is currently logged in.
10. IF a logged-in user attempts to create a Share that would cause the Account to exceed 100 active files or 1 gigabyte total storage, THEN THE AirToShareA SHALL reject the creation and display an error indicating which per-Account limit was exceeded.
11. WHEN a user submits an Account deletion request and confirms the request, THE Account_Service SHALL delete the Account record and all associated Shares, including favourites, within 24 hours of confirmation.
12. WHEN a user logs out, THE Account_Service SHALL invalidate the authenticated session within 5 seconds and THE AirToShareA SHALL revert to IP-based ownership for any subsequently created Shares.
13. THE AirToShareA SHALL continue to support the existing IP-based Guest flow for users who do not log in, with no reduction in functionality available to Guest users prior to introduction of Accounts.

### Requirement 17: Public Share Gallery

**User Story:** As an Owner, I want to opt in to making a Share public, so that anyone with the link can view it like a public WeTransfer link.

#### Acceptance Criteria

1. WHEN an Owner marks a Share as public, THE Public_Gallery SHALL generate a public slug consisting of 12 characters drawn from the URL-safe alphabet `A-Z`, `a-z`, `0-9`, `-`, and `_`, retrying generation up to 5 times on collision with an existing slug before returning an error.
2. WHEN a GET request arrives at the route `/p/{slug}` and the slug matches a public Share that is not also password-protected, THE Public_Gallery SHALL serve the Share content in the response within 3 seconds.
3. IF a Share is both public and password-protected, THEN THE Public_Gallery SHALL apply the password rules of Requirement 2 before serving the content for any request to that Share's `/p/{slug}` route.
4. THE Public_Gallery SHALL NOT include any public Share in the home page, blog index, sitemap, search results, or any other index page rendered by AirToShareA, and access SHALL require the exact 12-character slug.
5. WHEN the Public_Gallery serves a successful (HTTP 2xx) GET response for a public Share at `/p/{slug}`, THE Public_Gallery SHALL increment that Share's public-view counter by exactly one.
6. WHEN an Owner removes the public flag from a Share, THE Public_Gallery SHALL invalidate the public slug, and THE AirToShareA SHALL return HTTP status 404 for the previous slug starting at most 60 seconds after the removal regardless of whether internal invalidation is fully propagated.
7. WHILE a request is being handled by the `/p/{slug}` route, THE Public_Gallery SHALL set the response header `X-Robots-Tag: noindex, nofollow` on every response (including error responses).
8. IF a request to `/p/{slug}` references a slug that does not exist, has been invalidated, or belongs to a Share whose public flag is currently disabled, THEN THE Public_Gallery SHALL return HTTP status 404 with a body that does not disclose whether the slug ever existed.

### Requirement 18: Public REST API with API Keys

**User Story:** As a developer, I want a documented API and API keys, so that I can integrate AirToShareA with my own programs.

#### Acceptance Criteria

1. WHILE a user is logged in to an Account, THE API_Service SHALL allow the user to hold up to 5 unrevoked API_Keys at any one time per Account.
2. IF a logged-in user attempts to create an additional API_Key while the Account already holds 5 unrevoked API_Keys, THEN THE API_Service SHALL reject the creation with an error response indicating the per-Account limit of 5 has been reached.
3. WHEN an API_Key is generated, THE API_Service SHALL produce a key string of at least 32 characters drawn from a cryptographically secure random source, SHALL display the API_Key value to the user exactly once at the time of generation, and SHALL store only its bcrypt hash thereafter.
4. THE API_Service SHALL accept the API_Key in the `Authorization: Bearer <key>` request header on routes prefixed with `/api/v2`.
5. WHEN a request supplies an API_Key whose plaintext value bcrypt-verifies against a stored unrevoked API_Key hash, THE API_Service SHALL act on behalf of the API_Key's owning Account, with operations limited to the read, write, and delete actions on Shares, media, and other resources owned by that Account.
6. IF a request to `/api/v2/*` supplies no API_Key, supplies a malformed `Authorization` header, supplies an unrecognised key, or supplies a revoked key, THEN THE API_Service SHALL return HTTP status 401.
7. THE API_Service SHALL NOT validate API_Keys on routes outside `/api/v2/*` and SHALL NOT return HTTP status 401 on those routes for an invalid API_Key.
8. THE API_Service SHALL expose endpoints for creating Shares, retrieving Shares, uploading media via the legacy single-request endpoint, uploading media via the chunked endpoint, deleting media, and listing the Account's active Shares (where active means not deleted and not expired).
9. THE API_Service SHALL return JSON responses with a top-level `status` field whose value is either `success` or `error`.
10. THE API_Service SHALL rate-limit each API_Key to at most 60 requests in any rolling 60-second window, returning HTTP status 429 when the limit is exceeded.
11. THE API_Service SHALL NOT use HTTP status 429 for any condition other than rate-limit violations defined in criterion 10.
12. WHEN an Account user revokes an API_Key, THE API_Service SHALL reject subsequent requests authenticated with that key with HTTP status 401 within 60 seconds.
13. THE API_Service SHALL publish a public reference document covering authentication, the endpoints listed in criterion 8, the rate-limit rules, and the response shape from criterion 9, accessible to any visitor without authentication.

### Requirement 19: Progressive Web App Support

**User Story:** As a mobile user, I want to install AirToShareA as an app and use it offline, so that it feels like a native app.

#### Acceptance Criteria

1. THE PWA_Module SHALL serve a Web App Manifest at `/manifest.webmanifest` that declares the application name, short name, theme colour, background colour, display mode `standalone`, start URL `/`, and at least two PNG icons of sizes 192x192 and 512x512.
2. WHEN any page of AirToShareA finishes its initial document load, THE PWA_Module SHALL register a service worker located at `/sw.js` with the scope `/` within 5 seconds.
3. WHEN the service worker installs successfully, THE PWA_Module SHALL pre-cache the application shell consisting of the home page HTML, the Bulma CSS, the application JavaScript, the manifest, and the icon assets within 30 seconds of installation completing.
4. IF service worker installation fails, THEN THE PWA_Module SHALL NOT pre-cache the application shell and SHALL NOT display a blocking error to the user; AirToShareA SHALL remain fully usable as a non-PWA web application in this case.
5. WHILE the device is offline, THE PWA_Module SHALL serve the cached application shell for any URL whose path is part of the pre-cached shell, returning the cached response so the user reaches a usable offline page rather than a network error.
6. WHEN the device transitions from online to offline, THE PWA_Module SHALL display a banner within 2 seconds of detecting the transition that informs the user that uploads and Share retrieval require a network connection.
7. WHEN the device transitions from offline back to online, THE PWA_Module SHALL hide the offline banner within 2 seconds of detecting the transition.
8. WHEN the application shell version changes and a newly downloaded service worker activates, THE PWA_Module SHALL replace the cached shell atomically so that no mix of old and new shell assets is served to the user.
9. WHEN the cached shell has been replaced as described in criterion 8, THE PWA_Module SHALL display a prompt asking the user to reload the page; WHEN the user accepts the prompt, the PWA_Module SHALL reload the page within 2 seconds.
10. THE PWA_Module SHALL NOT cache HTTP responses whose URL path matches a Share content endpoint (such as `/api/v1/text`, `/api/v1/media`, `/api/v2/shares`, `/p/{slug}`, or `/download/{uuid}`), regardless of HTTP status.

### Requirement 20: Virus and Malware Scanning

**User Story:** As an Owner and as a recipient, I want uploaded files scanned for malware, so that I am protected from infected downloads.

#### Acceptance Criteria

1. WHEN a file upload completes, THE Virus_Scanner SHALL queue the file for scanning within 5 seconds of upload completion before the file is made available for download.
2. WHILE a file's scan status is `pending`, THE AirToShareA SHALL display the file's status as "Scanning…" and SHALL return HTTP status 425 (`Too Early`) for download requests of that file.
3. WHEN a file's scan completes with a clean result, THE Virus_Scanner SHALL set the file's scan status to `clean` and THE AirToShareA SHALL allow downloads of that file.
4. WHEN a file's scan completes with a malware detection, THE Virus_Scanner SHALL set the file's scan status to `infected`, THE AirToShareA SHALL block downloads of that file with HTTP status 451, and THE Virus_Scanner SHALL delete the file from storage within 5 minutes.
5. WHEN a file's scan completes with a malware detection, THE Virus_Scanner SHALL notify the Owner of the affected Share that the file was detected as infected and removed, using the same opted-in channels (browser notification or email) defined in Requirement 11 if the Owner has opted in, otherwise displaying the notice on the Owner's Share view within 60 seconds of the detection.
6. WHERE ClamAV is configured as the scan backend, THE Virus_Scanner SHALL invoke `clamdscan` against the file's path and SHALL interpret a non-zero exit code as `infected`.
7. WHERE VirusTotal is configured as the scan backend, THE Virus_Scanner SHALL submit the file's SHA-256 hash to the VirusTotal API and SHALL interpret two or more positive engine results as `infected`.
8. IF the configured scan backend returns a transient error (network timeout, HTTP 5xx, or local invocation failure), THEN THE Virus_Scanner SHALL retry the scan up to 3 times with at least 30 seconds between retries before treating the scan as failed for the purposes of criterion 9.
9. IF a scan does not produce a `clean` or `infected` result within 5 minutes of being queued, including any retries described in criterion 8, THEN THE Virus_Scanner SHALL set the file's scan status to `error` and THE AirToShareA SHALL block downloads of that file with HTTP status 503 until a manual review is performed by an administrator who flips the file's scan status to `clean` or `infected` through an administrative interface.
10. THE Virus_Scanner SHALL log the scan result, the backend used, the retry count, and the file UUID for every scan.
11. WHERE end-to-end encryption is enabled for a Share (Requirement 15), THE Virus_Scanner SHALL skip the file and THE AirToShareA SHALL display a notice on every Share view and download confirmation page for that Share stating that the file is end-to-end encrypted and has not been scanned for malware.
