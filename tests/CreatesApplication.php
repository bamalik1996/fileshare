<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

/**
 * Bootstraps the Laravel application for tests.
 *
 * The Laravel 12 default skeleton no longer requires a CreatesApplication
 * trait because Application boot is wired through bootstrap/app.php; tests
 * that extend {@see TestCase} need this method to start a fresh container
 * for each test. Mirrors the pre-Laravel-11 default exactly.
 */
trait CreatesApplication
{
    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
