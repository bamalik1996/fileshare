<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Log\Logger as IlluminateLogger;
use Monolog\Logger as MonologLogger;

/**
 * Laravel logging "tap" that pushes the
 * {@see ScrubSensitiveContextProcessor} onto every Monolog handler used by
 * the channel it is attached to.
 *
 * Configured in `config/logging.php` via the `tap` array on each channel:
 *
 *     'single' => [
 *         'driver' => 'single',
 *         'tap'    => [\App\Logging\ScrubSensitiveContextTap::class],
 *         ...
 *     ],
 *
 * The tap receives the framework's {@see IlluminateLogger} wrapper. We pull
 * the underlying Monolog instance with getLogger(), then push our processor
 * so it runs before any handler formats the record.
 */
class ScrubSensitiveContextTap
{
    public function __invoke(IlluminateLogger $logger): void
    {
        $monolog = $logger->getLogger();

        if ($monolog instanceof MonologLogger) {
            $monolog->pushProcessor(new ScrubSensitiveContextProcessor());
        }
    }
}
