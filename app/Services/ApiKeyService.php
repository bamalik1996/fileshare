<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApiKeyService
{
    private const MAX_ACTIVE_KEYS = 5;

    private const MIN_KEY_LENGTH = 32;

    private const PREFIX_LENGTH = 8;

    /**
     * @return array{api_key: ApiKey, plain: string}
     *
     * @throws ValidationException
     */
    public function create(Account $account, string $name = 'Default'): array
    {
        $active = $account->apiKeys()->active()->count();
        if ($active >= self::MAX_ACTIVE_KEYS) {
            throw ValidationException::withMessages([
                'api_key' => ['Maximum of ' . self::MAX_ACTIVE_KEYS . ' active API keys allowed.'],
            ]);
        }

        $plain = Str::random(self::MIN_KEY_LENGTH);
        $prefix = substr($plain, 0, self::PREFIX_LENGTH);

        $apiKey = $account->apiKeys()->create([
            'name'       => $name,
            'key_prefix' => $prefix,
            'key_hash'   => Hash::make($plain),
        ]);

        return ['api_key' => $apiKey, 'plain' => $plain];
    }

    public function revoke(ApiKey $apiKey): void
    {
        $apiKey->revoked_at = now();
        $apiKey->save();
    }

    public function findByBearer(string $token): ?ApiKey
    {
        if (strlen($token) < self::PREFIX_LENGTH) {
            return null;
        }

        $prefix = substr($token, 0, self::PREFIX_LENGTH);

        $candidates = ApiKey::query()
            ->active()
            ->where('key_prefix', $prefix)
            ->get();

        foreach ($candidates as $candidate) {
            if (Hash::check($token, $candidate->key_hash)) {
                return $candidate;
            }
        }

        return null;
    }
}
