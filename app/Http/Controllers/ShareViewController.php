<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Share;
use App\Services\ExpiryManager;
use App\Services\ShareService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Owner / recipient share view at GET /s/{share}.
 */
class ShareViewController extends Controller
{
    public function __construct(
        private readonly ShareService $shareService,
        private readonly ExpiryManager $expiryManager,
    ) {
    }

    public function show(Request $request, Share $share): View|Response
    {
        try {
            $this->expiryManager->enforceOnRead($share);
        } catch (\App\Exceptions\ShareExpiredException) {
            abort(404);
        }

        $room = null;
        if ($share->owner_type === Share::OWNER_TYPE_ROOM) {
            $room = Room::query()->find($share->owner_id);
        }

        $isOwner = $share->ownedByPrincipal($request->principal());

        $shareMedia = [];
        try {
            $shareMedia = $share->getMedia('shared_files')->map(static function ($item) {
                return [
                    'uuid'        => $item->uuid,
                    'name'        => $item->name,
                    'size'        => $item->size,
                    'mime_type'   => $item->mime_type,
                    'url'         => $item->getUrl(),
                    'preview_url' => $item->getFullUrl(),
                ];
            })->values()->all();
        } catch (\Throwable) {
            $shareMedia = [];
        }

        return view('home', [
            'share'        => $share,
            'room'         => $room,
            'viewingShare' => ! $isOwner,
            'shareMedia'   => $shareMedia,
        ]);
    }
}
