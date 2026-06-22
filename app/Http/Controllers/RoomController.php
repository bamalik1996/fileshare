<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\RoomAllocationException;
use App\Http\Middleware\RoomCodeRateLimit;
use App\Http\Middleware\SharePasswordGate;
use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * RoomController (design.md > Components and Interfaces > 7).
 *
 * Two HTTP entry points for the Room flow:
 *
 *  - {@see self::store()}  : `POST /rooms` creates a new Room with the
 *                            requested expiry option and (optional)
 *                            password, returning the freshly-allocated
 *                            6-character code so the caller can share
 *                            it. Delegates allocation to
 *                            {@see RoomService::create()} which owns the
 *                            uniqueness retry budget (Requirement 7.2)
 *                            and the password-length validation
 *                            (Requirement 7.7 + 2.2).
 *
 *  - {@see self::show()}   : `GET /r/{code}` looks up an existing Room
 *                            by code and either:
 *                              * 404 (non-disclosing) when format is
 *                                bad, code is unknown, or expired
 *                                (Requirement 7.4);
 *                              * delegates to {@see SharePasswordGate}
 *                                for the room's owned Share when a
 *                                password is set (Requirement 7.7); or
 *                              * redirects to the share view associated
 *                                with the Room when access is granted
 *                                (Requirement 7.3).
 *
 * Rate limiting (Requirement 7.8) is enforced by
 * {@see RoomCodeRateLimit} on the `show` route. The controller is
 * responsible for *recording* invalid submissions on the same per-IP
 * bucket so the 10-in-60s threshold flips the sticky 5-minute block;
 * the middleware itself only short-circuits incoming requests once the
 * block flag is set.
 */
class RoomController extends Controller
{
    public function __construct(private readonly RoomService $roomService)
    {
    }

    /**
     * POST /rooms
     *
     * Body parameters:
     *   - `expiry`   : string, optional. One of `1h`, `6h`, `24h`, `7d`.
     *                  Defaults to `24h` when omitted (Requirement 3.2).
     *                  `30d` is rejected for Room owners (Requirement 7.5).
     *   - `password` : string, optional. When present, must be 6..128
     *                  characters; hashed via PasswordManager and
     *                  stored on the Room (Requirement 7.7 + 2.1).
     *
     * Successful response (HTTP 201):
     *   `{ status: "success", code: "ABC234", expires_at: "..." }`
     *
     * Failure modes:
     *   - HTTP 422 : invalid expiry option or password length
     *                (Requirement 3.5 + 2.2). Surfaced via Laravel's
     *                default ValidationException handler.
     *   - HTTP 503 : Room Code allocation failed after retries
     *                (Requirement 7.2). Logged at warning level by
     *                RoomService; the response body is generic so the
     *                caller cannot tell allocation failure from any
     *                other transient outage.
     */
    public function store(Request $request): JsonResponse
    {
        // Lightweight validation - the precise option set is enforced
        // by ExpiryManager::parseOption() inside RoomService::create()
        // (and ultimately by RoomService throwing an
        // \InvalidArgumentException, which we map to 422 below). This
        // shape check rejects obviously wrong types early so the rest
        // of the pipeline can assume strings.
        $payload = $request->validate([
            'expiry'   => ['nullable', 'string'],
            'password' => ['nullable', 'string'],
        ]);

        try {
            $room = $this->roomService->create(
                $payload['expiry']   ?? null,
                $payload['password'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            // Out-of-set expiry option (Requirement 3.5 / 7.5).
            throw ValidationException::withMessages([
                'expiry' => [$e->getMessage()],
            ]);
        } catch (RoomAllocationException $e) {
            Log::warning('RoomController: room allocation exhausted retries', [
                'reason' => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Could not allocate a Room Code. Please retry.',
            ], 503);
        }

        return response()->json([
            'status'     => 'success',
            'code'       => $room->code,
            'expires_at' => $room->expires_at?->toIso8601String(),
            'url'        => url('/r/' . $room->code),
        ], 201);
    }

    /**
     * GET /r/{code}
     *
     * Resolves a Room Code to its Room and either:
     *   - returns 404 (non-disclosing body) when the code is malformed,
     *     unknown, or expired (Requirement 7.4);
     *   - returns 401 + password-required body when the Room is
     *     password-protected and the requester has not yet verified
     *     the password (Requirement 7.7 + 2.3);
     *   - redirects to the share view for the Room (Requirement 7.3).
     *
     * Rate limiting (Requirement 7.8): the {@see RoomCodeRateLimit}
     * middleware sits in front of this action and short-circuits with
     * HTTP 429 if the IP has been blocked. This action records every
     * invalid submission against the same bucket so the threshold flips
     * the block flag.
     *
     * Even when the request body would be JSON (Accept header), the
     * 200 case for *unprotected* rooms is a redirect to a HTML share
     * view to preserve the existing browser flow. JSON callers can
     * detect the 302 and follow it programmatically.
     */
    public function show(Request $request, string $code): Response
    {
        // Step 1: format validation. RoomService::findByCode() also
        // performs this check, but we duplicate it here so the
        // controller path is the single source of truth for "what
        // counts as an invalid submission" (Requirement 7.4 + 7.8).
        if (! $this->roomService->validateFormat($code)) {
            RoomCodeRateLimit::recordInvalidAttempt($request);
            return $this->notFound($request);
        }

        // Step 2: lookup. Returns null on unknown / expired code.
        $room = $this->roomService->findByCode($code);

        if ($room === null) {
            RoomCodeRateLimit::recordInvalidAttempt($request);
            return $this->notFound($request);
        }

        // Successful match: drop the per-IP invalid-attempts bucket so
        // a recipient who finally typed the right code after a few
        // typos is not penalised by the prior failures.
        RoomCodeRateLimit::clear($request);

        // Step 3: password gate. Requirement 7.7 reuses the rules of
        // Requirement 2 - the SharePasswordGate keys its session map
        // by Share id, so we look up the Room's owned Share and let
        // the gate enforce the contract directly. When the gate
        // returns 401 we propagate that response back to the caller
        // unchanged so the wire-level body is identical to the
        // standalone share-password flow.
        if ($this->roomRequiresPassword($room)) {
            // Resolve the share owned by this Room (the polymorphic
            // shares.owner_type='room' relation declared on
            // {@see \App\Models\Room::shares()}). Until task 12.x
            // wires Room creation to also create a Share aggregate,
            // there may be no Share row yet; in that case there is
            // nothing for the gate to check, so we fall back to a
            // Room-scoped session flag.
            $share = $room->shares()->first();

            if ($share !== null) {
                $verified = $request->hasSession()
                    && is_array($map = $request->session()->get(SharePasswordGate::SESSION_KEY, []))
                    && (($map[$share->id] ?? false) === true);

                if (! $verified) {
                    // Identical 401 body the SharePasswordGate would
                    // emit for an unverified, password-protected
                    // share (Requirement 2.6 + 7.7).
                    return $this->passwordRequired($request, $room);
                }
            } else {
                // No Share yet: enforce the password directly against
                // the Room's hash via a tiny Room-scoped session map.
                $verified = $request->hasSession()
                    && is_array($map = $request->session()->get('room_pw_ok', []))
                    && (($map[$room->id] ?? false) === true);

                if (! $verified) {
                    return $this->passwordRequired($request, $room);
                }
            }
        }

        // Step 4: access granted. Redirect to the canonical share
        // view for this Room. Until task 12.x stands up a dedicated
        // /room/{code}/share view we redirect to /s/{share-uuid} when
        // a Share exists, and otherwise return a 200 JSON / HTML body
        // so the caller can render the room view with the room code
        // alone. Both paths leave the rate-limit bucket cleared.
        $share = $room->shares()->first();

        if ($share !== null) {
            return redirect('/s/' . $share->uuid);
        }

        return $this->roomLanding($request, $room);
    }

    /**
     * Whether the Room is password-protected. A non-empty
     * `password_hash` column is the authoritative signal; the same
     * convention is used by {@see SharePasswordGate} for shares.
     */
    private function roomRequiresPassword(Room $room): bool
    {
        return is_string($room->password_hash) && $room->password_hash !== '';
    }

    /**
     * Generic, non-disclosing 404. Used for invalid format, unknown
     * code, and expired code (Requirement 7.4) so a probe cannot
     * distinguish the three outcomes.
     */
    private function notFound(Request $request): Response
    {
        if ($this->expectsJson($request)) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => 'Room not found.',
            ], 404);
        }

        return new Response('Room not found.', 404, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    /**
     * Identical 401 body to {@see SharePasswordGate::unauthorized()}
     * so the wire-level shape of the password challenge is the same
     * whether the requester arrived via /s/{uuid} or via /r/{code}
     * (Requirement 2.6 + 7.7).
     */
    private function passwordRequired(Request $request, ?Room $room = null): Response
    {
        if ($this->expectsJson($request)) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => SharePasswordGate::ERROR_MESSAGE,
            ], 401);
        }

        if ($room !== null) {
            return response()->view('share.password', [
                'identifier' => $room->code,
                'type'       => 'room',
                'returnUrl'  => url('/r/' . $room->code),
            ], 401);
        }

        return new Response(SharePasswordGate::ERROR_MESSAGE, 401, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    /**
     * Fallback landing response when the Room has no Share row yet
     * (e.g. the creator has not posted any text or media yet). Returns
     * a small JSON body identifying the Room so the client can render
     * its own waiting-room UI; HTML clients receive the same body as
     * plain text.
     */
    private function roomLanding(Request $request, Room $room): View|JsonResponse
    {
        if ($this->expectsJson($request)) {
            return new JsonResponse([
                'status'     => 'success',
                'code'       => $room->code,
                'expires_at' => $room->expires_at?->toIso8601String(),
            ], 200);
        }

        return view('home', [
            'share' => $room->shares()->first(),
            'room'  => $room,
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
