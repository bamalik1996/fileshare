<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Compare client IP literals with localhost normalisation.
 *
 * On Windows dev hosts `dev.fileshare.test` may resolve to `::1` on one
 * request and `127.0.0.1` on the next, which breaks strict string
 * equality checks used for IP-owned shares and `private-ip.*` channels.
 */
final class IpAddressMatcher
{
    public static function sameHost(?string $a, ?string $b): bool
    {
        if ($a === null || $b === null || $a === '' || $b === '') {
            return false;
        }

        if ($a === $b) {
            return true;
        }

        return self::normalize($a) === self::normalize($b);
    }

    public static function normalize(string $ip): string
    {
        $ip = trim($ip);

        if (self::isLoopback($ip)) {
            return '127.0.0.1';
        }

        return $ip;
    }

    /** Encode an IP for use in a Reverb/Pusher channel segment (no dots/colons). */
    public static function toChannelToken(string $ip): string
    {
        return str_replace(['.', ':'], ['-', '_'], trim($ip));
    }

    /** Decode a channel segment back to an IP literal. */
    public static function fromChannelToken(string $token): string
    {
        return str_replace(['-', '_'], ['.', ':'], $token);
    }

    private static function isLoopback(string $ip): bool
    {
        if ($ip === '127.0.0.1' || $ip === '::1' || $ip === '0:0:0:0:0:0:0:1') {
            return true;
        }

        if (str_starts_with($ip, '127.')) {
            return true;
        }

        return false;
    }
}
