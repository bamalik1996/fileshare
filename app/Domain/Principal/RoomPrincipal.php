<?php

declare(strict_types=1);

namespace App\Domain\Principal;

/**
 * Principal identified by a Room (Requirement 7).
 *
 * The identifier is the `rooms.id` foreign key, normalised to a string so it
 * can share the `shares.owner_id` column with IP literals and account ids.
 */
final class RoomPrincipal implements Principal
{
    public function __construct(private readonly int|string $roomId)
    {
    }

    public function type(): string
    {
        return 'room';
    }

    public function identifier(): string
    {
        return (string) $this->roomId;
    }
}
