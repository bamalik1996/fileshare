<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Share;
use App\Services\ShareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShareApiController extends Controller
{
    public function __construct(private readonly ShareService $shares)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $principal = $request->principal();

        $items = Share::query()
            ->ownedBy($principal)
            ->active()
            ->orderByDesc('id')
            ->get(['uuid', 'expires_at', 'public_slug', 'is_e2ee', 'created_at']);

        return response()->apiOk(['shares' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'expiry'   => ['nullable', 'string'],
            'password' => ['nullable', 'string'],
            'is_e2ee'  => ['nullable', 'boolean'],
        ]);

        $share = $this->shares->createForPrincipal($request->principal(), $payload);

        return response()->apiOk(['share' => $share->only(['uuid', 'expires_at', 'is_e2ee'])], 201);
    }

    public function show(Request $request, Share $share): JsonResponse
    {
        if (! $this->owns($request, $share)) {
            return response()->apiError('Forbidden.', [], 403);
        }

        return response()->apiOk(['share' => $share]);
    }

    private function owns(Request $request, Share $share): bool
    {
        $p = $request->principal();

        return $share->owner_type === $p->type()
            && $share->owner_id === $p->identifier();
    }
}
