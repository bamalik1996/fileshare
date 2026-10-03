<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AccountService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Permanently purge accounts whose 48-hour deletion grace period has elapsed.
 */
class PurgeScheduledAccountDeletions extends Command
{
    protected $signature = 'accounts:purge-scheduled-deletions';

    protected $description = 'Permanently delete accounts whose scheduled deletion grace period has elapsed.';

    public function handle(AccountService $accounts): int
    {
        $purged = $accounts->purgeScheduledDeletions();

        $message = sprintf('Purged %d scheduled account deletion(s).', $purged);
        $this->info($message);
        Log::info($message, ['purged' => $purged]);

        return self::SUCCESS;
    }
}
