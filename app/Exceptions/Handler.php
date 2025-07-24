<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Handle 404 errors
        if ($this->isHttpException($exception) && $exception->getStatusCode() === 404) {
            return response()->view('errors.404', [], 404);
        }

        // Handle 400 Bad Request errors
        if ($this->isHttpException($exception) && $exception->getStatusCode() === 400) {
            return response()->view('errors.400', [], 400);
        }

        // Handle 500 Internal Server errors
        if ($this->isHttpException($exception) && $exception->getStatusCode() === 500) {
            return response()->view('errors.500', [], 500);
        }

        // Handle other 5xx server errors
        if ($this->isHttpException($exception) && $exception->getStatusCode() >= 500) {
            return response()->view('errors.500', [], $exception->getStatusCode());
        }

        return parent::render($request, $exception);
    }
}
