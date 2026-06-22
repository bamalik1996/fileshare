<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Logging\ScrubSensitiveContextProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

/**
 * Tests for {@see \App\Logging\ScrubSensitiveContextProcessor} (task 4.1).
 *
 * Covers acceptance criterion 2.1: plaintext password and related secrets
 * must not appear in application logs. The processor scrubs `password`
 * and adjacent credential keys from both `context` and `extra` arrays.
 */
class ScrubSensitiveContextProcessorTest extends TestCase
{
    private function record(array $context = [], array $extra = []): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'irrelevant',
            context: $context,
            extra: $extra,
        );
    }

    public function test_password_key_is_redacted_from_context(): void
    {
        $processor = new ScrubSensitiveContextProcessor();
        $record = $this->record(['user' => 'alice', 'password' => 'hunter22']);

        $out = ($processor)($record);

        $this->assertSame('[REDACTED]', $out->context['password']);
        $this->assertSame('alice', $out->context['user']);
    }

    public function test_password_hash_key_is_redacted_from_context(): void
    {
        $processor = new ScrubSensitiveContextProcessor();
        $record = $this->record(['password_hash' => '$2y$10$abc']);

        $out = ($processor)($record);

        $this->assertSame('[REDACTED]', $out->context['password_hash']);
    }

    public function test_e2ee_key_and_key_are_redacted_from_context(): void
    {
        $processor = new ScrubSensitiveContextProcessor();
        $record = $this->record([
            'e2ee_key' => 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
            'key' => 'spilled-secret',
        ]);

        $out = ($processor)($record);

        $this->assertSame('[REDACTED]', $out->context['e2ee_key']);
        $this->assertSame('[REDACTED]', $out->context['key']);
    }

    public function test_match_is_case_insensitive(): void
    {
        $processor = new ScrubSensitiveContextProcessor();
        $record = $this->record([
            'Password' => 'hunter22',
            'PASSWORD_HASH' => '$2y$10$abc',
        ]);

        $out = ($processor)($record);

        $this->assertSame('[REDACTED]', $out->context['Password']);
        $this->assertSame('[REDACTED]', $out->context['PASSWORD_HASH']);
    }

    public function test_nested_arrays_are_scrubbed_recursively(): void
    {
        $processor = new ScrubSensitiveContextProcessor();
        $record = $this->record([
            'request' => [
                'headers' => ['authorization' => 'Bearer xyz'],
                'body' => ['password' => 'hunter22', 'name' => 'Alice'],
            ],
        ]);

        $out = ($processor)($record);

        $this->assertSame('[REDACTED]', $out->context['request']['body']['password']);
        $this->assertSame('Alice', $out->context['request']['body']['name']);
        $this->assertSame('[REDACTED]', $out->context['request']['headers']['authorization']);
    }

    public function test_extra_array_is_scrubbed(): void
    {
        $processor = new ScrubSensitiveContextProcessor();
        $record = $this->record([], ['password' => 'hunter22', 'request_id' => 'r-1']);

        $out = ($processor)($record);

        $this->assertSame('[REDACTED]', $out->extra['password']);
        $this->assertSame('r-1', $out->extra['request_id']);
    }

    public function test_record_with_no_sensitive_keys_is_returned_unchanged(): void
    {
        $processor = new ScrubSensitiveContextProcessor();
        $record = $this->record(['user' => 'alice', 'count' => 3]);

        $out = ($processor)($record);

        $this->assertSame($record, $out);
    }

    public function test_message_is_not_modified(): void
    {
        $processor = new ScrubSensitiveContextProcessor();
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'login failed for user',
            context: ['password' => 'hunter22'],
        );

        $out = ($processor)($record);

        $this->assertSame('login failed for user', $out->message);
    }

    public function test_extra_keys_constructor_argument_extends_redaction_set(): void
    {
        $processor = new ScrubSensitiveContextProcessor(['custom_secret']);
        $record = $this->record(['custom_secret' => 'shh', 'public' => 'ok']);

        $out = ($processor)($record);

        $this->assertSame('[REDACTED]', $out->context['custom_secret']);
        $this->assertSame('ok', $out->context['public']);
    }

    public function test_non_string_keys_are_left_alone(): void
    {
        // Numeric-keyed lists (e.g. positional placeholders) should pass
        // through untouched even if they contain values that look like
        // passwords; only named keys are matched.
        $processor = new ScrubSensitiveContextProcessor();
        $record = $this->record([0 => 'hunter22', 1 => 'public']);

        $out = ($processor)($record);

        $this->assertSame('hunter22', $out->context[0]);
        $this->assertSame('public', $out->context[1]);
    }
}
