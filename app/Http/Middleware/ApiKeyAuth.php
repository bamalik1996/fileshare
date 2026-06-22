<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Principal\ApiKeyPrincipal;
use App\Services\ApiKeyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function __construct(private readonly ApiKeyService $apiKeys)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');
        if (! str_starts_with($header, 'Bearer ')) {
            return $this->unauthorized();
        }

        $token = trim(substr($header, 7));
        if ($token === '') {
            return $this->unauthorized();
        }

        $apiKey = $this->apiKeys->findByBearer($token);
        if ($apiKey === null || $apiKey->isRevoked()) {
            return $this->unauthorized();
        }

        $apiKey->last_used_at = now();
        $apiKey->save();

        $principal = new ApiKeyPrincipal(
            accountId: (int) $apiKey->account_id,
            apiKeyId: (string) $apiKey->getKey(),
        );

        $request->attributes->set('principal', $principal);
        $request->attributes->set('api_key_id', (string) $apiKey->getKey());
        $request->attributes->set('api_key_account', $apiKey->account);

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return response()->json([
            'status'  => 'error',
            'message' => 'Unauthorized.',
        ], 401);
    }
}
