<?php

declare(strict_types=1);

namespace App\Domain\Principal;

/**
 * Principal identified solely by IP address (the default guest mode).
 *
 * Unlike {@see RoomPrincipal} and {@see AccountPrincipal}, an IP principal
 * has no backing Eloquent model — it is a pure value object. The IP literal
 * is stored verbatim in `shares.owner_id` (e.g. `"192.168.1.10"`).
 */
final class IpPrincipal implements Principal
{
    public function __construct(private readonly string $ip)
    {
    }

    public function type(): string
    {
        return 'ip';
    }

    public function identifier(): string
    {
        return $this->ip;
    }
}
