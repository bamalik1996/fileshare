<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * MediaScan model (design.md > Data Models > media_scans).
 *
 * Backs Requirement 20 (Virus scanner). One row per Spatie media row,
 * keyed by `media_uuid` (which references `media.uuid`, not its primary
 * key). The status column drives the download gate in
 * `MediaController::download()`:
 *
 *  - `pending`     → 425 Too Early
 *  - `clean`       → 200 with file
 *  - `infected`    → 451 Unavailable For Legal Reasons
 *  - `error`       → 503 Service Unavailable
 *  - `skipped_e2ee`→ 200 with unscanned-media notice (Requirement 15.7)
 *
 * `result_payload` is JSON for backend-specific data: ClamAV's exit details
 * or VirusTotal's `last_analysis_stats`.
 *
 * The schema only writes `queued_at` (and optionally `scanned_at`); there
 * are no Eloquent timestamp columns, so timestamps are disabled.
 *
 * @property string $media_uuid
 * @property string $status
 * @property string $backend
 * @property int $retry_count
 * @property ?array $result_payload
 * @property \Illuminate\Support\Carbon $queued_at
 * @property ?\Illuminate\Support\Carbon $scanned_at
 */
class MediaScan extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CLEAN = 'clean';
    public const STATUS_INFECTED = 'infected';
    public const STATUS_ERROR = 'error';
    public const STATUS_SKIPPED_E2EE = 'skipped_e2ee';

    public const BACKEND_CLAMAV = 'clamav';
    public const BACKEND_VIRUSTOTAL = 'virustotal';

    /**
     * Schema does not include the standard `created_at`/`updated_at` columns,
     * so disable Eloquent's automatic timestamp management.
     */
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'media_uuid',
        'status',
        'backend',
        'retry_count',
        'result_payload',
        'queued_at',
        'scanned_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'retry_count' => 'integer',
        'result_payload' => 'array',
        'queued_at' => 'datetime',
        'scanned_at' => 'datetime',
    ];

    /**
     * Spatie media row this scan belongs to.
     *
     * The schema does not declare a foreign key (Spatie's media table
     * predates the FK style used here), so we wire the relation manually
     * via `media.uuid`.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_uuid', 'uuid');
    }

    /**
     * Convenience scope for the download gate.
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
