<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Principal\AccountPrincipal;
use App\Domain\Principal\ApiKeyPrincipal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/limits — reports the per-principal upload and active-file
 * limits so the upload page can display the correct ceilings before the
 * user picks a file.
 *
 * Requirement 13.7: "WHEN the upload page is loaded, THE AirToShareA
 * SHALL display the configured per-upload size limits for both the chunked
 * and the legacy endpoints, and the active files limit."
 *
 * The endpoint is intentionally placed under /api/v1 (not /api/v2) so it
 * is accessible to guest IP users without an API key. It relies only on the
 * `ResolvePrincipal` middleware (bound globally in bootstrap/app.php), so
 * it works for all three principal kinds: IpPrincipal, AccountPrincipal,
 * and ApiKeyPrincipal (the latter two share the same account limits per
 * Requirement 16.9).
 *
 * Response shape:
 * {
 *   "status": "success",
 *   "limits": {
 *     "legacy_upload_max_bytes":   25165824,
 *     "chunked_upload_max_bytes":  524288000,
 *     "active_files_limit":        50,          // 100 for account principals
 *     "account_storage_limit_bytes": null        // null for IP/room, int for account
 *   }
 * }
 */
class LimitsController extends Controller
{
    /**
     * Return the effective upload and active-file limits for the requesting
     * principal.
     *
     * The response is intentionally small and cacheable in the browser;
     * it contains no user-specific data beyond the limit tier, so a
     * short-lived client-side cache (e.g. 60 s) is safe.
     */
    public function index(Request $request): JsonResponse
    {
        $principal = $request->principal();

        // Account principals (session login or API key) get the higher
        // per-account limits defined by Requirement 16.9. All others
        // (IP, room) get the IP limits from Requirement 13.3.
        $isAccount = $principal instanceof AccountPrincipal
            || $principal instanceof ApiKeyPrincipal;

        $activeFilesLimit = $isAccount
            ? (int) config('airtoshare.active_files_limit_account')
            : (int) config('airtoshare.active_files_limit_ip');

        $storageLimit = $isAccount
            ? (int) config('airtoshare.account_storage_limit_bytes')
            : null;

        return response()->json([
            'status' => 'success',
            'limits' => [
                'legacy_upload_max_bytes'    => (int) config('airtoshare.legacy_upload_max_bytes'),
                'chunked_upload_max_bytes'   => (int) config('airtoshare.chunked_upload_max_bytes'),
                'active_files_limit'         => $activeFilesLimit,
                'account_storage_limit_bytes' => $storageLimit,
            ],
        ]);
    }
}
