<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single analytics event (a view or a download) recorded against a Share.
 *
 * Rows are immutable append-only records consumed by the account analytics
 * dashboard. The table deliberately stores no raw IP address - only a salted,
 * truncated hash (see {@see \App\Services\AnalyticsService}).
 *
 * @property int $id
 * @property int $share_id
 * @property ?string $media_uuid
 * @property string $event_type
 * @property ?string $ip_hash
 * @property ?string $country
 * @property ?string $city
 * @property ?string $device
 * @property ?string $browser
 * @property ?string $os
 * @property ?string $referrer
 * @property \Illuminate\Support\Carbon $created_at
 */
class ShareEvent extends Model
{
    public const EVENT_VIEW = 'view';

    public const EVENT_DOWNLOAD = 'download';

    /**
     * Events only carry a creation timestamp; they are never updated.
     */
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'share_id',
        'media_uuid',
        'event_type',
        'ip_hash',
        'country',
        'city',
        'device',
        'browser',
        'os',
        'referrer',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function share(): BelongsTo
    {
        return $this->belongsTo(Share::class);
    }
}
