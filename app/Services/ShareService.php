<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Principal\AccountPrincipal;
use App\Domain\Principal\IpPrincipal;
use App\Domain\Principal\Principal;
use App\Domain\Principal\RoomPrincipal;
use App\Events\TextUpdated;
use App\Models\Account;
use App\Models\MediaFile;
use App\Models\Share;
use App\Support\IpAddressMatcher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Share_Service (design.md > Components and Interfaces, "ShareService.php (NEW)").
 *
 * Single point of orchestration for the Share aggregate. Replaces the
 * direct controller/Eloquent coupling that the legacy
 * `SharedText`/`MediaFile` flow used to do, and centralises three pieces
 * of cross-cutting policy that belong above the model:
 *
 *   - Expiry parsing/enforcement, delegated to {@see ExpiryManager}.
 *   - Password add/remove, delegated to {@see PasswordManager}.
 *   - Per-owner active-file and storage limits sourced from
 *     `config/airtoshare.php` (Requirements 13.3, 13.4, 13.8, 16.9, 16.10).
 *
 * The class is deliberately payload-driven rather than DTO-driven: every
 * caller (controllers, the v2 API, tests) builds a small associative array
 * with only the keys it actually needs to set, and the service treats
 * absent keys as "leave unchanged" on update. Empty / `null` values for
 * `password` are interpreted as "clear" (Requirement 2.8); to leave the
 * password unchanged, omit the key entirely.
 *
 * Acceptance criteria covered:
 *   3.4 - reading an expired share returns 404 (via ExpiryManager).
 *   3.8 - reading an expired share deletes it before responding.
 *  13.3 - per-owner active-file limit (50 for IP/Room).
 *  13.4 - upload exceeding the active-file limit is rejected without
 *         touching existing files.
 *  13.8 - logged-in Accounts use the per-Account limits (Requirement 16).
 *  16.4 - shares created during an authenticated session are owned by the
 *         Account principal (achieved by {@see createForPrincipal()} simply
 *         honouring the `Principal` it is given).
 */
class ShareService
{
    public function __construct(
        private readonly ExpiryManager $expiryManager,
        private readonly PasswordManager $passwordManager,
        private readonly MarkdownRenderer $markdownRenderer,
        private readonly NotificationService $notificationService,
    ) {
    }

    /**
     * Create a new Share owned by the given Principal.
     *
     * Recognised payload keys (all optional unless stated otherwise):
     *   - `expiry`          : expiry-option token (`1h`/`6h`/`24h`/`7d`,
     *                         plus `30d` for Account principals). Defaults
     *                         to `24h` (Requirement 3.2).
     *   - `password`        : plaintext password; hashed and stored on the
     *                         Share. Validated for length (6..128) by
     *                         {@see PasswordManager::hash()}. `null` /
     *                         empty string ⇒ no password.
     *   - `text_content`    : server-rendered HTML or plaintext body.
     *   - `markdown_source` : Markdown source (Requirement 12). Capped
     *                         at 500 000 chars by Form Request validation.
     *   - `is_e2ee`         : truthy ⇒ enable end-to-end encryption mode
     *                         (Requirement 15).
     *
     * @throws \InvalidArgumentException                  When `expiry` is invalid
     *                                                    for the principal kind.
     * @throws \Illuminate\Validation\ValidationException When the password
     *                                                    length is outside [6,128].
     */
    public function createForPrincipal(Principal $principal, array $payload): Share
    {
        $share = new Share();
        $share->owner_type = $principal->type();
        $share->owner_id = $principal->identifier();
        $share->expires_at = $this->expiryManager->parseOption(
            $this->stringOrNull($payload, 'expiry'),
            $principal,
        );

        $password = $payload['password'] ?? null;
        if (is_string($password) && $password !== '') {
            $share->password_hash = $this->passwordManager->hash($password);
        }

        if (array_key_exists('markdown_source', $payload)) {
            $this->applyMarkdownSource($share, $this->stringOrNull($payload, 'markdown_source'));
        } elseif (array_key_exists('text_content', $payload)) {
            $share->text_content = $this->stringOrNull($payload, 'text_content');
        }

        if (array_key_exists('is_e2ee', $payload)) {
            $share->is_e2ee = (bool) $payload['is_e2ee'];
        }

        $this->applyNotificationPreferences($share, $payload);

        $share->save();

        $this->notificationService->arm($share);

        return $share;
    }

    /**
     * Return the newest non-expired Share for a principal, creating one
     * with the default expiry when none exists yet. Used by upload paths
     * (legacy media, chunked upload) so files land on the Share aggregate
     * for Account and Room principals.
     */
    public function findOrCreateActiveForPrincipal(Principal $principal): Share
    {
        $existing = Share::query()
            ->where('owner_type', $principal->type())
            ->where('owner_id', $principal->identifier())
            ->where('expires_at', '>', Carbon::now())
            ->orderByDesc('id')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return $this->createForPrincipal($principal, ['expiry' => ExpiryManager::DEFAULT_OPTION]);
    }

    /**
     * Attach active guest (IP) text shares and legacy MediaFile uploads to
     * the authenticated Account. Covers the common case where a user created
     * content before logging in, or where localhost IP variants (::1 vs
     * 127.0.0.1) split ownership across rows.
     */
    public function claimGuestContentForAccount(Account $account, string $ipAddress): int
    {
        $accountId = (string) $account->getKey();
        $candidates = $this->ipClaimCandidates($ipAddress);
        $claimed = 0;

        $ipShares = Share::query()
            ->where('owner_type', Share::OWNER_TYPE_IP)
            ->whereIn('owner_id', $candidates)
            ->where('expires_at', '>', Carbon::now())
            ->get();

        foreach ($ipShares as $share) {
            if ($share->owner_type === Share::OWNER_TYPE_ACCOUNT && $share->owner_id === $accountId) {
                continue;
            }

            $share->owner_type = Share::OWNER_TYPE_ACCOUNT;
            $share->owner_id = $accountId;
            $share->save();
            $claimed++;
        }

        $mediaFiles = MediaFile::query()
            ->whereIn('ip_address', $candidates)
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->get();

        if ($mediaFiles->isNotEmpty()) {
            $targetShare = Share::query()
                ->where('owner_type', Share::OWNER_TYPE_ACCOUNT)
                ->where('owner_id', $accountId)
                ->where('expires_at', '>', Carbon::now())
                ->orderByDesc('id')
                ->first();

            if ($targetShare === null) {
                $targetShare = $this->findOrCreateActiveForPrincipal(
                    new AccountPrincipal((int) $account->getKey()),
                );
            }

            $morphClass = $targetShare->getMorphClass();

            foreach ($mediaFiles as $mediaFile) {
                $mediaIds = $mediaFile->getMedia('shared_files')->pluck('id');

                if ($mediaIds->isNotEmpty()) {
                    DB::table('media')
                        ->whereIn('id', $mediaIds)
                        ->update([
                            'model_type' => $morphClass,
                            'model_id'   => $targetShare->id,
                        ]);

                    $claimed += $mediaIds->count();
                }

                $mediaFile->delete();
            }
        }

        if ($claimed > 0) {
            Log::info('ShareService: claimed guest content for account', [
                'account_id' => $account->getKey(),
                'ip'         => $ipAddress,
                'claimed'    => $claimed,
            ]);
        }

        return $claimed;
    }

    /**
     * Apply a partial update to an existing Share.
     *
     * Recognised payload keys (every key is treated independently; absent
     * keys leave the corresponding column unchanged):
     *   - `password`        : plaintext password; `null` or `''` clears
     *                         the stored hash (Requirement 2.8). A non-
     *                         empty string is validated and re-hashed.
     *   - `expiry`          : expiry-option token; re-evaluated against
     *                         the principal kind that owns the Share
     *                         (Requirement 3.5 / 16.9).
     *   - `text_content`    : replace the rendered body.
     *   - `markdown_source` : replace the markdown source
     *                         (Requirement 12.6, 12.11).
     *   - `is_e2ee` /
     *     `is_favourite`    : toggle owner-side flags.
     */
    public function update(Share $share, array $payload): Share
    {
        $previousExpiry = $share->expires_at?->copy();
        $previousTextLength = mb_strlen(strip_tags((string) ($share->text_content ?? '')));

        if (array_key_exists('password', $payload)) {
            $password = $payload['password'];
            if ($password === null || $password === '') {
                // Requirement 2.8: removing the password is part of the
                // same save, so the next request sees the share unguarded.
                $share->password_hash = null;
            } else {
                $share->password_hash = $this->passwordManager->hash((string) $password);
            }
        }

        if (array_key_exists('expiry', $payload)) {
            $share->expires_at = $this->expiryManager->parseOption(
                $this->stringOrNull($payload, 'expiry'),
                $this->principalFromShare($share),
            );
        }

        if (array_key_exists('markdown_source', $payload)) {
            $this->applyMarkdownSource($share, $this->stringOrNull($payload, 'markdown_source'));
        } elseif (array_key_exists('text_content', $payload)) {
            $share->text_content = $this->stringOrNull($payload, 'text_content');
        }

        if (array_key_exists('is_e2ee', $payload)) {
            $share->is_e2ee = (bool) $payload['is_e2ee'];
        }

        if (array_key_exists('is_favourite', $payload)) {
            $share->is_favourite = (bool) $payload['is_favourite'];
        }

        $this->applyNotificationPreferences($share, $payload);

        $share->save();

        if (($previousExpiry?->timestamp ?? null) !== ($share->expires_at?->timestamp ?? null)) {
            $this->notificationService->rearmOnExpiryChange($share, $previousExpiry);
        }

        $newTextLength = mb_strlen(strip_tags((string) ($share->text_content ?? '')));

        if ($newTextLength !== $previousTextLength) {
            broadcast(new TextUpdated($share, $newTextLength));
        }

        return $share;
    }

    /**
     * Resolve a Share by its UUID or its public-gallery slug, enforcing
     * read-time expiry semantics before returning.
     *
     * The lookup tries `uuid` first because UUID-based reads dominate the
     * authenticated-owner path; the public gallery's 12-char slug is
     * checked as a fallback (Requirement 17). A miss in both columns
     * raises {@see ModelNotFoundException}, which Laravel maps to HTTP
     * 404 by default - byte-identical to the body returned for an
     * expired share, satisfying the non-disclosure goal of 17.6.
     *
     * @throws \App\Exceptions\ShareExpiredException                  When the
     *         resolved share is at or past its expiry; the share and its
     *         media are deleted before the exception escapes
     *         (Requirements 3.4, 3.8).
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException   When
     *         no share matches either column.
     */
    public function loadShare(string $uuidOrSlug): Share
    {
        $share = Share::query()
            ->where(function ($query) use ($uuidOrSlug): void {
                $query->where('uuid', $uuidOrSlug)
                    ->orWhere('public_slug', $uuidOrSlug);
            })
            ->first();

        if ($share === null) {
            throw (new ModelNotFoundException())->setModel(Share::class, [$uuidOrSlug]);
        }

        // Requirements 3.4 + 3.8: expiry check happens before any other
        // read-side concern (password gate, payload rendering, etc.) so
        // an expired share is always indistinguishable from a missing
        // one to the caller. enforceOnRead deletes the row + media and
        // throws ShareExpiredException (HTTP 404).
        $this->expiryManager->enforceOnRead($share);

        return $share;
    }

    /**
     * Determine whether a new file of the given size can be added to the
     * given Share without breaching the per-owner active-file or storage
     * caps configured in `config/airtoshare.php`.
     *
     * Rules applied (depend on `$share->owner_type`):
     *   - IP or Room owner   : up to `active_files_limit_ip` files
     *                          (default 50, Requirement 13.3).
     *   - Account owner      : up to `active_files_limit_account` files
     *                          (default 100, Requirement 16.9) AND total
     *                          stored bytes ≤ `account_storage_limit_bytes`
     *                          (default 1 GB, Requirement 16.10).
     *
     * Per-file size limits (`legacy_upload_max_bytes` /
     * `chunked_upload_max_bytes`, Requirements 13.1/13.2/13.5/13.6) are
     * enforced at the upload-controller boundary, not here, because they
     * are independent of the owner's existing usage. Negative
     * `$sizeBytes` is treated as invalid input and returns `false`.
     *
     * Counts include media attached to every active Share owned by the
     * principal, not just the Share passed in. This matches the
     * "active files at any one time" wording of Requirements 13.3 / 16.9
     * and ensures a malicious owner cannot bypass the limit by spreading
     * files across many shares.
     */
    public function canAddFile(Share $share, int $sizeBytes): bool
    {
        if ($sizeBytes < 0) {
            return false;
        }

        $isAccount = $share->owner_type === Share::OWNER_TYPE_ACCOUNT;

        $maxFiles = $isAccount
            ? (int) config('airtoshare.active_files_limit_account')
            : (int) config('airtoshare.active_files_limit_ip');

        // Pull every active share owned by the same principal and join
        // the media table once. We resolve the IDs in a separate query
        // (rather than a sub-query) so the cross-database SQL remains
        // portable to SQLite under test as well as MySQL in production.
        $shareIds = Share::query()
            ->where('owner_type', $share->owner_type)
            ->where('owner_id', $share->owner_id)
            ->where('expires_at', '>', Carbon::now())
            ->pluck('id');

        if ($shareIds->isEmpty()) {
            // First file for this owner - only the per-file caps could
            // possibly trip, and those are not our concern.
            return $sizeBytes >= 0 && $maxFiles >= 1
                && (! $isAccount || $sizeBytes <= (int) config('airtoshare.account_storage_limit_bytes'));
        }

        $morphClass = (new Share())->getMorphClass();

        $mediaQuery = Media::query()
            ->where('model_type', $morphClass)
            ->whereIn('model_id', $shareIds);

        $currentFileCount = (int) $mediaQuery->count();

        if ($currentFileCount + 1 > $maxFiles) {
            return false;
        }

        if ($isAccount) {
            $storageLimit = (int) config('airtoshare.account_storage_limit_bytes');
            $currentBytes = (int) (clone $mediaQuery)->sum('size');

            if ($currentBytes + $sizeBytes > $storageLimit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reconstruct the {@see Principal} that owns this Share from its
     * persisted owner_type/owner_id pair. Used by {@see update()} to
     * re-validate expiry options through {@see ExpiryManager} (because
     * the allowed option set is principal-dependent).
     */
    private function principalFromShare(Share $share): Principal
    {
        return match ($share->owner_type) {
            Share::OWNER_TYPE_ACCOUNT => new AccountPrincipal($share->owner_id),
            Share::OWNER_TYPE_ROOM    => new RoomPrincipal($share->owner_id),
            default                   => new IpPrincipal((string) $share->owner_id),
        };
    }

    /**
     * Coerce a payload value to a string or null, treating `false`/`0`
     * as the literal value the caller meant rather than as "missing".
     */
    private function stringOrNull(array $payload, string $key): ?string
    {
        if (! array_key_exists($key, $payload)) {
            return null;
        }

        $value = $payload[$key];

        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    /**
     * Persist Markdown source and render to `text_content` on save.
     *
     * Requirement 12.6: server renders CommonMark to HTML on save.
     * Requirement 12.11: on render failure the raw source is preserved
     * and `text_content` is left unchanged so the owner can edit/resubmit.
     */
    private function applyMarkdownSource(Share $share, ?string $markdown): void
    {
        $share->markdown_source = $markdown;

        if ($markdown === null || $markdown === '') {
            $share->text_content = null;

            return;
        }

        try {
            $share->text_content = $this->markdownRenderer->render($markdown);
        } catch (\Throwable) {
            // Preserve markdown_source; leave text_content as-is (Req 12.11).
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyNotificationPreferences(Share $share, array $payload): void
    {
        if (array_key_exists('notify_browser', $payload)) {
            $share->notify_browser = (bool) $payload['notify_browser'];
        }

        if (array_key_exists('notify_email', $payload)) {
            $share->notify_email = (bool) $payload['notify_email'];
        }

        if (array_key_exists('notify_email_address', $payload)) {
            $share->notify_email_address = $this->stringOrNull($payload, 'notify_email_address');
        }
    }

    /**
     * @return list<string>
     */
    private function ipClaimCandidates(string $ipAddress): array
    {
        $normalized = IpAddressMatcher::normalize($ipAddress);

        return array_values(array_unique(array_filter([
            trim($ipAddress),
            $normalized,
            '::1',
            '127.0.0.1',
        ])));
    }
}
