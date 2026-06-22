<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\QrGenerationException;
use App\Exceptions\ShareExpiredException;
use App\Models\Share;
use App\Services\QrGenerator;
use App\Services\ShareService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * QR Code controller (design.md > Components and Interfaces > 1).
 *
 * Serves the PNG body produced by {@see QrGenerator} for any active
 * Share, resolved by either its UUID or its 12-char public-gallery slug.
 *
 * Acceptance criteria covered:
 *
 *   1.1 - the QR code is produced for the resolved Share's HTTPS URL on
 *         every request. Generation cost is bounded by the BaconQrCode
 *         + GD pipeline which renders the default 240x240 PNG in
 *         single-digit milliseconds, well inside the 2-second budget.
 *   1.2 - {@see QrGenerator} renders at >= 200x200 pixels (default 240).
 *   1.4 - the same endpoint serves both inline previews and downloads.
 *         Without a query parameter the response is rendered inline
 *         (Content-Disposition omitted, defaulting to inline). With
 *         `?download=1` the response forces download with the file name
 *         `share-{slug}.png` (Content-Disposition: attachment).
 *   1.5 - on generation failure the controller renders the
 *         `qr.fallback` HTML view (Share URL as text + error banner)
 *         instead of offering a download. The view never includes the
 *         download anchor so the failure mode cannot accidentally
 *         degrade into a broken PNG download.
 *   1.6 - on failure the controller logs the underlying exception
 *         message together with `share_id` and `share_uuid` so the
 *         operator can correlate the failure with downstream services
 *         (BaconQrCode / GD) without leaking the failure body to the
 *         client.
 *
 * Resolution semantics mirror {@see ShareService::loadShare()} - either
 * `shares.uuid` or `shares.public_slug` is accepted. When the lookup
 * finds an expired Share, ShareService deletes it and throws
 * {@see ShareExpiredException} (HTTP 404). When the slug does not match
 * any row, {@see ModelNotFoundException} surfaces (HTTP 404). Both 404
 * responses are byte-identical to a never-existed Share so the QR
 * endpoint cannot be used as an existence oracle (Requirement 17.6).
 */
class QrCodeController extends Controller
{
    public function __construct(
        private readonly ShareService $shareService,
        private readonly QrGenerator $qrGenerator,
    ) {
    }

    /**
     * GET /qr/{slug}
     *
     * Renders the Share's QR code. `?download=1` forces an attachment
     * disposition; any other value (including absent) renders inline.
     */
    public function show(Request $request, string $slug): Response
    {
        // ShareService::loadShare() handles both identifier kinds and
        // raises 404 on expiry / not-found. The 404 path is delegated
        // to the framework's exception handler so the response body is
        // identical to every other Share-not-found response.
        $share = $this->shareService->loadShare($slug);

        $shareUrl = $this->canonicalShareUrl($share, $slug);

        try {
            $png = $this->qrGenerator->generateOrFail($shareUrl);
        } catch (QrGenerationException $e) {
            // Acceptance criterion 1.6: log share id + reason. Both the
            // primary key and the external uuid are captured because
            // ops dashboards typically surface only one of them.
            Log::error('QR code generation failed.', [
                'share_id'   => $share->id,
                'share_uuid' => $share->uuid,
                'reason'     => $e->getPrevious()?->getMessage() ?? $e->getMessage(),
            ]);

            return $this->renderFallback($shareUrl);
        }

        return $this->renderPng($png, $slug, $request->query('download') === '1');
    }

    /**
     * Build the absolute HTTPS URL that the QR encodes.
     *
     * The canonical URL is whichever public-facing share view matches
     * the identifier the caller provided:
     *
     *   - the slug matches `shares.public_slug` → `/p/{slug}` (the
     *     Public Gallery route, Requirement 17). Public-gallery shares
     *     are the canonical "shareable link" target.
     *   - otherwise → `/s/{uuid}` (the owner-facing share view, the
     *     primary external identifier per design.md > Data Models).
     *
     * Using whatever identifier the requester supplied keeps the QR
     * payload aligned with the link the user is actually distributing,
     * so a scanner lands on the same surface the QR appeared on.
     */
    private function canonicalShareUrl(Share $share, string $slug): string
    {
        if ($share->public_slug !== null && $share->public_slug === $slug) {
            return url('/p/' . $slug);
        }

        return url('/s/' . $share->uuid);
    }

    /**
     * Build the PNG response. `download` toggles the disposition between
     * inline (default) and attachment. The file name uses the slug the
     * caller supplied so the on-disk artefact mirrors the link they
     * shared (Requirement 1.4).
     */
    private function renderPng(string $png, string $slug, bool $download): Response
    {
        $disposition = $download
            ? 'attachment; filename="share-' . $this->safeFileSlug($slug) . '.png"'
            : 'inline; filename="share-' . $this->safeFileSlug($slug) . '.png"';

        return response($png, 200, [
            'Content-Type'        => 'image/png',
            'Content-Length'      => (string) strlen($png),
            'Content-Disposition' => $disposition,
            // Discourage caches from holding stale QR images across
            // share invalidation (e.g. public-gallery slug rotation).
            'Cache-Control'       => 'private, max-age=0, no-store',
        ]);
    }

    /**
     * Render the fallback page (URL text + error banner, no download
     * affordance). Sent with HTTP 503 so any retry middleware in the
     * caller stack can decide how to react, and so the inline `<img>`
     * tag in the surrounding Share view fires its `error` listener
     * (criterion 1.5 + design.md > Component 1 frontend wiring).
     */
    private function renderFallback(string $shareUrl): Response
    {
        $html = view('qr.fallback', [
            'shareUrl' => $shareUrl,
        ])->render();

        return response($html, 503, [
            'Content-Type'  => 'text/html; charset=UTF-8',
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }

    /**
     * Strip characters that would be unsafe in a Content-Disposition
     * filename. The slug coming in is either a uuid (hex + dashes) or a
     * 12-char URL-safe gallery slug (`[A-Za-z0-9_-]`); both are already
     * filename-safe, but we sanitise defensively in case the slug
     * resolution path widens in the future.
     */
    private function safeFileSlug(string $slug): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_\-]/', '', $slug) ?? '';

        return $clean === '' ? 'share' : $clean;
    }
}
