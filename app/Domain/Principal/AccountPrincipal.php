<?php

declare(strict_types=1);

namespace App\Domain\Principal;

/**
 * Principal identified by an Account (Requirement 16).
 *
 * The identifier is the `accounts.id` foreign key, normalised to a string so
 * it can share the `shares.owner_id` column with IP literals and room ids.
 */
final class AccountPrincipal implements Principal
{
    public function __construct(private readonly int|string $accountId)
    {
    }

    public function type(): string
    {
        return 'account';
    }

    public function identifier(): string
    {
        return (string) $this->accountId;
    }
}
