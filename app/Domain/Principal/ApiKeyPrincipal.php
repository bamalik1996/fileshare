<?php

declare(strict_types=1);

namespace App\Domain\Principal;

/**
 * Principal identified by an API Key acting on behalf of its owning Account
 * (Requirement 18.5).
 *
 * Per design.md > Architecture > Authentication and Authorisation Layers,
 * a successful API-key authentication on `/api/v2/*` "resolves principal =
 * ApiKey.account" which then drives the same per-Account limits a session
 * login would. The persisted owner-type token is therefore `account`,
 * exactly matching {@see AccountPrincipal::type()}: shares created via the
 * REST API are owned by the Account and indistinguishable on the data
 * layer from shares created through the web session (Requirement 16.4
 * applies to both paths).
 *
 * The originating API key id is retained in the value object so audit and
 * logging code can record which key acted on the request without needing a
 * separate lookup. It is intentionally not part of the `identifier()`
 * payload because that would split a single Account's content across
 * multiple `shares.owner_id` values.
 */
final class ApiKeyPrincipal implements Principal
{
    public function __construct(
        private readonly int|string $accountId,
        private readonly int|string $apiKeyId,
    ) {
    }

    /**
     * Owner-type token persisted in `shares.owner_type`. Always `account`
     * — see class doc comment for why it is not a dedicated `api_key`
     * type.
     */
    public function type(): string
    {
        return 'account';
    }

    /**
     * The owning Account's primary key, normalised to a string so it can
     * share the polymorphic `shares.owner_id` column with IP literals and
     * room ids.
     */
    public function identifier(): string
    {
        return (string) $this->accountId;
    }

    /**
     * Identifier of the API key that authenticated the request. Read by
     * audit logging and by `last_used_at` bookkeeping in `ApiKeyAuth`
     * (task 25.2). Not part of the {@see Principal} contract.
     */
    public function apiKeyId(): string
    {
        return (string) $this->apiKeyId;
    }
}
