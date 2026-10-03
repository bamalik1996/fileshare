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
    | Allowed Upload Types (expanded allowlist)
    |--------------------------------------------------------------------------
    |
    | Option B: broad common document / media / code / archive types.
    | Executable installers (.exe, .msi, .bat, .cmd, .ps1, .dll, .scr, .com,
    | .apk, …) are intentionally omitted — they are not accepted.
    |
    | A file passes when its MIME matches mime_patterns OR its extension is
    | listed in extensions (covers browsers that send application/octet-stream).
    |
    */

    'upload_types' => [
        'label' => 'Images, Office, PDF, Text/code, Archives, Audio, Video',

        // HTML <input accept="…"> value (comma-separated).
        'accept' => implode(',', [
            'image/*',
            'video/*',
            'audio/*',
            '.pdf',
            '.doc', '.docx', '.odt',
            '.xls', '.xlsx', '.csv', '.ods',
            '.ppt', '.pptx', '.odp',
            '.txt', '.md', '.rtf',
            '.json', '.xml', '.yaml', '.yml',
            '.html', '.htm', '.css', '.js', '.ts', '.tsx', '.jsx',
            '.php', '.py', '.java', '.go', '.rs', '.c', '.cpp', '.h', '.cs', '.rb', '.swift', '.kt', '.sql',
            '.zip', '.rar', '.7z', '.tar', '.gz', '.tgz',
            '.epub',
        ]),

        'mime_patterns' => [
            'image/*',
            'video/*',
            'audio/*',
            'text/*',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/vnd.oasis.opendocument.presentation',
            'application/rtf',
            'application/json',
            'application/xml',
            'application/javascript',
            'application/typescript',
            'application/x-yaml',
            'application/yaml',
            'application/zip',
            'application/x-zip-compressed',
            'application/x-rar-compressed',
            'application/vnd.rar',
            'application/x-7z-compressed',
            'application/x-tar',
            'application/gzip',
            'application/x-gzip',
            'application/epub+zip',
            'application/csv',
            'text/csv',
            'text/markdown',
            'text/x-markdown',
        ],

        'extensions' => [
            // Images / media (wildcard mimes usually cover these; listed for octet-stream)
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico', 'heic', 'heif', 'avif',
            'mp4', 'webm', 'mov', 'mkv', 'avi', 'm4v',
            'mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac',
            // Documents / Office
            'pdf', 'doc', 'docx', 'odt', 'rtf',
            'xls', 'xlsx', 'csv', 'ods',
            'ppt', 'pptx', 'odp',
            'epub',
            // Text / code
            'txt', 'md', 'markdown', 'log',
            'json', 'xml', 'yaml', 'yml', 'toml', 'ini', 'env',
            'html', 'htm', 'css', 'scss', 'less',
            'js', 'mjs', 'cjs', 'ts', 'tsx', 'jsx',
            'php', 'py', 'java', 'go', 'rs', 'c', 'cpp', 'h', 'hpp', 'cs', 'rb', 'swift', 'kt', 'kts',
            'sql',
            // Archives
            'zip', 'rar', '7z', 'tar', 'gz', 'tgz', 'bz2',
            // Intentionally omitted (not allowed): exe, msi, bat, cmd, ps1, dll, scr, com, apk, sh
        ],
    ],

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

    // Free-tier limits (SaaS features, Phase 6). Intentionally generous while
    // we grow the user base - no billing gates these yet. The ceilings are
    // resolved centrally by App\Services\PlanService so a future paid plan can
    // override them per-Account without touching call sites.
    'active_files_limit_ip'      => (int) env('AIRTOSHARE_ACTIVE_FILES_LIMIT_IP', 100),
    'active_files_limit_account' => (int) env('AIRTOSHARE_ACTIVE_FILES_LIMIT_ACCOUNT', 500),
    'account_storage_limit_bytes' => (int) env('AIRTOSHARE_ACCOUNT_STORAGE_LIMIT_BYTES', 10 * 1024 * 1024 * 1024),

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
