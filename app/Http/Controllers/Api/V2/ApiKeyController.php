<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Share;
use App\Services\ApiKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function __construct(private readonly ApiKeyService $apiKeys)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:64'],
        ]);

        $account = $request->user('account');
        $result = $this->apiKeys->create($account, $data['name'] ?? 'Default');

        return response()->apiOk([
            'api_key' => [
                'id'         => $result['api_key']->id,
                'name'       => $result['api_key']->name,
                'key_prefix' => $result['api_key']->key_prefix,
                'plain'      => $result['plain'],
            ],
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $account = $request->user('account');
        $apiKey = $account->apiKeys()->where('id', $id)->first();

        if ($apiKey === null) {
            return response()->apiError('Not found.', [], 404);
        }

        $this->apiKeys->revoke($apiKey);

        return response()->apiOk(['revoked' => true]);
    }
}
