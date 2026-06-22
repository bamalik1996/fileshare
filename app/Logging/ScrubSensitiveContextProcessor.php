<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Monolog processor that redacts sensitive credential fields from log
 * context and extra arrays before they are formatted or written.
 *
 * Required by Requirement 2 ("plaintext password never persisted to logs")
 * and Requirement 15 (E2EE keys must never reach server-side storage).
 *
 * The processor recurses through nested arrays so structured payloads such
 * as request bodies attached to exception reports are scrubbed wholesale.
 *
 * Keys whose names match the redaction list (case-insensitive) are replaced
 * with the string "[REDACTED]" rather than removed; this keeps the key
 * present so log readers can still see that a sensitive field existed
 * without ever seeing its value.
 *
 * Wire-up lives in {@see \App\Logging\LogTap} via the `tap` mechanism on
 * each channel in `config/logging.php`.
 */
class ScrubSensitiveContextProcessor implements ProcessorInterface
{
    /**
     * Default redaction list. Lower-cased; matched case-insensitively.
     *
     * @var list<string>
     */
    public const DEFAULT_KEYS = [
        'password',
        'password_confirmation',
        'password_hash',
        'current_password',
        'new_password',
        'old_password',
        'e2ee_key',
        'key',
        'secret',
        'token',
        'api_key',
        'authorization',
    ];

    public const REDACTED_PLACEHOLDER = '[REDACTED]';

    /** @var array<string,true> Lower-cased lookup table. */
    private array $redactionSet;

    /**
     * @param list<string>|null $extraKeys Additional keys to scrub beyond
     *                                     the defaults. Names are matched
     *                                     case-insensitively.
     */
    public function __construct(?array $extraKeys = null)
    {
        $keys = self::DEFAULT_KEYS;
        if ($extraKeys !== null) {
            $keys = array_merge($keys, $extraKeys);
        }

        $this->redactionSet = [];
        foreach ($keys as $key) {
            $this->redactionSet[strtolower($key)] = true;
        }
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $this->scrub($record->context);
        $extra = $this->scrub($record->extra);

        if ($context === $record->context && $extra === $record->extra) {
            return $record;
        }

        return $record->with(context: $context, extra: $extra);
    }

    /**
     * Recursively walk an array, replacing values whose key matches the
     * redaction list with {@see self::REDACTED_PLACEHOLDER}.
     *
     * @param array<mixed,mixed> $data
     * @return array<mixed,mixed>
     */
    private function scrub(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && isset($this->redactionSet[strtolower($key)])) {
                $data[$key] = self::REDACTED_PLACEHOLDER;
                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->scrub($value);
            }
        }

        return $data;
    }
}
