<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Share;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Public gallery slug management (Requirement 17).
 */
class PublicGalleryService
{
    private const SLUG_LENGTH = 12;

    private const MAX_RETRIES = 5;

    private const TOMBSTONE_TTL = 60;

    public function enable(Share $share): string
    {
        if (is_string($share->public_slug) && $share->public_slug !== '') {
            $this->rememberSlug($share);

            return $share->public_slug;
        }

        for ($attempt = 0; $attempt < self::MAX_RETRIES; $attempt++) {
            $slug = $this->generateSlug();
            if (! Share::query()->where('public_slug', $slug)->exists()) {
                $share->public_slug = $slug;
                $share->save();
                $this->rememberSlug($share);

                return $slug;
            }
        }

        throw new \RuntimeException('Could not allocate a unique public slug.');
    }

    public function disable(Share $share): void
    {
        $oldSlug = $share->public_slug;
        $share->public_slug = null;
        $share->save();

        if (is_string($oldSlug) && $oldSlug !== '') {
            Cache::put($this->cacheKey($oldSlug), 'tombstone', self::TOMBSTONE_TTL);
        }

        Cache::forget($this->cacheKey((string) $share->uuid));
    }

    public function findBySlug(string $slug): ?Share
    {
        $cached = Cache::get($this->cacheKey($slug));
        if ($cached === 'tombstone') {
            return null;
        }

        if (is_int($cached)) {
            $share = Share::query()->find($cached);

            return $share instanceof Share && $share->public_slug === $slug ? $share : null;
        }

        $share = Share::query()
            ->where('public_slug', $slug)
            ->where('expires_at', '>', now())
            ->first();

        if ($share !== null) {
            $this->rememberSlug($share);
        }

        return $share;
    }

    private function generateSlug(): string
    {
        return Str::random(self::SLUG_LENGTH);
    }

    private function rememberSlug(Share $share): void
    {
        if ($share->public_slug) {
            Cache::put($this->cacheKey($share->public_slug), (int) $share->id, 3600);
        }
    }

    private function cacheKey(string $slug): string
    {
        return 'public:slug:' . $slug;
    }
}
