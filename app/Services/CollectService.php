<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Principal\AccountPrincipal;
use App\Models\Account;
use App\Models\Share;
use Illuminate\Support\Str;

/**
 * File-request / "collect" inbox management (SaaS features, Phase 3).
 *
 * A collect Share is an ordinary Account-owned Share flagged with
 * `is_collect`. Recipients (no account required) upload files to it through
 * the public /collect/{slug} page; the files land on the owner's Share and
 * show up in their dashboard like any other media.
 */
class CollectService
{
    private const SLUG_LENGTH = 12;

    private const MAX_RETRIES = 5;

    /**
     * Expiry window for a new collect inbox. Collect links are typically
     * filled over several days, so we use the Account maximum.
     */
    private const DEFAULT_EXPIRY = '30d';

    public function __construct(
        private readonly ShareService $shareService,
    ) {
    }

    /**
     * Create a new collect inbox owned by the given Account.
     */
    public function createForAccount(Account $account, ?string $title = null, ?string $instructions = null): Share
    {
        $share = $this->shareService->createForPrincipal(
            new AccountPrincipal((int) $account->getKey()),
            ['expiry' => self::DEFAULT_EXPIRY],
        );

        $share->is_collect = true;
        $share->collect_slug = $this->generateSlug();
        $share->collect_title = $this->clean($title, 115);
        $share->collect_instructions = $this->clean($instructions, 495);
        $share->save();

        return $share;
    }

    /**
     * Resolve a collect inbox by slug that is currently accepting uploads.
     */
    public function findOpenBySlug(string $slug): ?Share
    {
        $share = Share::query()
            ->where('collect_slug', $slug)
            ->where('is_collect', true)
            ->first();

        if ($share === null || ! $share->acceptsCollectUploads()) {
            return null;
        }

        return $share;
    }

    private function generateSlug(): string
    {
        for ($attempt = 0; $attempt < self::MAX_RETRIES; $attempt++) {
            $slug = Str::random(self::SLUG_LENGTH);
            if (! Share::query()->where('collect_slug', $slug)->exists()) {
                return $slug;
            }
        }

        throw new \RuntimeException('Could not allocate a unique collect slug.');
    }

    private function clean(?string $value, int $limit): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : Str::limit($value, $limit, '');
    }
}
