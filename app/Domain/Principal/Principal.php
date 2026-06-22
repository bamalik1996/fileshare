<?php

declare(strict_types=1);

namespace App\Domain\Principal;

/**
 * A Principal identifies the owner of a Share.
 *
 * Three concrete kinds exist (per design.md > Architecture > Authentication
 * and Authorisation Layers, and the `shares.owner_type` enum):
 *
 *  - `ip`      → {@see IpPrincipal}      (default for unauthenticated guests)
 *  - `room`    → {@see RoomPrincipal}    (Requirement 7: Room codes)
 *  - `account` → {@see AccountPrincipal} (Requirement 16: Accounts)
 *
 * The `type()` value is exactly the token persisted in `shares.owner_type`
 * and emitted on broadcast payloads.
 *
 * `identifier()` returns the value persisted in `shares.owner_id`. Because
 * `owner_id` is a single string column shared across all owner kinds, the
 * identifier is normalised to a string here too: an IP literal for IP,
 * the room id for Room, the account id for Account.
 */
interface Principal
{
    /**
     * Owner-type token persisted in `shares.owner_type`.
     *
     * @return string One of `'ip'`, `'room'`, `'account'`.
     */
    public function type(): string;

    /**
     * Owner identifier persisted in `shares.owner_id`.
     */
    public function identifier(): string;
}
