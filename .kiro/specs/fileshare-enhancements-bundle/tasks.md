# Implementation Plan: Fileshare Enhancements Bundle

## Overview

This plan implements the twenty enhancements layered onto the existing AirToShareA Laravel 12 app. The work is broken into 19 epics that move from data-layer foundations to per-feature services and finally to integration. Each task references one or more granular acceptance criteria, and every Correctness Property in the design is mapped to a property-based test task placed close to the implementation it validates.

The implementation language is **PHP 8.2 + Laravel 12** for the server, **vanilla JavaScript (ES2022)** for the browser, with **Vitest** for JS tests and **PHPUnit + eris** for PHP property tests, exactly as specified in the design's Testing Strategy.

## Tasks

- [x] 1. Set up shared infrastructure and configuration
  - [x] 1.1 Add core Composer and npm dependencies
    - Composer: `bacon/bacon-qr-code`, `league/commonmark`, `laravel/reverb`, `giorgiosironi/eris`, `khanamiryan/qrcode-detector-decoder`
    - npm (devDependencies): `vitest`, `jsdom`, `fast-check`, `@playwright/test`, `axe-core`, `marked`, `turndown`
    - Pin all versions in `composer.json` and `package.json`
    - _Requirements: 1.1, 12.6, 14.1, 18.13_
  - [x] 1.2 Create `config/airtoshare.php`
    - Define keys: `legacy_upload_max_bytes` (25 MB), `chunked_upload_max_bytes` (500 MB), `active_files_limit_ip` (50), `active_files_limit_account` (100), `account_storage_limit_bytes` (1 GB), `account_max_expiry_option` (`30d`), `password_verify_rate_limit`, `room_code_rate_limit`, `notification_window_seconds`, `scan_backend`, `scan_timeout_seconds`
    - Wire into `config/app.php` providers array
    - _Requirements: 13.1, 13.2, 13.3, 13.7, 16.9, 11.1, 20.6, 20.7_
  - [x] 1.3 Set up Vitest, Playwright, and eris harnesses
    - Add `vitest.config.ts` (jsdom env)
    - Add `playwright.config.ts`
    - Add `tests/Support/Generators.php` (placeholder generators class)
    - Add `resources/js/__tests__/generators.ts` (placeholder generators)
    - Update `phpunit.xml` to include the new `tests/Unit` and `tests/Feature` directories explicitly
    - _Requirements: 1.1, 2.1, 9.1_

- [x] 2. Create the share aggregate and supporting tables
  - [x] 2.1 Write migration `create_shares_table`
    - Columns per design data model: `id`, `uuid`, `owner_type`, `owner_id`, `text_content`, `markdown_source`, `password_hash`, `expires_at`, `public_slug`, `public_view_count`, `is_e2ee`, `is_favourite`, timestamps
    - Indexes on `(owner_type, owner_id, expires_at)`, `(public_slug)`, `(expires_at)`
    - _Requirements: 2.1, 3.3, 16.4, 17.1_
  - [x] 2.2 Write migrations for `rooms`, `accounts`, `account_favourites`, `api_keys`, `upload_sessions`, `upload_chunks`, `media_scans`, `share_notifications`
    - Match exactly the column lists in `design.md` "Data Models"
    - Add composite unique constraints where called out (`(account_id, share_id)`, `(session_id, chunk_index)`, `(share_id, cycle_expires_at, channel)`)
    - _Requirements: 7.1, 16.1, 18.1, 9.2, 11.3, 20.1_
  - [x] 2.3 Implement the `Share` Eloquent model
    - Polymorphic `owner()` morphTo (ip, room, account)
    - Spatie HasMedia integration (mirrors existing `MediaFile`)
    - Cast `expires_at` to `datetime`, `is_e2ee` and `is_favourite` to `bool`
    - `scopeActive()`, `scopeOwnedBy(Principal $p)`, `isExpired()`
    - _Requirements: 2.1, 3.3, 7.5, 16.4_
  - [x] 2.4 Implement supporting models: `Room`, `Account` (Authenticatable), `ApiKey`, `UploadSession`, `UploadChunk`, `MediaScan`, `ShareNotification`
    - Wire relationships to `Share`
    - Override `Account::getAuthPasswordName()` (uses `password_hash`)
    - _Requirements: 7.1, 16.1, 18.1, 9.2, 20.1_
  - [x] 2.5 Define principal resolution in a `ResolvePrincipal` middleware
    - Returns `IpPrincipal`, `AccountPrincipal`, or `ApiKeyPrincipal`
    - Bound into the global middleware stack so every request has `$request->principal`
    - _Requirements: 16.4, 16.13, 18.5_
  - [x]* 2.6 Write unit tests for Share scopes and principal resolution
    - Verify `Share::scopeActive` excludes expired
    - Verify `ResolvePrincipal` returns IP for guests, Account for sessions, ApiKey for `/api/v2/*`
    - _Requirements: 2.1, 16.4, 16.13, 18.5_

- [x] 3. Implement Expiry Manager and scheduled cleanup
  - [x] 3.1 Implement `App\Services\ExpiryManager`
    - `parseOption(?string $opt, Principal $p): Carbon` (supports `1h`, `6h`, `24h`, `7d`, plus `30d` for accounts)
    - `enforceOnRead(Share $s)` deletes + throws `ShareExpiredException`
    - Form Request rule helper for the allowed option set
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.8, 7.5, 16.9_
  - [-]* 3.2 Write property test for expiry option parsing
    - **Property 7: Expiry option parsing**
    - **Validates: Requirements 3.1, 3.3, 7.5, 16.9**
  - [-]* 3.3 Write property test for invalid expiry rejection
    - **Property 8: Invalid expiry option rejection is side-effect free**
    - **Validates: Requirements 3.5**
  - [x] 3.4 Implement `ShareCleanupExpired` artisan command
    - Deletes shares whose `expires_at < now()->subHour()` AND not favourited
    - Cascades media via Spatie `clearMediaCollection`
    - Registered hourly in `Console\Kernel`
    - _Requirements: 3.6, 3.7, 16.7_
  - [-]* 3.5 Write property test for expired-share read behaviour
    - **Property 9: Expired share read returns 404 and deletes record**
    - **Validates: Requirements 3.4, 3.8**
  - [-]* 3.6 Write property test for cleanup grace window
    - **Property 10: Cleanup deletes shares older than the grace window**
    - **Validates: Requirements 3.6, 3.7**

- [x] 4. Implement Password Manager and protected-share gate
  - [x] 4.1 Implement `App\Services\PasswordManager`
    - `hash`, `verify`, `validate` (length 6..128); throws `ValidationException` on invalid length
    - Logger processor that strips `password` keys from log context
    - _Requirements: 2.1, 2.2, 2.8_
  - [-]* 4.2 Write property test for password hashing round trip
    - **Property 2: Password hashing round trip with no plaintext leak**
    - **Validates: Requirements 2.1, 2.5**
  - [ ]* 4.3 Write property test for password length validation
    - **Property 3: Password length validation**
    - **Validates: Requirements 2.2**
  - [x] 4.4 Implement `SharePasswordGate` middleware and `PasswordVerifyRateLimit` middleware
    - Gate consults `session('share_pw_ok')[$shareId]`; returns 401 with non-disclosing body otherwise
    - Rate-limit middleware uses `RateLimiter::for('share-pw')` keyed by `ip|share_id`; 5 failures in 15 min ⇒ 15 min block, returns 401 without invoking bcrypt
    - _Requirements: 2.3, 2.4, 2.6, 2.7_
  - [ ]* 4.5 Write feature property test for protected-share access
    - **Property 4: Protected-share access gate**
    - **Validates: Requirements 2.3, 2.4**
  - [ ]* 4.6 Write feature property test for password verification rate limit
    - **Property 5: Password verification rate limit**
    - **Validates: Requirements 2.7**
  - [ ]* 4.7 Write feature property test for password removal flips access
    - **Property 6: Password removal flips access**
    - **Validates: Requirements 2.8**

- [x] 5. Refactor existing endpoints onto Share aggregate (`ShareService`)
  - [x] 5.1 Implement `App\Services\ShareService`
    - `createForPrincipal(Principal, array $payload): Share`
    - `update(Share, array $payload): Share` (handles password add/remove, expiry change, markdown updates)
    - `loadShare(string $uuidOrSlug): Share` (calls `ExpiryManager::enforceOnRead` first)
    - `canAddFile(Share, int $sizeBytes): bool` (consults per-principal limits from `config/airtoshare.php`)
    - _Requirements: 3.4, 3.8, 13.3, 13.4, 13.8, 16.4_
  - [x] 5.2 Refactor `ShareController` to delegate to `ShareService`
    - Keep guest IP flow byte-for-byte compatible
    - Adapter writes legacy `shared_texts` rows for one release cycle
    - _Requirements: 16.13_
  - [x] 5.3 Refactor `MediaController` to use `ShareService::canAddFile` for all add/delete paths
    - Read `media_scans.status` on download and apply the mapping in design Section 20
    - _Requirements: 13.3, 13.4, 20.2, 20.3, 20.4, 20.9_
  - [ ]* 5.4 Write property test for guest IP feature parity
    - **Property 57: Guest IP feature parity**
    - **Validates: Requirements 16.13**
  - [ ]* 5.5 Write property test for upload size boundary enforcement
    - **Property 41: Upload size boundary enforcement**
    - **Validates: Requirements 13.1, 13.2, 13.5, 13.6**
  - [ ]* 5.6 Write property test for per-owner active-file and storage limits
    - **Property 42: Per-owner active-file and storage limits**
    - **Validates: Requirements 13.3, 13.4, 13.8, 16.9, 16.10**

- [x] 6. Checkpoint - Foundation complete
  - Ensure all tests pass, ask the user if questions arise.

- [x] 7. Implement QR Code generation
  - [x] 7.1 Implement `App\Services\QrGenerator`
    - `generate(string $url): string` returns ≥ 200x200 PNG bytes
    - `generateOrFail(string $url): string` throws `QrGenerationException`; caller logs `share_id` + reason
    - _Requirements: 1.1, 1.2, 1.5, 1.6_
  - [x] 7.2 Implement `QrCodeController@show`
    - Route `GET /qr/{slug}`; supports `?download=1` for `Content-Disposition: attachment`
    - On generation failure renders fallback (URL text + error banner) without offering download
    - _Requirements: 1.4, 1.5_
  - [x] 7.3 Wire QR slot into the share view template
    - `<img src="/qr/{slug}">` plus click-to-download handler and `error` listener for fallback rendering
    - _Requirements: 1.4, 1.5_
  - [ ]* 7.4 Write property test for QR round trip and minimum size
    - **Property 1: QR code round trip and minimum size**
    - **Validates: Requirements 1.1, 1.2, 1.3**

- [x] 8. Implement Theme Manager (frontend)
  - [x] 8.1 Add inline pre-paint bootstrap script in the layout `<head>`
    - Reads `localStorage["airtoshare_theme"]`, falls back to `prefers-color-scheme`, then to light
    - Self-heals invalid stored values
    - _Requirements: 4.3, 4.4, 4.5, 4.6, 4.7, 4.8_
  - [x] 8.2 Implement `public/assets/js/theme-manager.js`
    - Toggle binding, persistence to `localStorage`, runtime contrast self-check that disables dark mode when WCAG AA fails
    - _Requirements: 4.1, 4.2, 4.5, 4.9, 4.10_
  - [x] 8.3 Add `[data-theme="dark"]` overrides in `public/assets/css/custom.css`
    - All body, link, and form-field colours hit WCAG AA ratios per design
    - Rebuild `custom.min.css`
    - _Requirements: 4.9_
  - [ ]* 8.4 Write JS property test for theme persistence and self-healing
    - **Property 11: Theme preference is round-tripped through localStorage and self-heals invalid values**
    - **Validates: Requirements 4.5, 4.8**
  - [ ]* 8.5 Write Playwright + axe-core test for dark theme contrast
    - **Property 12: Dark theme contrast invariant**
    - **Validates: Requirements 4.9**

- [x] 9. Implement Copy-to-Clipboard component
  - [x] 9.1 Implement `public/assets/js/clipboard.js`
    - Tri-strategy chain: `navigator.clipboard.writeText` → hidden textarea + `execCommand` → persistent error banner
    - Disable button while in flight; show 2-5s confirm indicator
    - _Requirements: 5.2, 5.3, 5.4, 5.5, 5.6_
  - [x] 9.2 Render the Copy button conditionally in the share view
    - Server-rendered: button present iff `text_content` length ≥ 1
    - _Requirements: 5.1_
  - [ ]* 9.3 Write JS property test for Copy button visibility
    - **Property 13: Copy button visibility is text-driven**
    - **Validates: Requirements 5.1**

- [x] 10. Implement inline Preview Renderer
  - [x] 10.1 Implement `public/assets/js/preview-renderer.js`
    - Classifier maps `(mime, size)` to `<img>` / PDF.js viewer / `<video>` / generic-icon
    - `IntersectionObserver` lazy-load + 5s out-of-view release
    - 10s load-error retry control; download button always rendered
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7_
  - [x] 10.2 Add PDF.js assets under `public/assets/pdfjs/` (or pin a CDN URL via config)
    - _Requirements: 6.2_
  - [ ]* 10.3 Write JS property test for preview classification
    - **Property 14: Preview classification**
    - **Validates: Requirements 6.4, 6.5**

- [x] 11. Checkpoint - Per-share UI features in place
  - Ensure all tests pass, ask the user if questions arise.

- [x] 12. Implement Room service and rate-limited access
  - [x] 12.1 Implement `App\Services\RoomService`
    - `create(?string $expiry, ?string $password): Room` using alphabet `ABCDEFGHJKLMNPQRSTUVWXYZ23456789`, up to 5 retries on collision
    - `findByCode(string)` case-insensitive, expiry-aware
    - `validateFormat(string): bool` regex
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.7_
  - [x] 12.2 Implement `RoomController` (`store`, `show`) and `RoomCodeRateLimit` middleware
    - Rate limit: 10 invalid submissions in 60s ⇒ 5 min block, no lookup performed
    - When room is password-protected, delegate to `SharePasswordGate`
    - _Requirements: 7.3, 7.4, 7.7, 7.8_
  - [x] 12.3 Implement `Room` cleanup branch inside `ShareCleanupExpired`
    - Deletes rooms where `expires_at <= now()` AND `last_activity_at <= now()->subSeconds(60)`
    - _Requirements: 7.6_
  - [ ]* 12.4 Write property test for room code format
    - **Property 15: Room code format**
    - **Validates: Requirements 7.1**
  - [ ]* 12.5 Write property test for case-insensitive room code lookup
    - **Property 16: Case-insensitive room code lookup**
    - **Validates: Requirements 7.3**
  - [ ]* 12.6 Write property test for invalid room submission preserves state
    - **Property 17: Invalid room submission preserves state**
    - **Validates: Requirements 7.4**
  - [ ]* 12.7 Write property test for room deletion conditions
    - **Property 18: Room deletion conditioned on expiry and inactivity**
    - **Validates: Requirements 7.6**
  - [ ]* 12.8 Write feature property test for room password gate
    - **Property 19: Room password gate**
    - **Validates: Requirements 7.7**
  - [ ]* 12.9 Write feature property test for room code rate limit
    - **Property 20: Room code rate limit**
    - **Validates: Requirements 7.8**

- [x] 13. Implement Upload Manager (drag-and-drop with progress)
  - [x] 13.1 Implement `public/assets/js/upload-manager.js`
    - Drop zone with hover indicator
    - Per-file `XMLHttpRequest`, state machine `queued → uploading → succeeded | failed | exhausted`
    - 250 ms throttled progress (percentage + bytes uploaded + total bytes)
    - Retry button up to 3 attempts then disable
    - Final summary line of `successful_count` and `failed_count`
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8, 8.9, 8.10_
  - [x] 13.2 Add `GET /api/v1/limits` endpoint consumed by the upload page
    - Returns `legacy_upload_max_bytes`, `chunked_upload_max_bytes`, `active_files_limit_*` per principal
    - _Requirements: 13.7_
  - [ ]* 13.3 Write JS property test for upload queue capacity
    - **Property 21: Upload queue capacity**
    - **Validates: Requirements 8.3, 8.4**
  - [ ]* 13.4 Write JS property test for retry counter termination
    - **Property 22: Retry counter terminates at 3**
    - **Validates: Requirements 8.9**
  - [ ]* 13.5 Write JS property test for upload summary completeness
    - **Property 23: Upload summary completeness**
    - **Validates: Requirements 8.10**

- [ ] 14. Implement Chunked Upload Service
  - [x] 14.1 Implement `App\Services\ChunkedUploadService`
    - `start`, `receiveChunk`, `status`, `assemble` per design
    - SHA-256 integrity check; chunk ≤ 5 MB; total ≤ 500 MB; total chunks 1..1000
    - Idempotent re-upload of `(session, index)` with matching hash; mismatched hash ⇒ 409 `integrity_failed`
    - Storage under `storage/app/chunks/{session_uuid}/{index}.bin`
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.7, 9.10, 13.1, 13.5_
  - [x] 14.2 Implement `ChunkedUploadController` with routes `/chunked-upload/start|chunk|status|complete`
    - Returns received-index list within 2 s for status calls
    - 422 for missing/out-of-range metadata, 404 for missing/expired/completed sessions
    - _Requirements: 9.5, 9.6, 9.9, 9.10_
  - [x] 14.3 Implement `AssembleChunkedUpload` job and `CleanupExpiredUploadSessions` job
    - Hourly cleanup deletes sessions whose first-chunk timestamp > 24 h old without completion
    - _Requirements: 9.4, 9.8_
  - [x] 14.4 Wire Upload Manager to call resumable endpoints for files > 5 MB
    - Client splits, uploads only `T - R` indexes after a status call, retries 3× per chunk
    - _Requirements: 9.1, 9.6_
  - [ ]* 14.5 Write property test for chunked upload round trip
    - **Property 24: Chunked upload round trip**
    - **Validates: Requirements 9.1, 9.4**
  - [ ]* 14.6 Write property test for chunk metadata validation
    - **Property 25: Chunk metadata validation**
    - **Validates: Requirements 9.2, 9.10**
  - [ ]* 14.7 Write property test for hash mismatch isolation
    - **Property 26: Hash mismatch isolation**
    - **Validates: Requirements 9.3, 9.7**
  - [ ]* 14.8 Write JS property test for resume transmits only missing chunks
    - **Property 27: Resume transmits only missing chunks**
    - **Validates: Requirements 9.5, 9.6**
  - [ ]* 14.9 Write property test for stale upload session cleanup
    - **Property 28: Stale upload session cleanup**
    - **Validates: Requirements 9.8**
  - [ ]* 14.10 Write feature property test for nonexistent session reference
    - **Property 29: Nonexistent session reference is side-effect free**
    - **Validates: Requirements 9.9**

- [~] 15. Checkpoint - Upload pipeline complete
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 16. Implement Markdown Rich Text Editor
  - [x] 16.1 Implement `App\Services\MarkdownRenderer`
    - CommonMark + HtmlFilter sanitiser allowlist tuned to Bulma; strips `<script>`, `<iframe>`, `<object>`, `<embed>`, `on*` attrs
    - 500,000-char source limit enforcement
    - _Requirements: 12.1, 12.6, 12.7, 12.8, 12.10, 12.11_
  - [x] 16.2 Implement `public/assets/js/rich-text-editor.js`
    - Toolbar buttons (bold/italic/H1-H3/ul/ol/inline code/fenced block) wrap selection or insert at cursor with cursor placement between markers
    - Live preview via `marked` with 200 ms / 1000 ms debounce by source size
    - Paste handler: convert `text/html` via Turndown; fall back to `text/plain`
    - Enforce 500,000-char limit on input/paste with reject + error message
    - _Requirements: 12.2, 12.3, 12.4, 12.5, 12.9, 12.10_
  - [x] 16.3 Wire markdown source storage and server-side render in `ShareService::update`
    - Persist `markdown_source`; render to `text_content` HTML on save
    - On render failure preserve source and show warning notification
    - _Requirements: 12.6, 12.11_
  - [ ]* 16.4 Write property test for CommonMark rendering and length enforcement
    - **Property 37: CommonMark rendering and length enforcement**
    - **Validates: Requirements 12.1, 12.6, 12.8, 12.10**
  - [ ]* 16.5 Write property test for sanitised HTML excludes disallowed elements
    - **Property 38: Sanitised HTML excludes disallowed elements**
    - **Validates: Requirements 12.7**
  - [ ]* 16.6 Write JS property test for toolbar wrap and insert positions
    - **Property 39: Toolbar wrap and insert positions**
    - **Validates: Requirements 12.3, 12.4**
  - [ ]* 16.7 Write JS property test for paste preserves plaintext
    - **Property 40: Paste preserves plaintext**
    - **Validates: Requirements 12.9**

- [x] 17. Implement Notification Service
  - [x] 17.1 Implement `App\Services\NotificationService`
    - `arm(Share)` writes one row per channel keyed by `(share_id, cycle_expires_at, channel)`
    - `cancelFor(Share)` deletes pending rows
    - Hooks into `ShareService::update` to re-arm on expiry change
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.8_
  - [x] 17.2 Implement `ScheduleExpiryReminders` artisan command (every minute)
    - Selects rows with `send_at` in the next 60 s, dispatches `SendExpiryReminder`
    - _Requirements: 11.1, 11.2, 11.5_
  - [x] 17.3 Implement `SendExpiryReminder` job
    - Browser channel: Web Push for accounts; `private-ip.{ip}` Reverb broadcast for IP-only owners
    - Email channel: `Mail::to(email)->send(new ShareExpiryReminder($share))`
    - Single retry 5..6 min later; on second failure mark `sent_at`
    - _Requirements: 11.1, 11.2, 11.6, 11.7_
  - [ ]* 17.4 Write property test for reminder window and uniqueness
    - **Property 33: Notification reminder window and uniqueness**
    - **Validates: Requirements 11.1, 11.2, 11.3, 11.5**
  - [ ]* 17.5 Write property test for re-arming on expiry change
    - **Property 34: Notification re-arming on expiry change**
    - **Validates: Requirements 11.4**
  - [ ]* 17.6 Write property test for retry idempotence
    - **Property 35: Notification retry idempotence**
    - **Validates: Requirements 11.6, 11.7**
  - [ ]* 17.7 Write property test for cancellation on share deletion
    - **Property 36: Notification cancellation on share deletion**
    - **Validates: Requirements 11.8**

- [x] 18. Implement Realtime Broadcaster (Laravel Reverb)
  - [x] 18.1 Configure Laravel Reverb
    - `config/reverb.php`, `config/broadcasting.php` set to `reverb`
    - `BROADCAST_CONNECTION=reverb` in `.env.example`
    - Wire `routes/channels.php` for `private-share.{shareId}`, `presence-share.{shareId}`, `private-room.{roomId}.clipboard`
    - Channel auth checks `share_pw_ok[$id]` for password-protected shares/rooms
    - _Requirements: 14.1, 14.6, 14.7_
  - [x] 18.2 Implement events `MediaAdded`, `MediaDeleted`, `TextUpdated`
    - Implement `ShouldBroadcastNow`
    - Payloads: media UUID + metadata; deleted UUID; new `length` integer 0..500000
    - Dispatched from `ShareService` add/update/delete paths
    - _Requirements: 14.2, 14.3, 14.4_
  - [x] 18.3 Implement `BroadcastingMiddleware`
    - Drops broadcasts to subscribers whose `share_pw_ok` flag has been revoked
    - _Requirements: 14.7_
  - [x] 18.4 Implement `public/assets/js/realtime.js`
    - Echo + Reverb client subscription
    - Exponential backoff schedule `min(30, 2^k)` for k=0..9 then offline indicator
    - On reconnect: `GET /api/v1/shares/{id}/state` and replace local view
    - _Requirements: 14.1, 14.5, 14.8, 14.9_
  - [x] 18.5 Implement `GET /api/v1/shares/{id}/state` endpoint
    - Returns current media list and text length for reconnection reconciliation
    - _Requirements: 14.9_
  - [ ]* 18.6 Write property test for share event payloads
    - **Property 43: Share event payloads**
    - **Validates: Requirements 14.2, 14.3, 14.4**
  - [ ]* 18.7 Write feature property test for channel authorisation tracking
    - **Property 44: Realtime channel authorisation tracks password state**
    - **Validates: Requirements 14.6, 14.7**
  - [ ]* 18.8 Write JS property test for reconnect backoff schedule
    - **Property 45: Reconnect backoff schedule**
    - **Validates: Requirements 14.8**
  - [ ]* 18.9 Write JS property test for reconnect reconciliation
    - **Property 46: Reconnect reconciliation**
    - **Validates: Requirements 14.9**

- [x] 19. Implement Clipboard Sync Service
  - [x] 19.1 Implement `App\Services\ClipboardSyncService`
    - `update(Room, string $text)` rejects > 500,000 chars; `UPDATE ... WHERE clipboard_updated_at < :ts` enforces last-write-wins
    - Broadcasts `ClipboardUpdated` on `private-room.{id}.clipboard`
    - _Requirements: 10.2, 10.4, 10.6, 10.7_
  - [x] 19.2 Implement `public/assets/js/clipboard-sync.js` (subscriber side)
    - Subscribes within 2 s of room join; replaces displayed text within 1 s of receipt
    - _Requirements: 10.1, 10.3_
  - [x] 19.3 Implement presence-tracking job
    - Reverb presence channel `presence-room.{id}` updates `last_seen_at` per device
    - Periodic job (every 30 s) marks devices `last_seen_at < now()->subSeconds(30)` departed
    - _Requirements: 10.5_
  - [x] 19.4 Add retry logic in `ClipboardUpdated` dispatch
    - Up to 3 retries 1 s apart per device; mark as out-of-sync after exhaustion
    - _Requirements: 10.8_
  - [ ]* 19.5 Write feature property test for clipboard sync delivery scope
    - **Property 30: Clipboard sync delivery scope**
    - **Validates: Requirements 10.4, 10.5**
  - [ ]* 19.6 Write property test for clipboard size limit preserves state
    - **Property 31: Clipboard size limit preserves state**
    - **Validates: Requirements 10.6**
  - [ ]* 19.7 Write property test for last-write-wins clipboard
    - **Property 32: Last-write-wins clipboard**
    - **Validates: Requirements 10.7**

- [~] 20. Checkpoint - Realtime + collaboration features complete
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 21. Implement End-to-End Encryption
  - [~] 21.1 Implement `public/assets/js/encryption-module.js`
    - AES-GCM 256-bit key + 96-bit IV via `crypto.getRandomValues`
    - Key placed in URL fragment as base64url; never in body/query/header
    - Encrypts files in 5 MB pre-chunks before handing to upload manager
    - Decrypts on download with auth-tag verification; fragment never re-fetched
    - On any failure, immediately overwrite plaintext buffer and never render partial
    - Validates fragment is a valid 256-bit AES-GCM key before requesting ciphertext
    - _Requirements: 15.1, 15.2, 15.3, 15.5, 15.6, 15.9_
  - [~] 21.2 Server-side E2EE plumbing
    - `shares.is_e2ee` toggles E2EE mode
    - Each media row stores `iv` + `auth_tag`
    - Submission validation rejects fields named `key`, `e2ee_key`, etc.
    - Logger processor strips `#fragment` substrings from any logged URL
    - _Requirements: 15.2, 15.4_
  - [~] 21.3 Suppress server-side preview/scan for E2EE shares
    - Preview Renderer detects `is_e2ee` and uses local-decrypt + blob URLs
    - Virus scanner bound to skip and write `media_scans.status = skipped_e2ee`
    - Share view renders unscanned-media notice
    - _Requirements: 15.7, 15.8, 20.11_
  - [ ]* 21.4 Write JS property test for E2EE key never crosses the network
    - **Property 47: E2EE key never crosses the network**
    - **Validates: Requirements 15.1, 15.2, 15.3**
  - [ ]* 21.5 Write feature property test for URL fragment never persisted
    - **Property 48: URL fragment never persisted**
    - **Validates: Requirements 15.4**
  - [ ]* 21.6 Write JS property test for E2EE ciphertext round trip
    - **Property 49: E2EE ciphertext round trip**
    - **Validates: Requirements 15.5**
  - [ ]* 21.7 Write JS property test for decryption failure does not leak plaintext
    - **Property 50: E2EE decryption failure does not leak plaintext**
    - **Validates: Requirements 15.6, 15.9**
  - [ ]* 21.8 Write feature property test for E2EE skips server-side previews and scanning
    - **Property 51: E2EE skips server-side previews and scanning**
    - **Validates: Requirements 15.7, 15.8, 20.11**

- [ ] 22. Implement Account Service
  - [~] 22.1 Implement `App\Services\AccountService`
    - Register: RFC 5322 lite email + password 8..128, bcrypt cost ≥ 12, unique email
    - Login: rejects non-matching credentials with same generic error
    - Logout: invalidates session within 5 s; principal reverts to IP
    - Account deletion: soft-deletes account + cascades shares/favourites/api_keys within 24 h via queued job
    - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5, 16.11, 16.12_
  - [~] 22.2 Implement `AuthController` (`register`, `login`, `logout`) and `AccountController` (`destroy`, `shares`, `favourite`)
    - Routes `/auth/register`, `/auth/login`, `/auth/logout`, `/account`, `/account/shares`, `/account/shares/{id}/favourite`
    - My Shares page lists owner shares incl. ones expired within last 30 days, reverse-chronological
    - Favourites limited to 50 (enforced at write time); favourited shares exempted from cleanup
    - _Requirements: 16.6, 16.7, 16.8, 16.11_
  - [~] 22.3 Wire per-Account limits into `ShareService::canAddFile`
    - 100 active files, 1 GB total storage, max expiry `30d` for accounts
    - _Requirements: 16.9, 16.10_
  - [ ]* 22.4 Write feature property test for registration and credentials
    - **Property 52: Account registration and credentials**
    - **Validates: Requirements 16.1, 16.3, 16.4, 16.5**
  - [ ]* 22.5 Write property test for bcrypt cost invariant
    - **Property 53: bcrypt cost invariant**
    - **Validates: Requirements 16.2**
  - [ ]* 22.6 Write feature property test for favourites limit
    - **Property 54: Account favourites limit**
    - **Validates: Requirements 16.7, 16.8**
  - [ ]* 22.7 Write feature property test for account deletion
    - **Property 55: Account deletion deletes all owned content**
    - **Validates: Requirements 16.11**
  - [ ]* 22.8 Write feature property test for logout invalidates session
    - **Property 56: Logout invalidates session**
    - **Validates: Requirements 16.12**

- [ ] 23. Implement Public Gallery
  - [~] 23.1 Implement `App\Services\PublicGalleryService`
    - `enable(Share)` generates 12-char URL-safe slug `[A-Za-z0-9_-]`, retry up to 5 on collision
    - `disable(Share)` clears slug, increments `public_invalidation_revision`, sets cache tombstone TTL 60 s
    - _Requirements: 17.1, 17.6_
  - [~] 23.2 Implement `PublicShareController@show` at `/p/{slug}`
    - Looks up via cache-aside, applies password gate, sets `X-Robots-Tag: noindex, nofollow` on every response
    - Increments `public_view_count` exactly once per HTTP 2xx
    - 404 body byte-identical for invalidated and never-existed slugs
    - _Requirements: 17.2, 17.3, 17.5, 17.6, 17.7, 17.8_
  - [~] 23.3 Update `SitemapController` and home/blog renderers to exclude any public Share
    - _Requirements: 17.4_
  - [ ]* 23.4 Write property test for public slug format and uniqueness
    - **Property 58: Public slug format and uniqueness**
    - **Validates: Requirements 17.1**
  - [ ]* 23.5 Write feature property test for public access combines password and public flags
    - **Property 59: Public access combines password and public flags**
    - **Validates: Requirements 17.2, 17.3**
  - [ ]* 23.6 Write feature property test for public shares are not in any index
    - **Property 60: Public shares are not in any index**
    - **Validates: Requirements 17.4**
  - [ ]* 23.7 Write feature property test for public view counter
    - **Property 61: Public view counter**
    - **Validates: Requirements 17.5**
  - [ ]* 23.8 Write feature property test for robots header on every public response
    - **Property 62: Robots header on every public response**
    - **Validates: Requirements 17.7**
  - [ ]* 23.9 Write feature property test for indistinguishable 404
    - **Property 63: Indistinguishable 404 for invalidated and never-existed slugs**
    - **Validates: Requirements 17.6, 17.8**

- [~] 24. Checkpoint - Account, E2EE, public gallery complete
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 25. Implement Public REST API (v2)
  - [~] 25.1 Implement `App\Services\ApiKeyService`
    - `create(Account)` produces ≥ 32-char key; stores `key_prefix` (first 8 chars) and `Hash::make($plain)`; returns plaintext once
    - `revoke(ApiKey)`; per-Account cap 5 unrevoked
    - _Requirements: 18.1, 18.2, 18.3, 18.12_
  - [~] 25.2 Implement `ApiKeyAuth` middleware
    - Parses `Authorization: Bearer`, looks up by prefix, `Hash::check` against `key_hash`, requires `revoked_at IS NULL`
    - 401 on missing/malformed/unknown/revoked; bound only to `/api/v2/*`
    - _Requirements: 18.4, 18.5, 18.6, 18.7, 18.12_
  - [~] 25.3 Implement v2 controllers and routes
    - `POST /api/v2/shares`, `GET /api/v2/shares`, `GET /api/v2/shares/{id}`
    - `POST /api/v2/shares/{id}/media`, chunked-upload start/chunk/complete
    - `DELETE /api/v2/shares/{id}/media/{uuid}`
    - `POST /api/v2/api-keys` (account-session only), `DELETE /api/v2/api-keys/{id}`
    - JSON envelope macro `apiOk`/`apiError` with top-level `status` field
    - _Requirements: 18.8, 18.9_
  - [~] 25.4 Apply rate limiter for `/api/v2/*`
    - 60 requests / 60 s window keyed by `apikey:{id}`; 429 only on this condition
    - _Requirements: 18.10, 18.11_
  - [~] 25.5 Author and serve API reference at `/docs/api`
    - `resources/docs/api-v2.md` rendered by `DocsController@show`; no auth required
    - _Requirements: 18.13_
  - [ ]* 25.6 Write property test for API key creation limit
    - **Property 64: API key creation limit**
    - **Validates: Requirements 18.1, 18.2, 18.3**
  - [ ]* 25.7 Write feature property test for API key authentication and 401 surface
    - **Property 65: API key authentication and 401 surface**
    - **Validates: Requirements 18.4, 18.5, 18.6, 18.7**
  - [ ]* 25.8 Write feature property test for API response shape
    - **Property 66: API response shape**
    - **Validates: Requirements 18.9**
  - [ ]* 25.9 Write feature property test for API rate limit
    - **Property 67: API rate limit is 429-only**
    - **Validates: Requirements 18.10, 18.11**
  - [ ]* 25.10 Write feature property test for API key revocation propagation
    - **Property 68: API key revocation propagation**
    - **Validates: Requirements 18.12**

- [ ] 26. Implement PWA module
  - [~] 26.1 Implement `ManifestController@show` at `/manifest.webmanifest`
    - Content-Type `application/manifest+json`; `name`, `short_name`, `theme_color`, `background_color`, `display: "standalone"`, `start_url: "/"`, both icons
    - _Requirements: 19.1_
  - [~] 26.2 Implement `public/sw.js`
    - Pre-cache shell on install (`/`, Bulma + custom CSS, app JS, manifest, both icons); versioned `airtoshare-shell-v{n}`
    - Atomic activate: deletes prior `airtoshare-shell-v*` caches
    - Runtime exclusion list: `/api/v1/text`, `/api/v1/media`, `/api/v2/shares*`, `/p/*`, `/download/*` always pass through
    - _Requirements: 19.3, 19.5, 19.8, 19.10_
  - [~] 26.3 Implement client-side registration and update prompt
    - Register `/sw.js` with scope `/` within 5 s of document load; non-blocking on failure
    - `controllerchange` triggers banner with Reload button; reload within 2 s
    - _Requirements: 19.2, 19.4, 19.9_
  - [~] 26.4 Implement online/offline banner
    - 2 s debounced banner on `online`/`offline` window events
    - _Requirements: 19.6, 19.7_
  - [ ]* 26.5 Write JS property test for service worker atomic shell replacement
    - **Property 69: Service worker atomic shell replacement**
    - **Validates: Requirements 19.8**
  - [ ]* 26.6 Write JS property test for content endpoints are never cached
    - **Property 70: Content endpoints are never cached**
    - **Validates: Requirements 19.10**

- [ ] 27. Implement Virus Scanner
  - [~] 27.1 Define `App\Services\Scanning\ScanBackend` interface and concrete `ClamAvBackend`, `VirusTotalBackend`
    - ClamAV: `Process::run("clamdscan --no-summary {$path}")`; exit codes mapped clean/infected/error
    - VirusTotal: SHA-256 lookup; classify infected iff `malicious >= 2`
    - _Requirements: 20.6, 20.7_
  - [~] 27.2 Implement `App\Services\VirusScanner` orchestrator
    - Skip if share is E2EE; mark `skipped_e2ee`
    - Otherwise dispatch to backend with retry policy: up to 3 retries ≥ 30 s apart on transient errors
    - 5-min total timeout ⇒ `error`
    - On infected: dispatch `DeleteInfectedMedia` (within 5 min) + `NotificationService` notice on opted-in channels (or fall back to share-view notice within 60 s)
    - Emit log entry with media UUID, backend, retry count, classification on every completion
    - _Requirements: 20.4, 20.5, 20.8, 20.9, 20.10, 20.11_
  - [~] 27.3 Implement `ScanMediaForViruses` queued job and `DeleteInfectedMedia` job
    - Queued within 5 s of upload completion; bound to media UUID
    - _Requirements: 20.1, 20.4_
  - [~] 27.4 Implement download gate in `MediaController::download`
    - Mapping `{pending→425, clean→200, infected→451, error→503, skipped_e2ee→200}`
    - "Scanning…" UI state for `pending`
    - _Requirements: 20.2, 20.3, 20.4, 20.9_
  - [~] 27.5 Implement `/admin/scans` administrative review page
    - Gated by `accounts.is_admin` flag; allows manual flip from `error` to `clean`/`infected`
    - _Requirements: 20.9_
  - [ ]* 27.6 Write feature property test for scan-status to download-status mapping
    - **Property 71: Scan-status to download-status mapping**
    - **Validates: Requirements 20.2, 20.3, 20.4, 20.9**
  - [ ]* 27.7 Write feature property test for scan queueing on upload completion
    - **Property 72: Scan queueing on upload completion**
    - **Validates: Requirements 20.1**
  - [ ]* 27.8 Write property test for VirusTotal classification threshold
    - **Property 73: VirusTotal classification threshold**
    - **Validates: Requirements 20.7**
  - [ ]* 27.9 Write property test for transient scan failure retry policy
    - **Property 74: Transient scan failure retry policy**
    - **Validates: Requirements 20.8**
  - [ ]* 27.10 Write feature property test for owner notification on infection
    - **Property 75: Owner notification on infection**
    - **Validates: Requirements 20.5**
  - [ ]* 27.11 Write property test for scan log completeness
    - **Property 76: Scan log completeness**
    - **Validates: Requirements 20.10**

- [ ] 28. Final integration and wiring
  - [~] 28.1 Update `app/Exceptions/Handler.php`
    - JSON rendering for `Accept: application/json` or `/api/*` paths
    - Strip stack traces in production; scrub `password`, `markdown_source`, fragments, `Authorization` from log context
    - _Requirements: 2.6, 12.11, 15.4, 18.6_
  - [~] 28.2 Update `app/Console/Kernel.php` schedule
    - `ShareCleanupExpired` hourly; `CleanupExpiredUploadSessions` hourly; `ScheduleExpiryReminders` every minute; presence cleanup every 30 s
    - _Requirements: 3.6, 9.8, 10.5, 11.1_
  - [~] 28.3 Wire all new middlewares into the kernel
    - `ResolvePrincipal`, `SecurityHeaders` (existing), `IpBasedRateLimit` (existing), `SharePasswordGate`, `PasswordVerifyRateLimit`, `RoomCodeRateLimit`, `ApiKeyAuth`, `BroadcastingMiddleware`
    - _Requirements: 2.7, 7.8, 14.7, 18.4_
  - [~] 28.4 Replace existing cleanup commands with the new unified flow
    - Adapt `CleanupExpiredContent` and `CleanupExpiredMedia` to defer to `ShareCleanupExpired`; mark deprecated in inline doc
    - _Requirements: 3.6, 3.7_
  - [ ]* 28.5 Write Playwright E2E tests for the headline flows
    - Drag-drop upload, dark-theme persistence + axe contrast, real-time updates, public gallery view, PWA install
    - _Requirements: 4.6, 8.1, 14.5, 17.2, 19.1_
  - [ ]* 28.6 Extend `.github/workflows/deploy.yml` with `php-tests`, `js-tests`, `e2e-tests` jobs
    - PHPUnit, Vitest, Playwright (latter gated by label or main branch)
    - _Requirements: 18.13_

- [~] 29. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP. They are predominantly test tasks.
- Each task references specific granular requirements (e.g. `7.4`, `16.9`) for traceability rather than user-story numbers alone.
- Property test sub-tasks each cite the property number from the design's Correctness Properties section and the requirements clauses they validate, exactly once per property.
- Checkpoints (tasks 6, 11, 15, 20, 24, 29) provide regular validation gates.
- The implementation is incremental: existing IP-based guest flow remains functional throughout. Legacy `shared_texts` and `media_files` tables stay in place for one release cycle as compatibility adapters.
- Backend tasks use PHP 8.2 + Laravel 12; frontend tasks use vanilla JavaScript with Vitest + fast-check for property tests; Playwright + axe-core for E2E and accessibility.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3"] },
    { "id": 1, "tasks": ["2.1", "2.2"] },
    { "id": 2, "tasks": ["2.3", "2.4"] },
    { "id": 3, "tasks": ["2.5", "2.6", "4.1", "4.2", "4.3"] },
    { "id": 4, "tasks": ["3.1", "3.2", "3.3", "4.4"] },
    { "id": 5, "tasks": ["3.4", "3.5", "3.6", "4.5", "4.6", "4.7", "5.1"] },
    { "id": 6, "tasks": ["5.2", "5.3"] },
    { "id": 7, "tasks": ["5.4", "5.5", "5.6", "7.1", "8.1", "8.3", "9.1", "10.1", "10.2"] },
    { "id": 8, "tasks": ["7.2", "7.3", "7.4", "8.2", "8.4", "8.5", "9.2", "9.3", "10.3"] },
    { "id": 9, "tasks": ["12.1", "13.1", "14.1", "16.1"] },
    { "id": 10, "tasks": ["12.2", "12.3", "13.2", "14.2", "14.3", "16.2", "16.3"] },
    { "id": 11, "tasks": ["12.4", "12.5", "12.6", "12.7", "12.8", "12.9", "13.3", "13.4", "13.5", "14.4"] },
    { "id": 12, "tasks": ["14.5", "14.6", "14.7", "14.8", "14.9", "14.10", "16.4", "16.5", "16.6", "16.7"] },
    { "id": 13, "tasks": ["17.1", "17.2", "17.3", "18.1", "18.2"] },
    { "id": 14, "tasks": ["18.3", "18.4", "18.5", "19.1", "19.2", "19.3", "19.4"] },
    { "id": 15, "tasks": ["17.4", "17.5", "17.6", "17.7", "18.6", "18.7", "18.8", "18.9", "19.5", "19.6", "19.7"] },
    { "id": 16, "tasks": ["21.1", "21.2", "22.1", "23.1"] },
    { "id": 17, "tasks": ["21.3", "22.2", "22.3", "23.2", "23.3"] },
    { "id": 18, "tasks": ["21.4", "21.5", "21.6", "21.7", "21.8", "22.4", "22.5", "22.6", "22.7", "22.8", "23.4", "23.5", "23.6", "23.7", "23.8", "23.9"] },
    { "id": 19, "tasks": ["25.1", "25.2", "26.1", "26.2", "27.1"] },
    { "id": 20, "tasks": ["25.3", "25.4", "25.5", "26.3", "26.4", "27.2", "27.3", "27.4", "27.5"] },
    { "id": 21, "tasks": ["25.6", "25.7", "25.8", "25.9", "25.10", "26.5", "26.6", "27.6", "27.7", "27.8", "27.9", "27.10", "27.11"] },
    { "id": 22, "tasks": ["28.1", "28.2", "28.3", "28.4"] },
    { "id": 23, "tasks": ["28.5", "28.6"] }
  ]
}
```
