<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * UploadSession model (design.md > Data Models > upload_sessions).
 *
 * Backs Requirement 9 (Chunked and resumable uploads). One row per in-flight
 * upload; rows are deleted by `CleanupExpiredUploadSessions` 24 hours after
 * the first chunk if the session has not completed (Requirement 9.8).
 *
 * `share_id` is nullable because a session may be started before its target
 * Share row exists (e.g. drafts created from the upload page); the FK is
 * filled in by `ChunkedUploadService::assemble()`.
 *
 * @property string $uuid
 * @property ?int $share_id
 * @property string $filename
 * @property string $mime
 * @property int $total_bytes
 * @property int $total_chunks
 * @property ?\Illuminate\Support\Carbon $first_chunk_at
 * @property ?\Illuminate\Support\Carbon $completed_at
 */
class UploadSession extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'share_id',
        'filename',
        'mime',
        'total_bytes',
        'total_chunks',
        'first_chunk_at',
        'completed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'total_bytes' => 'integer',
        'total_chunks' => 'integer',
        'first_chunk_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Auto-populate `uuid` so callers don't have to remember.
     */
    protected static function booted(): void
    {
        static::creating(function (self $session): void {
            if (empty($session->uuid)) {
                $session->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Share that will receive the assembled file once
     * {@see \App\Services\ChunkedUploadService::assemble()} runs. Nullable
     * for in-progress sessions that haven't been bound to a Share yet.
     */
    public function share(): BelongsTo
    {
        return $this->belongsTo(Share::class);
    }

    /**
     * Chunks belonging to this session. Cascade delete is enforced by the
     * FK on `upload_chunks.session_id` (see migration).
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(UploadChunk::class, 'session_id')
            ->orderBy('chunk_index');
    }

    /**
     * True when every chunk has been received and assembly has run.
     * Mirrors the read-time guard in `ChunkedUploadController`.
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
