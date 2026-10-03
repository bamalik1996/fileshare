<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Principal\AccountPrincipal;
use App\Domain\Principal\ApiKeyPrincipal;
use App\Domain\Principal\Principal;
use App\Models\Account;

/**
 * Central resolution point for per-principal limits and feature flags
 * (SaaS features, Phase 6).
 *
 * Today there is a single implicit plan - "free" - and every ceiling is
 * read from config/airtoshare.php. The point of this seam is that call
 * sites (upload gate, limits endpoint, UI) ask PlanService instead of
 * reading config or hard-coding numbers directly. When paid plans are
 * introduced later, only this class changes: it can look up the Account's
 * plan (e.g. via Laravel Cashier) and return different ceilings / feature
 * toggles, with no changes required at the call sites.
 *
 * No billing or payment logic lives here yet - this is purely the interface
 * through which limits flow.
 */
class PlanService
{
    public const PLAN_FREE = 'free';

    /**
     * Feature flags. All features are currently enabled for everyone; this
     * map is where a future plan gate would differentiate tiers.
     *
     * @var list<string>
     */
    public const FEATURES = [
        'analytics',
        'link_revoke',
        'download_limit',
        'burn_after_read',
        'file_request',
        'branding',
        'public_gallery',
        'api',
    ];

    /**
     * The plan an Account is on. Always "free" for now.
     */
    public function planFor(?Account $account): string
    {
        return self::PLAN_FREE;
    }

    /**
     * Whether a feature is available to the given Account. Everything is
     * on while we are free-for-all; this is the future plan gate.
     */
    public function can(string $feature, ?Account $account = null): bool
    {
        return in_array($feature, self::FEATURES, true);
    }

    /**
     * Account-tier principals (session login or API key) share the higher
     * per-account ceilings; everyone else uses the IP tier.
     */
    public function isAccountTier(Principal $principal): bool
    {
        return $principal instanceof AccountPrincipal
            || $principal instanceof ApiKeyPrincipal;
    }

    /**
     * Maximum number of concurrently active files for a principal.
     */
    public function activeFilesLimit(Principal $principal): int
    {
        return $this->isAccountTier($principal)
            ? (int) config('airtoshare.active_files_limit_account')
            : (int) config('airtoshare.active_files_limit_ip');
    }

    /**
     * Total storage ceiling in bytes, or null when the tier is uncapped
     * on storage (IP / room tiers only cap file count).
     */
    public function storageLimitBytes(Principal $principal): ?int
    {
        return $this->isAccountTier($principal)
            ? (int) config('airtoshare.account_storage_limit_bytes')
            : null;
    }

    public function legacyUploadMaxBytes(): int
    {
        return (int) config('airtoshare.legacy_upload_max_bytes');
    }

    public function chunkedUploadMaxBytes(): int
    {
        return (int) config('airtoshare.chunked_upload_max_bytes');
    }

    /**
     * The ceilings payload surfaced to the upload UI (Requirement 13.7).
     *
     * @return array{
     *     legacy_upload_max_bytes:int,
     *     chunked_upload_max_bytes:int,
     *     active_files_limit:int,
     *     account_storage_limit_bytes:?int,
     *     plan:string
     * }
     */
    public function limitsPayload(Principal $principal): array
    {
        return [
            'legacy_upload_max_bytes'     => $this->legacyUploadMaxBytes(),
            'chunked_upload_max_bytes'    => $this->chunkedUploadMaxBytes(),
            'active_files_limit'          => $this->activeFilesLimit($principal),
            'account_storage_limit_bytes' => $this->storageLimitBytes($principal),
            'plan'                        => self::PLAN_FREE,
        ];
    }
}
