<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Thrown when a read attempt encounters a Share whose `expires_at <= now()`.
 *
 * Acceptance criteria 3.4 and 3.8 require the read path to return HTTP 404
 * for expired shares regardless of any other validation issues, and to
 * delete the expired record (and its media) before the response is
 * returned. Extending {@see HttpException} with status 404 lets Laravel's
 * {@see \App\Exceptions\Handler::render()} map this exception straight to
 * the 404 view; the deletion side-effect is performed by the throwing
 * code (see {@see \App\Services\ExpiryManager::enforceOnRead()}) before
 * this exception escapes.
 *
 * The exception body is intentionally generic ("Share not found.") so that
 * an expired share is indistinguishable from a never-existed share to the
 * client, matching the existing 404 view's wording.
 */
class ShareExpiredException extends HttpException
{
    public function __construct(string $message = 'Share not found.', ?Throwable $previous = null)
    {
        parent::__construct(statusCode: 404, message: $message, previous: $previous);
    }
}
