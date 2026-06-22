<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown by {@see \App\Services\QrGenerator::generateOrFail()} when a QR
 * code cannot be produced for the requested URL.
 *
 * Acceptance criterion 1.5 requires the application to render a fallback
 * (the Share URL as text plus an error banner) instead of offering a PNG
 * download whenever generation fails. Acceptance criterion 1.6 requires
 * the failure to be logged with the Share identifier and the reason.
 *
 * The QrGenerator wraps every underlying error (BaconQrCode writer
 * exceptions, GD failures, invalid input) into this single exception type
 * so the controller / view layer has exactly one branch to handle and the
 * `previous` exception preserves the original reason for logging.
 *
 * The exception body is intentionally generic (no Share URL, no stack
 * trace) so it is safe to surface in HTTP responses; structured detail is
 * carried by {@see self::getPrevious()} for log enrichment.
 */
class QrGenerationException extends RuntimeException
{
    public function __construct(
        string $message = 'Failed to generate QR code.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
