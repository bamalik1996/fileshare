<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Share;
use App\Services\AccountService;
use App\Services\PublicGalleryService;
use App\Services\ShareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    private const MAX_FAVOURITES = 50;

    public function __construct(
        private readonly AccountService $accounts,
        private readonly PublicGalleryService $publicGallery,
        private readonly ShareService $shareService,
        private readonly \App\Services\AnalyticsService $analytics,
    ) {
    }

    public function shares(Request $request): View
    {
        /** @var Account $account */
        $account = $request->user('account');

        $this->shareService->claimGuestContentForAccount($account, (string) $request->ip());
        $request->session()->put('guest_content_claimed_for', (string) $account->getKey());
        $shares = Share::query()
            ->where('owner_type', Share::OWNER_TYPE_ACCOUNT)
            ->where('owner_id', (string) $account->getKey())
            ->where('expires_at', '>', now()->subDays(30))
            ->withCount('media')
            ->orderByDesc('expires_at')
            ->get();

        // Per-share view/download counters for the list cards.
        $analytics = $this->analytics->summaryForShareIds($shares->pluck('id'));

        return view('account.shares', [
            'account'         => $account,
            'shares'          => $shares,
            'favouriteCount'  => $account->favourites()->count(),
            'analytics'       => $analytics,
        ]);
    }

    /**
     * JSON analytics drill-down for a single Share (owner only). Powers the
     * expandable "Analytics" panel on the My Shares dashboard.
     */
    public function analytics(Request $request, Share $share): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user('account');

        if ($share->owner_type !== Share::OWNER_TYPE_ACCOUNT
            || $share->owner_id !== (string) $account->getKey()) {
            return response()->json(['status' => 'error', 'message' => 'Forbidden.'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $this->analytics->detailFor($share),
        ]);
    }

    /**
     * Schedule account deletion in 48 hours (soft request). Requires typed
     * confirmation so a single mis-click cannot wipe the account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        /** @var Account $account */
        $account = $request->user('account');

        $validated = $request->validate([
            'confirm_email' => ['required', 'email'],
        ]);

        if (strtolower(trim($validated['confirm_email'])) !== strtolower($account->email)) {
            return redirect()
                ->route('account.shares')
                ->withErrors(['confirm_email' => 'Email does not match your account. Deletion was not scheduled.']);
        }

        $this->accounts->scheduleDeletion($account);

        return redirect()
            ->route('account.shares')
            ->with('status', 'Account deletion scheduled. You have 48 hours to cancel before it is permanently removed.');
    }

    /**
     * Cancel a pending account deletion during the grace period.
     */
    public function cancelDeletion(Request $request): RedirectResponse
    {
        /** @var Account $account */
        $account = $request->user('account');
        $this->accounts->cancelDeletion($account);

        return redirect()
            ->route('account.shares')
            ->with('status', 'Account deletion cancelled. Your account is safe.');
    }

    /**
     * Branding settings page (SaaS features, Phase 4).
     */
    public function settings(Request $request): View
    {
        /** @var Account $account */
        $account = $request->user('account');

        return view('account.settings', [
            'account'  => $account,
            'branding' => $account->brandingPayload(),
        ]);
    }

    /**
     * Persist branding settings: logo upload, accent colour, and message.
     * These are applied to the Account's public share and collect pages.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        /** @var Account $account */
        $account = $request->user('account');

        $validated = $request->validate([
            'brand_color'   => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brand_message' => ['nullable', 'string', 'max:160'],
            'logo'          => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'remove_logo'   => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_logo') && ! empty($account->brand_logo_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($account->brand_logo_path);
            $account->brand_logo_path = null;
        }

        if ($request->hasFile('logo')) {
            if (! empty($account->brand_logo_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($account->brand_logo_path);
            }

            $path = $request->file('logo')->store('brand-logos', 'public');
            $account->brand_logo_path = $path;
        }

        $account->brand_color = $validated['brand_color'] ?? null;
        $account->brand_message = $validated['brand_message'] ?? null;
        $account->save();

        return redirect()->route('account.settings')->with('status', 'Branding updated.');
    }

    public function favourite(Request $request, Share $share): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user('account');

        if ($share->owner_type !== Share::OWNER_TYPE_ACCOUNT
            || $share->owner_id !== (string) $account->getKey()) {
            return response()->json(['status' => 'error', 'message' => 'Forbidden.'], 403);
        }

        $pivot = $account->favourites()->where('share_id', $share->id)->exists();

        if ($pivot) {
            $account->favourites()->detach($share->id);
            $share->is_favourite = false;
            $share->save();

            return response()->json(['status' => 'success', 'favourited' => false]);
        }

        if ($account->favourites()->count() >= self::MAX_FAVOURITES) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Favourites limit reached (50).',
            ], 422);
        }

        $account->favourites()->attach($share->id, ['created_at' => now()]);
        $share->is_favourite = true;
        $share->save();

        return response()->json(['status' => 'success', 'favourited' => true]);
    }

    public function enablePublic(Request $request, Share $share): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user('account');

        if ($share->owner_type !== Share::OWNER_TYPE_ACCOUNT
            || $share->owner_id !== (string) $account->getKey()) {
            return response()->json(['status' => 'error', 'message' => 'Forbidden.'], 403);
        }

        $slug = $this->publicGallery->enable($share);

        return response()->json([
            'status' => 'success',
            'slug'   => $slug,
            'url'    => url('/p/' . $slug),
        ]);
    }

    public function disablePublic(Request $request, Share $share): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user('account');

        if ($share->owner_type !== Share::OWNER_TYPE_ACCOUNT
            || $share->owner_id !== (string) $account->getKey()) {
            return response()->json(['status' => 'error', 'message' => 'Forbidden.'], 403);
        }

        $this->publicGallery->disable($share);

        return response()->json(['status' => 'success']);
    }

    /**
     * Toggle revocation of a Share's links (SaaS features, Phase 2). When
     * revoked, downloads return 410 and recipient/public views return 404
     * immediately. Owner can un-revoke to restore access.
     */
    public function revoke(Request $request, Share $share): JsonResponse
    {
        if (! $this->ownsShare($request, $share)) {
            return response()->json(['status' => 'error', 'message' => 'Forbidden.'], 403);
        }

        $share->revoked_at = $share->isRevoked() ? null : now();
        $share->save();

        return response()->json([
            'status'  => 'success',
            'revoked' => $share->isRevoked(),
        ]);
    }

    /**
     * Set (or clear) a Share's download ceiling. A limit of 1 is the
     * "burn after reading" shortcut; null clears the ceiling.
     */
    public function downloadLimit(Request $request, Share $share): JsonResponse
    {
        if (! $this->ownsShare($request, $share)) {
            return response()->json(['status' => 'error', 'message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'max_downloads' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ]);

        $share->max_downloads = $validated['max_downloads'] ?? null;
        $share->save();

        return response()->json([
            'status'         => 'success',
            'max_downloads'  => $share->max_downloads,
            'download_count' => (int) $share->download_count,
        ]);
    }

    private function ownsShare(Request $request, Share $share): bool
    {
        /** @var Account $account */
        $account = $request->user('account');

        return $share->owner_type === Share::OWNER_TYPE_ACCOUNT
            && $share->owner_id === (string) $account->getKey();
    }
}
