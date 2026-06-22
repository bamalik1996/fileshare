<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\PasswordVerifyRateLimit;
use App\Http\Middleware\SharePasswordGate;
use App\Models\Room;
use App\Models\Share;
use App\Services\PasswordManager;
use App\Services\RoomService;
use App\Services\ShareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Password verification and owner-side password management (Requirement 2).
 */
class SharePasswordController extends Controller
{
    public function __construct(
        private readonly PasswordManager $passwordManager,
        private readonly ShareService $shareService,
        private readonly RoomService $roomService,
    ) {
    }

    /**
     * POST /api/v1/shares/{share}/verify-password
     */
    public function verifyShare(Request $request, string $share): JsonResponse|RedirectResponse
    {
        $model = $this->resolveShare($share);

        if ($model === null || ! $model->hasPassword()) {
            return $this->deny($request);
        }

        return $this->verifyAgainstHash(
            $request,
            (string) $request->input('password', ''),
            (string) $model->password_hash,
            (int) $model->id,
            fn () => SharePasswordGate::grantVerifiedShare($request, (int) $model->id),
            redirect()->to('/s/' . $model->uuid),
        );
    }

    /**
     * POST /api/v1/rooms/{code}/verify-password
     */
    public function verifyRoom(Request $request, string $code): JsonResponse|RedirectResponse
    {
        if (! $this->roomService->validateFormat($code)) {
            return $this->deny($request);
        }

        $room = $this->roomService->findByCode($code);

        if ($room === null || ! is_string($room->password_hash) || $room->password_hash === '') {
            return $this->deny($request);
        }

        return $this->verifyAgainstHash(
            $request,
            (string) $request->input('password', ''),
            $room->password_hash,
            'room-' . $room->id,
            function () use ($request, $room): void {
                $this->grantVerifiedRoom($request, $room);

                $share = $room->shares()->first();
                if ($share !== null) {
                    SharePasswordGate::grantVerifiedShare($request, (int) $share->id);
                }
            },
            redirect()->to('/r/' . $room->code),
        );
    }

    /**
     * POST /api/v1/share/password — set or clear password on the caller's active share.
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'password' => ['nullable', 'string', 'max:128'],
        ]);

        $share = $this->shareService->findOrCreateActiveForPrincipal($request->principal());

        if (! $share->ownedByPrincipal($request->principal())) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Forbidden.',
            ], 403);
        }

        try {
            $password = array_key_exists('password', $data) ? $data['password'] : null;
            $share = $this->shareService->update($share, ['password' => $password]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid password.',
                'errors'  => $e->errors(),
            ], 422);
        }

        return response()->json([
            'status'       => 'success',
            'message'      => $share->hasPassword() ? 'Password protection enabled.' : 'Password removed.',
            'has_password' => $share->hasPassword(),
            'share_uuid'   => $share->uuid,
        ]);
    }

    private function verifyAgainstHash(
        Request $request,
        string $plain,
        string $hash,
        string|int $rateKeyId,
        callable $onSuccess,
        RedirectResponse $redirect,
    ): JsonResponse|RedirectResponse {
        $key = PasswordVerifyRateLimit::keyFor($request, $rateKeyId);
        $decay = (int) config('airtoshare.password_verify_rate_limit.decay_seconds', 900);
        $maxAttempts = (int) config('airtoshare.password_verify_rate_limit.max_attempts', 5);

        if (RateLimiter::tooManyAttempts($key, max(1, $maxAttempts))) {
            return $this->deny($request);
        }

        if (! $this->passwordManager->verify($plain, $hash)) {
            RateLimiter::hit($key, $decay);

            return $this->deny($request);
        }

        RateLimiter::clear($key);
        $onSuccess();

        if ($this->expectsJson($request)) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Access granted.',
            ]);
        }

        return $redirect;
    }

    private function grantVerifiedRoom(Request $request, Room $room): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $map = $request->session()->get('room_pw_ok', []);
        if (! is_array($map)) {
            $map = [];
        }

        $map[$room->id] = true;
        $request->session()->put('room_pw_ok', $map);
    }

    private function resolveShare(string $identifier): ?Share
    {
        $share = Share::query()->where('uuid', $identifier)->first();
        if ($share !== null) {
            return $share;
        }

        return Share::query()->where('public_slug', $identifier)->first();
    }

    private function deny(Request $request): JsonResponse|Response
    {
        if ($this->expectsJson($request)) {
            return response()->json([
                'status'  => 'error',
                'message' => SharePasswordGate::ERROR_MESSAGE,
            ], 401);
        }

        return new Response(SharePasswordGate::ERROR_MESSAGE, 401, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    private function expectsJson(Request $request): bool
    {
        if ($request->expectsJson()) {
            return true;
        }

        return str_starts_with($request->path(), 'api/');
    }
}
