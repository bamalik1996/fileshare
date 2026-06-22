<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Share;
use App\Models\ShareNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Pre-expiry notification scheduler (Requirement 11).
 */
class NotificationService
{
    /**
     * Arm reminder rows for every opted-in channel on this Share.
     *
     * Cycle id = the share's current {@see Share::$expires_at} value.
     */
    public function arm(Share $share): void
    {
        $cycle = $share->expires_at;

        if ($cycle === null) {
            return;
        }

        foreach ($this->optedInChannels($share) as $channel) {
            $sendAt = $cycle->copy()->subHour();

            if ($sendAt->lte(Carbon::now())) {
                $sendAt = Carbon::now();
            }

            ShareNotification::query()->updateOrCreate(
                [
                    'share_id'          => $share->id,
                    'cycle_expires_at'  => $cycle,
                    'channel'           => $channel,
                ],
                [
                    'send_at'        => $sendAt,
                    'sent_at'        => null,
                    'failure_count'  => 0,
                ],
            );
        }
    }

    /**
     * Cancel all pending reminders for a Share (Requirement 11.8).
     */
    public function cancelFor(Share $share): void
    {
        ShareNotification::query()
            ->where('share_id', $share->id)
            ->whereNull('sent_at')
            ->delete();
    }

    /**
     * Re-arm reminders after an expiry change (Requirement 11.4).
     */
    public function rearmOnExpiryChange(Share $share, ?Carbon $previousExpiry): void
    {
        if ($previousExpiry !== null) {
            ShareNotification::query()
                ->where('share_id', $share->id)
                ->where('cycle_expires_at', $previousExpiry)
                ->whereNull('sent_at')
                ->delete();
        }

        $this->arm($share);
    }

    /**
     * @return list<string>
     */
    private function optedInChannels(Share $share): array
    {
        $channels = [];

        if ($share->notify_browser) {
            $channels[] = ShareNotification::CHANNEL_BROWSER;
        }

        if ($share->notify_email && $this->resolveNotifyEmail($share) !== null) {
            $channels[] = ShareNotification::CHANNEL_EMAIL;
        }

        return $channels;
    }

    public function resolveNotifyEmail(Share $share): ?string
    {
        if ($share->notify_email_address !== null && $share->notify_email_address !== '') {
            return $share->notify_email_address;
        }

        if ($share->owner_type === Share::OWNER_TYPE_ACCOUNT) {
            $account = Account::query()->find($share->owner_id);

            return $account?->email;
        }

        return null;
    }

    /**
     * Resolve the IP address for browser-channel delivery to IP-only owners.
     */
    public function resolveOwnerIp(Share $share): ?string
    {
        if ($share->owner_type === Share::OWNER_TYPE_IP) {
            return (string) $share->owner_id;
        }

        return null;
    }

    public function logArmFailure(Share $share, string $reason): void
    {
        Log::warning('NotificationService: failed to arm reminders', [
            'share_id' => $share->id,
            'reason'   => $reason,
        ]);
    }
}
