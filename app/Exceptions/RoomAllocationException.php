<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown by {@see \App\Services\RoomService::create()} when a unique 6-
 * character Room Code cannot be allocated within the allowed retry budget.
 *
 * Acceptance criterion 7.2 mandates that the Room_Service retries
 * generation up to five times on collision before returning an error
 * indicating that a Room Code could not be allocated. This exception is
 * the canonical "could not be allocated" signal: callers (controllers,
 * the v2 API, tests) catch it and surface a service-level error to the
 * user without leaking internals.
 *
 * Probability of hitting this branch is astronomical with the configured
 * 32-character alphabet (32^6 ≈ 1.07e9 codes); five consecutive collisions
 * therefore signal either a database/driver fault or a deliberately
 * exhausted code space. Both are operational concerns worth flagging
 * loudly, hence a dedicated exception type rather than a generic
 * RuntimeException.
 *
 * The exception body is intentionally generic so it is safe to surface in
 * HTTP responses; structured detail (e.g. how many retries were attempted)
 * is carried by the underlying logger context written in
 * {@see \App\Services\RoomService::create()}.
 */
class RoomAllocationException extends RuntimeException
{
    public function __construct(
        string $message = 'Could not allocate a unique Room Code.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
