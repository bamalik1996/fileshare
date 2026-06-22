<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Room;
use App\Services\ClipboardSyncService;
use App\Services\RoomService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Room clipboard sync and presence endpoints (Requirements 10, 19).
 */
class RoomClipboardController extends Controller
{
    public function __construct(
        private readonly ClipboardSyncService $clipboardSyncService,
        private readonly RoomService $roomService,
    ) {
    }

    public function update(Request $request, string $code): JsonResponse
    {
        $room = $this->roomService->findByCode($code);

        if ($room === null) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Room not found.',
            ], 404);
        }

        $payload = $request->validate([
            'text'       => ['required', 'string'],
            'updated_at' => ['nullable', 'date'],
        ]);

        $timestamp = isset($payload['updated_at'])
            ? Carbon::parse($payload['updated_at'])
            : null;

        try {
            $accepted = $this->clipboardSyncService->update(
                $room,
                $payload['text'],
                $timestamp,
            );
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Clipboard text exceeds the 500,000 character limit.',
                'errors'  => $e->errors(),
            ], 422);
        }

        if (! $accepted) {
            return response()->json([
                'status'  => 'error',
                'message' => 'A newer clipboard update already exists.',
            ], 409);
        }

        return response()->json(['status' => 'success']);
    }

    public function presence(Request $request, string $code): JsonResponse
    {
        $room = $this->roomService->findByCode($code);

        if ($room === null) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Room not found.',
            ], 404);
        }

        $payload = $request->validate([
            'device_id' => ['required', 'string', 'max:64'],
        ]);

        $this->clipboardSyncService->touchPresence($room, $payload['device_id']);

        return response()->json(['status' => 'success']);
    }

    public function show(Request $request, string $code): JsonResponse
    {
        $room = $this->roomService->findByCode($code);

        if ($room === null) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Room not found.',
            ], 404);
        }

        return response()->json([
            'status'     => 'success',
            'code'       => $room->code,
            'text'       => $room->clipboard_text,
            'updated_at' => $room->clipboard_updated_at?->toIso8601String(),
        ]);
    }
}
