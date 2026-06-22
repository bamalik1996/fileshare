<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Upload Size Limits
    |--------------------------------------------------------------------------
    |
    | Per-file size ceilings for the two upload endpoints.
    |
    | - legacy_upload_max_bytes : single-request endpoint (Requirement 13.2)
    | - chunked_upload_max_bytes: chunked/resumable endpoint (Requirement 13.1)
    |
    */

    'legacy_upload_max_bytes'  => (int) env('AIRTOSHARE_LEGACY_UPLOAD_MAX_BYTES', 25 * 1024 * 1024),
    'chunked_upload_max_bytes' => (int) env('AIRTOSHARE_CHUNKED_UPLOAD_MAX_BYTES', 500 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Per-Owner Active File and Storage Limits
    |--------------------------------------------------------------------------
    |
    | Active file counts and storage caps applied per principal. IP-based
    | guests use the *_ip values (Requirement 13.3); logged-in Account owners
    | use the *_account values (Requirement 16.9).
    |
    */

    'active_files_limit_ip'      => (int) env('AIRTOSHARE_ACTIVE_FILES_LIMIT_IP', 50),
    'active_files_limit_account' => (int) env('AIRTOSHARE_ACTIVE_FILES_LIMIT_ACCOUNT', 100),
    'account_storage_limit_bytes' => (int) env('AIRTOSHARE_ACCOUNT_STORAGE_LIMIT_BYTES', 1024 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Account Expiry Ceiling
    |--------------------------------------------------------------------------
    |
    | Maximum selectable expiry option for Shares owned by an Account
    | (Requirement 16.9). Allowed values follow ExpiryManager::parseOption().
    |
    */

    'account_max_expiry_option' => env('AIRTOSHARE_ACCOUNT_MAX_EXPIRY_OPTION', '30d'),

    /*
    |--------------------------------------------------------------------------
    | Share Password Verification Rate Limit
    |--------------------------------------------------------------------------
    |
    | Brute-force protection for the password-protected Share gate
    | (Requirement 2.7). After max_attempts failures within decay_seconds
    | from the same (ip, share_id) bucket, further verifications are blocked
    | for block_seconds without invoking bcrypt.
    |
    */

    'password_verify_rate_limit' => [
        'max_attempts'  => (int) env('AIRTOSHARE_PASSWORD_VERIFY_MAX_ATTEMPTS', 5),
        'decay_seconds' => (int) env('AIRTOSHARE_PASSWORD_VERIFY_DECAY_SECONDS', 15 * 60),
        'block_seconds' => (int) env('AIRTOSHARE_PASSWORD_VERIFY_BLOCK_SECONDS', 15 * 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Room Code Submission Rate Limit
    |--------------------------------------------------------------------------
    |
    | Rate limit applied to invalid Room Code submissions per IP
    | (Requirement 7.8). After max_attempts invalid submissions inside the
    | rolling decay_seconds window, further submissions return a rate-limited
    | error for block_seconds without performing a code lookup.
    |
    */

    'room_code_rate_limit' => [
        'max_attempts'  => (int) env('AIRTOSHARE_ROOM_CODE_MAX_ATTEMPTS', 10),
        'decay_seconds' => (int) env('AIRTOSHARE_ROOM_CODE_DECAY_SECONDS', 60),
        'block_seconds' => (int) env('AIRTOSHARE_ROOM_CODE_BLOCK_SECONDS', 5 * 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pre-Expiry Notification Window
    |--------------------------------------------------------------------------
    |
    | Tolerance, in seconds, applied around the 60-minute pre-expiry reminder
    | target. Acceptance criterion 11.1/11.2 requires delivery within
    | 60 minutes ± 60 seconds.
    |
    */

    'notification_window_seconds' => (int) env('AIRTOSHARE_NOTIFICATION_WINDOW_SECONDS', 60),

    /*
    |--------------------------------------------------------------------------
    | Virus Scanner
    |--------------------------------------------------------------------------
    |
    | scan_backend         : which Virus_Scanner implementation to invoke.
    |                        Supported values: "clamav", "virustotal", "null"
    |                        (Requirements 20.6, 20.7).
    | scan_timeout_seconds : the upper time bound, in seconds, before a scan
    |                        without a conclusive result is marked as `error`
    |                        (Requirement 20.9). Default 5 minutes.
    |
    */

    'scan_backend'         => env('AIRTOSHARE_SCAN_BACKEND', 'clamav'),
    'scan_timeout_seconds' => (int) env('AIRTOSHARE_SCAN_TIMEOUT_SECONDS', 5 * 60),

    /*
    |--------------------------------------------------------------------------
    | PDF.js Viewer URL (Requirement 6.2)
    |--------------------------------------------------------------------------
    |
    | URL of the PDF.js viewer.html used by the inline Preview_Renderer to
    | display application/pdf attachments with prev/next/page-number
    | controls. Two deployment shapes are supported:
    |
    |   1. Self-hosted: drop a PDF.js distribution under
    |      public/assets/pdfjs/ and set this to "/assets/pdfjs/web/viewer.html".
    |
    |   2. Pinned CDN (default): use a versioned jsDelivr URL so the
    |      viewer asset is locked to a known release and cannot drift
    |      under us. The default below points at pdfjs-dist 4.6.82 on
    |      jsDelivr; override via AIRTOSHARE_PDFJS_VIEWER_URL when a
    |      different pinned version (or self-hosted path) is desired.
    |
    | The configured value is emitted into the layout as
    | <meta name="airtoshare-pdfjs-viewer" content="..."> and consumed by
    | public/assets/js/preview-renderer.js, which falls back to
    | "/assets/pdfjs/web/viewer.html" if the meta tag is absent.
    |
    */

    'pdfjs_viewer_url' => env(
        'AIRTOSHARE_PDFJS_VIEWER_URL',
        'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.6.82/web/viewer.html'
    ),

];
