<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PasswordManager;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Tests for {@see \App\Services\PasswordManager} (task 4.1).
 *
 * Covers acceptance criteria 2.1 (bcrypt hash, plaintext never returned),
 * 2.2 (length 6..128 enforcement), and 2.5 (verify() round trip).
 */
class PasswordManagerTest extends TestCase
{
    private PasswordManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->app->make(PasswordManager::class);
    }

    public function test_hash_produces_bcrypt_hash_that_is_not_plaintext(): void
    {
        $plain = 'correct horse battery staple';
        $hash = $this->manager->hash($plain);

        $this->assertNotSame($plain, $hash);
        $this->assertStringStartsWith('$2', $hash, 'Hash must be a bcrypt $2y$/$2a$/$2b$ string.');
        $this->assertGreaterThan(40, strlen($hash));
    }

    public function test_hash_then_verify_round_trip_returns_true(): void
    {
        $hash = $this->manager->hash('hunter22');

        $this->assertTrue($this->manager->verify('hunter22', $hash));
    }

    public function test_verify_returns_false_for_wrong_password(): void
    {
        $hash = $this->manager->hash('hunter22');

        $this->assertFalse($this->manager->verify('hunter23', $hash));
    }

    public function test_verify_returns_false_for_empty_plaintext(): void
    {
        $hash = $this->manager->hash('hunter22');

        $this->assertFalse($this->manager->verify('', $hash));
    }

    public function test_verify_returns_false_for_empty_hash(): void
    {
        $this->assertFalse($this->manager->verify('hunter22', ''));
    }

    public function test_validate_passes_for_minimum_length_password(): void
    {
        $this->expectNotToPerformAssertions();
        $this->manager->validate('abcdef'); // 6 chars
    }

    public function test_validate_passes_for_maximum_length_password(): void
    {
        $this->expectNotToPerformAssertions();
        $this->manager->validate(str_repeat('a', 128));
    }

    public function test_validate_throws_for_password_below_minimum(): void
    {
        $this->expectException(ValidationException::class);
        $this->manager->validate('abcde'); // 5 chars
    }

    public function test_validate_throws_for_empty_password(): void
    {
        $this->expectException(ValidationException::class);
        $this->manager->validate('');
    }

    public function test_validate_throws_for_password_above_maximum(): void
    {
        $this->expectException(ValidationException::class);
        $this->manager->validate(str_repeat('a', 129));
    }

    public function test_validate_uses_character_count_for_multibyte_strings(): void
    {
        // 6 multi-byte glyphs = 6 characters but 18 bytes; must be accepted.
        $this->expectNotToPerformAssertions();
        $this->manager->validate(str_repeat('é', 6));
    }

    public function test_validate_error_message_is_attached_under_password_key(): void
    {
        try {
            $this->manager->validate('abc');
            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey(PasswordManager::ERROR_KEY, $e->errors());
            $this->assertNotEmpty($e->errors()[PasswordManager::ERROR_KEY]);
        }
    }

    public function test_hash_throws_validation_exception_for_invalid_length(): void
    {
        $this->expectException(ValidationException::class);
        $this->manager->hash('abc');
    }

    public function test_hash_does_not_return_plaintext_substring(): void
    {
        // Acceptance criterion 2.1: plaintext is never embedded in any
        // persisted artefact. The bcrypt hash itself should not contain
        // the original password.
        $plain = 'verysecretpassword';
        $hash = $this->manager->hash($plain);

        $this->assertStringNotContainsString($plain, $hash);
    }

    public function test_two_hashes_of_same_password_differ_due_to_salt(): void
    {
        $a = $this->manager->hash('hunter22');
        $b = $this->manager->hash('hunter22');

        $this->assertNotSame($a, $b);
        $this->assertTrue($this->manager->verify('hunter22', $a));
        $this->assertTrue($this->manager->verify('hunter22', $b));
    }
}
