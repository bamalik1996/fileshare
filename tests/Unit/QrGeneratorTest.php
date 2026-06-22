<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\QrGenerationException;
use App\Services\QrGenerator;
use BaconQrCode\Encoder\QrCode;
use BaconQrCode\Renderer\RendererInterface;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

/**
 * Tests for {@see \App\Services\QrGenerator} (task 7.1).
 *
 * Covers acceptance criteria 1.1 (QR is produced for a Share URL),
 * 1.2 (output is at least 200x200 pixels), 1.5 (failures surface as a
 * single typed exception suitable for fallback rendering), and 1.6
 * (original failure reason is preserved for logging via
 * {@see QrGenerationException::getPrevious()}).
 */
class QrGeneratorTest extends TestCase
{
    public function test_generate_returns_png_bytes_for_a_share_url(): void
    {
        $generator = new QrGenerator();

        $png = $generator->generate('https://airtoshare.test/s/abc123');

        // PNG signature: \x89 P N G \r \n \x1A \n
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($png, 0, 8));
        $this->assertGreaterThan(0, strlen($png));
    }

    public function test_generated_png_is_at_least_two_hundred_pixels_per_side(): void
    {
        // Acceptance criterion 1.2: minimum rendered size is 200x200 px.
        // The default size is 240 px; we verify the IHDR chunk in the PNG.
        $generator = new QrGenerator();

        $png = $generator->generate('https://airtoshare.test/s/abc123');

        [$width, $height] = $this->readPngDimensions($png);

        $this->assertGreaterThanOrEqual(QrGenerator::MIN_SIZE, $width);
        $this->assertGreaterThanOrEqual(QrGenerator::MIN_SIZE, $height);
    }

    public function test_generated_png_uses_default_size_of_two_hundred_forty(): void
    {
        $generator = new QrGenerator();

        $png = $generator->generate('https://airtoshare.test/s/abc123');

        [$width, $height] = $this->readPngDimensions($png);

        $this->assertSame(QrGenerator::DEFAULT_SIZE, $width);
        $this->assertSame(QrGenerator::DEFAULT_SIZE, $height);
    }

    public function test_generate_honours_a_custom_size_at_or_above_the_minimum(): void
    {
        $generator = new QrGenerator(size: 300);

        $png = $generator->generate('https://airtoshare.test/s/abc');

        [$width, $height] = $this->readPngDimensions($png);

        $this->assertSame(300, $width);
        $this->assertSame(300, $height);
    }

    public function test_constructor_rejects_a_size_below_the_minimum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new QrGenerator(size: 100);
    }

    public function test_generate_rejects_an_empty_url(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new QrGenerator())->generate('');
    }

    public function test_generate_or_fail_returns_png_bytes_on_success(): void
    {
        $generator = new QrGenerator();

        $png = $generator->generateOrFail('https://airtoshare.test/s/abc123');

        $this->assertSame("\x89PNG\r\n\x1a\n", substr($png, 0, 8));
    }

    public function test_generate_or_fail_wraps_empty_url_failure_in_qr_generation_exception(): void
    {
        $generator = new QrGenerator();

        try {
            $generator->generateOrFail('');
            $this->fail('QrGenerationException was not thrown.');
        } catch (QrGenerationException $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e->getPrevious());
            $this->assertNotSame('', $e->getMessage());
        }
    }

    public function test_generate_or_fail_wraps_renderer_failures_and_preserves_previous(): void
    {
        // Acceptance criterion 1.6: the original failure reason must reach
        // the logger. The renderer exception is preserved as `previous`.
        $renderer = new class implements RendererInterface
        {
            public function render(QrCode $qrCode): string
            {
                throw new RuntimeException('GD allocation failed');
            }
        };

        $generator = new QrGenerator(renderer: $renderer);

        try {
            $generator->generateOrFail('https://airtoshare.test/s/xyz');
            $this->fail('QrGenerationException was not thrown.');
        } catch (QrGenerationException $e) {
            $this->assertInstanceOf(RuntimeException::class, $e->getPrevious());
            $this->assertSame('GD allocation failed', $e->getPrevious()->getMessage());
            $this->assertStringContainsString('GD allocation failed', $e->getMessage());
        }
    }

    public function test_generate_handles_long_share_urls(): void
    {
        // Acceptance criterion 1.3: URLs longer than 256 chars must still
        // round-trip; here we at least ensure the encoder accepts them and
        // emits a valid PNG. Decoder round-trip is covered by the property
        // test in task 7.4.
        $url = 'https://airtoshare.test/s/' . str_repeat('a', 250);

        $png = (new QrGenerator())->generate($url);

        $this->assertSame("\x89PNG\r\n\x1a\n", substr($png, 0, 8));
    }

    /**
     * Read the width and height from the PNG IHDR chunk.
     *
     * The PNG specification places IHDR immediately after the 8-byte
     * signature: 4 bytes length, 4 bytes type ("IHDR"), 4 bytes width,
     * 4 bytes height (big-endian unsigned 32-bit integers).
     *
     * @return array{0: int, 1: int}
     */
    private function readPngDimensions(string $png): array
    {
        $this->assertGreaterThanOrEqual(24, strlen($png), 'PNG too short to contain IHDR.');
        $this->assertSame('IHDR', substr($png, 12, 4));

        /** @var array{width: int, height: int} $unpacked */
        $unpacked = unpack('Nwidth/Nheight', substr($png, 16, 8));

        return [$unpacked['width'], $unpacked['height']];
    }
}
