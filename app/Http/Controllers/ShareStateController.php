<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Share;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/shares/{id}/state — reconciliation payload (Requirement 14.9).
 */
class ShareStateController extends Controller
{
    public function show(Request $request, string $id): JsonResponse
    {
        $share = Share::query()
            ->where('uuid', $id)
            ->orWhere('id', $id)
            ->firstOrFail();

        $media = $share->getMedia('shared_files')->map(static function ($item) {
            return [
                'uuid'      => $item->uuid,
                'name'      => $item->name,
                'size'      => $item->size,
                'mime_type' => $item->mime_type,
                'url'       => $item->getUrl(),
            ];
        })->values();

        $text = strip_tags((string) ($share->text_content ?? ''));

        return response()->json([
            'status'      => 'success',
            'share_uuid'  => $share->uuid,
            'text_length' => mb_strlen($text),
            'media'       => $media,
        ]);
    }
}
