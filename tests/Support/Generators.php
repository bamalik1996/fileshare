<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Shared eris generators for the fileshare-enhancements-bundle property tests.
 *
 * This is a placeholder scaffold introduced by task 1.3. Each generator below
 * is implemented just enough to compile against `giorgiosironi/eris` once the
 * dependency is added (task 1.1). Property tests added in later tasks will
 * fill these in to match the calibration described in design.md
 * "Testing Strategy → Property Test Configuration".
 *
 * Calibration targets (from design.md):
 * - URL generator (Property 1): valid HTTPS URLs of length 1..2048.
 * - Password generator (Property 2): printable strings of length 6..128.
 * - Bytes generator (Property 24): byte buffers of length 5 MB..50 MB
 *   (the 500 MB upper bound is exercised by a single manual integration test).
 * - Markdown generator (Property 37): random CommonMark plus adversarial
 *   constructions (raw <script>, <iframe>, broken UTF-8, very long lines).
 * - Room code generator (Property 15): 6-char strings over the alphabet
 *   `ABCDEFGHJKLMNPQRSTUVWXYZ23456789`.
 * - Expiry option generator (Property 7): one of `1h`, `6h`, `24h`, `7d`,
 *   plus `30d` when the principal is an Account.
 *
 * NOTE: Concrete return types are intentionally left open (`mixed`) so that
 * we do not import eris symbols before the dependency is installed. Each
 * method will be retyped to `\Eris\Generator` once the package is in place.
 */
final class Generators
{
    private function __construct()
    {
        // Static-only utility class.
    }

    /**
     * Random valid HTTPS URLs of length 1..2048 (Property 1).
     */
    public static function httpsUrl(): mixed
    {
        throw new \LogicException(
            'Generators::httpsUrl() is not yet implemented. '
            . 'Add the eris dependency (task 1.1) and wire the URL generator '
            . 'as part of Property 1 (task 7.4).'
        );
    }

    /**
     * Random printable passwords of length 6..128 (Property 2).
     */
    public static function password(): mixed
    {
        throw new \LogicException(
            'Generators::password() is not yet implemented. '
            . 'Wire under tasks 4.2 and 22.4.'
        );
    }

    /**
     * One of the allowed expiry options (Property 7).
     *
     * @param  bool  $accountPrincipal  Include `30d` when true.
     */
    public static function expiryOption(bool $accountPrincipal = false): mixed
    {
        throw new \LogicException(
            'Generators::expiryOption() is not yet implemented. '
            . 'Wire under task 3.2.'
        );
    }

    /**
     * Random byte buffers for chunked-upload property tests (Property 24).
     *
     * @param  int  $minBytes  Inclusive lower bound (default 5 MB).
     * @param  int  $maxBytes  Inclusive upper bound (default 50 MB).
     */
    public static function bytes(int $minBytes = 5 * 1024 * 1024, int $maxBytes = 50 * 1024 * 1024): mixed
    {
        throw new \LogicException(
            'Generators::bytes() is not yet implemented. '
            . 'Wire under tasks 14.5 and 14.6.'
        );
    }

    /**
     * Random CommonMark sources plus adversarial constructions (Property 37).
     */
    public static function markdownSource(): mixed
    {
        throw new \LogicException(
            'Generators::markdownSource() is not yet implemented. '
            . 'Wire under task 16.4.'
        );
    }

    /**
     * 6-character room codes from the unambiguous alphabet (Property 15).
     */
    public static function roomCode(): mixed
    {
        throw new \LogicException(
            'Generators::roomCode() is not yet implemented. '
            . 'Wire under task 12.4.'
        );
    }
}
