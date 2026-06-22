<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UploadChunk model (design.md > Data Models > upload_chunks).
 *
 * Backs Requirement 9 (Chunked and resumable uploads). Each row records a
 * single received chunk; the composite unique key
 * `(session_id, chunk_index)` enforces idempotent re-uploads of the same
 * (session, index) pair when the SHA-256 matches (Requirement 9.7).
 *
 * Only `created_at` is tracked at the schema level (no `updated_at`),
 * because chunks are immutable once received.
 *
 * @property int $session_id
 * @property int $chunk_index
 * @property string $sha256
 * @property int $size_bytes
 * @property string $stored_path  `chunks/{session_uuid}/{index}.bin`
 * @property ?\Illuminate\Support\Carbon $created_at
 */
class UploadChunk extends Model
{
    /**
     * Schema only stores `created_at`, not `updated_at`.
     */
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'session_id',
        'chunk_index',
        'sha256',
        'size_bytes',
        'stored_path',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'chunk_index' => 'integer',
        'size_bytes' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Owning {@see UploadSession}.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(UploadSession::class, 'session_id');
    }
}
