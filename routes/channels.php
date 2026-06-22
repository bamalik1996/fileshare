<?php

use App\Http\Middleware\SharePasswordGate;
use App\Models\Room;
use App\Models\Share;
use App\Support\IpAddressMatcher;
use Illuminate\Support\Facades\Broadcast;

/*
| Channel authorisation for Laravel Reverb (Requirements 14.6, 10.4).
|
| Password-protected shares and rooms require an active session flag in
| `share_pw_ok` (or `room_pw_ok` for room-only passwords), unless the
| requester owns the share (home-page editor flow).
*/

Broadcast::channel('share.{shareId}', function ($user, int $shareId) {
    $share = Share::query()->find($shareId);

    if ($share === null) {
        return false;
    }

    return $share->allowsBroadcastSubscription(request());
});

Broadcast::channel('presence-share.{shareId}', function ($user, int $shareId) {
    $share = Share::query()->find($shareId);

    if ($share === null) {
        return false;
    }

    if (! $share->allowsBroadcastSubscription(request())) {
        return false;
    }

    return [
        'id'   => session()->getId(),
        'name' => 'viewer-' . substr(session()->getId(), 0, 8),
    ];
});

Broadcast::channel('room.{roomId}.clipboard', function ($user, int $roomId) {
    $room = Room::query()->find($roomId);

    if ($room === null || $room->isExpired()) {
        return false;
    }

    if (! is_string($room->password_hash) || $room->password_hash === '') {
        return true;
    }

    $share = $room->shares()->first();

    if ($share !== null) {
        return $share->allowsBroadcastSubscription(request());
    }

    $roomMap = request()->session()->get('room_pw_ok', []);

    return is_array($roomMap) && (($roomMap[$roomId] ?? false) === true);
});

Broadcast::channel('presence-room.{roomId}', function ($user, int $roomId) {
    $room = Room::query()->find($roomId);

    if ($room === null || $room->isExpired()) {
        return false;
    }

    if (is_string($room->password_hash) && $room->password_hash !== '') {
        $share = $room->shares()->first();

        if ($share !== null) {
            if (! $share->allowsBroadcastSubscription(request())) {
                return false;
            }
        } else {
            $roomMap = request()->session()->get('room_pw_ok', []);

            if (! is_array($roomMap) || (($roomMap[$roomId] ?? false) !== true)) {
                return false;
            }
        }
    }

    return [
        'id'   => request()->header('X-Airtoshare-Device-Id', session()->getId()),
        'name' => 'device-' . substr(session()->getId(), 0, 8),
    ];
});

Broadcast::channel('ip.{ipToken}', function ($user, string $ipToken) {
    return IpAddressMatcher::sameHost(
        request()->ip(),
        IpAddressMatcher::fromChannelToken($ipToken),
    );
});
