<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\QrGenerationException;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\RendererInterface;
use BaconQrCode\Writer;
use InvalidArgumentException;
use Throwable;

/**
 * QR_Generator service (design.md > Components and Interfaces > 1).
 *
 * Produces PNG bytes encoding a Share's HTTPS URL using the pure-PHP
 * `bacon/bacon-qr-code` library. Acceptance criteria covered:
 *
 *   1.1 - a QR code is produced for a given URL within the request cycle;
 *         performance is bounded by BaconQrCode + GD which renders a
 *         240x240 PNG in single-digit milliseconds.
 *   1.2 - rendered output is at least 200x200 pixels. The default size of
 *         240 pixels matches the design's "comfortable margin" guidance
 *         and the {@see self::MIN_SIZE} guard prevents under-sized
 *         configurations from sneaking in via tests or DI overrides.
 *   1.5 - {@see self::generateOrFail()} surfaces a single, well-typed
 *         exception that callers can catch and turn into the URL-text +
 *         error-banner fallback view.
 *   1.6 - the original failure cause is preserved as
 *         {@see QrGenerationException::getPrevious()} so the controller
 *         can log `share_id` + reason without losing the underlying
 *         BaconQrCode / GD error message.
 *
 * Round-trip correctness for arbitrary URL lengths (1.3) is delegated to
 * BaconQrCode's encoder, which automatically picks the QR version big
 * enough to fit the payload up to its 4296-byte alphanumeric / 2953-byte
 * binary cap; URLs longer than that will throw, which is the correct
 * behaviour because no conformant decoder could decode them anyway.
 */
class QrGenerator
{
    /**
     * Default rendered size in pixels per side. Design > Component 1
     * specifies 240 to leave a comfortable quiet-zone margin while still
     * exceeding the 200 px minimum from criterion 1.2.
     */
    public const DEFAULT_SIZE = 240;

    /**
     * Minimum rendered size in pixels per side mandated by criterion 1.2.
     */
    public const MIN_SIZE = 200;

    /**
     * Quiet-zone margin in QR modules. Four modules is the value
     * recommended by ISO/IEC 18004 for reliable scanning.
     */
    private const MARGIN_MODULES = 4;

    private readonly RendererInterface $renderer;

    /**
     * @param  int|null  $size  Optional explicit rendered size in pixels per
     *                          side. Must be >= {@see self::MIN_SIZE}; falls
     *                          back to {@see self::DEFAULT_SIZE} when null.
     * @param  RendererInterface|null  $renderer  Optional pre-configured
     *                          renderer, primarily for testing. When supplied
     *                          the $size argument is ignored.
     *
     * @throws InvalidArgumentException If an explicit $size is below
     *                                  {@see self::MIN_SIZE}.
     */
    public function __construct(
        ?int $size = null,
        ?RendererInterface $renderer = null,
    ) {
        if ($renderer !== null) {
            $this->renderer = $renderer;

            return;
        }

        $resolvedSize = $size ?? self::DEFAULT_SIZE;

        if ($resolvedSize < self::MIN_SIZE) {
            throw new InvalidArgumentException(sprintf(
                'QR code size must be at least %d pixels (got %d).',
                self::MIN_SIZE,
                $resolvedSize,
            ));
        }

        $this->renderer = new GDLibRenderer(
            size: $resolvedSize,
            margin: self::MARGIN_MODULES,
            imageFormat: 'png',
        );
    }

    /**
     * Render the supplied URL as a QR code and return the raw PNG bytes.
     *
     * Underlying errors from BaconQrCode (empty content, encoder failures)
     * or GD (extension missing, allocation failures) propagate untouched.
     * Use {@see self::generateOrFail()} when you want a normalised
     * {@see QrGenerationException} contract for the controller path.
     *
     * @return string Raw PNG byte string starting with the standard PNG
     *                signature (\x89PNG\r\n\x1a\n).
     */
    public function generate(string $url): string
    {
        if ($url === '') {
            // BaconQrCode would also throw for empty content, but raising
            // a clearer message early avoids the "Found empty contents"
            // string leaking into application logs.
            throw new InvalidArgumentException('Cannot generate a QR code for an empty URL.');
        }

        return (new Writer($this->renderer))->writeString($url);
    }

    /**
     * Render the supplied URL as a QR code, wrapping any failure in a
     * {@see QrGenerationException} so callers have a single catch branch.
     *
     * The original throwable is attached as the previous exception so the
     * caller can log the precise reason alongside the share identifier
     * (criterion 1.6).
     *
     * @return string Raw PNG byte string.
     *
     * @throws QrGenerationException When the QR code cannot be produced.
     */
    public function generateOrFail(string $url): string
    {
        try {
            return $this->generate($url);
        } catch (Throwable $e) {
            throw new QrGenerationException(
                'Failed to generate QR code: ' . $e->getMessage(),
                previous: $e,
            );
        }
    }
}
