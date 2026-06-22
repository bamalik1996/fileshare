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

        return view('account.shares', [
            'account'         => $account,
            'shares'          => $shares,
            'favouriteCount'  => $account->favourites()->count(),
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        /** @var Account $account */
        $account = $request->user('account');
        $this->accounts->deleteAccount($account);

        return redirect('/')->with('status', 'Account deleted.');
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
}
