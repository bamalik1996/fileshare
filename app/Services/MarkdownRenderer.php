<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Validation\ValidationException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\DisallowedRawHtml\DisallowedRawHtmlExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Server-side Markdown → HTML renderer tuned for the AirToShare UI (Bulma CSS).
 *
 * Sanitisation guarantees (Requirements 12.7):
 *   - <script>, <iframe>, <object>, <embed> tags are escaped via the
 *     DisallowedRawHtml extension (rendered as &lt;…).
 *   - All on* event-handler attributes are stripped from every rendered tag
 *     via a post-render regex pass.
 *
 * Source-limit enforcement (Requirements 12.8, 12.10):
 *   - Input exceeding 500,000 characters throws a ValidationException so the
 *     controller can return a 422 with the `length_exceeded` error code.
 */
class MarkdownRenderer
{
    /**
     * Maximum allowed Markdown source length (characters, not bytes).
     */
    public const MAX_SOURCE_LENGTH = 500_000;

    /**
     * Tags that must be escaped / disabled in rendered output.
     * The DisallowedRawHtml extension turns opening/closing occurrences of
     * these into &lt;… so browsers never interpret them as live HTML.
     */
    private const DISALLOWED_TAGS = [
        'script',
        'iframe',
        'object',
        'embed',
        // Keep the CommonMark defaults as well so we don't regress on them.
        'title',
        'textarea',
        'style',
        'xmp',
        'noembed',
        'noframes',
        'plaintext',
    ];

    private MarkdownConverter $converter;

    public function __construct()
    {
        $environment = new Environment([
            'disallowed_raw_html' => [
                'disallowed_tags' => self::DISALLOWED_TAGS,
            ],
            // Allow raw HTML in Markdown source so that the DisallowedRawHtml
            // extension actually has content to filter; unsafe=true passes
            // through inline HTML for the extension to process.
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new DisallowedRawHtmlExtension());

        $this->converter = new MarkdownConverter($environment);
    }

    /**
     * Render Markdown to sanitised HTML.
     *
     * @param  string  $markdown  CommonMark Markdown source.
     * @return string             Safe HTML string, ready for display.
     *
     * @throws ValidationException  When $markdown exceeds MAX_SOURCE_LENGTH characters.
     */
    public function render(string $markdown): string
    {
        $this->enforceSourceLimit($markdown);

        // Convert Markdown → raw HTML (inline HTML is forwarded to the
        // DisallowedRawHtml renderer which escapes the forbidden tags).
        $html = $this->converter->convert($markdown)->getContent();

        // Strip all on* event-handler attributes from every HTML tag.
        // This covers both CommonMark-generated tags and any raw HTML that
        // survived the DisallowedRawHtml pass (e.g. <a onclick="…">).
        $html = $this->stripEventHandlerAttributes($html);

        return $html;
    }

    /**
     * Enforce the 500,000-character source limit.
     *
     * @throws ValidationException
     */
    private function enforceSourceLimit(string $markdown): void
    {
        // Use mb_strlen so multi-byte characters count as a single char,
        // matching the editor-side JS `string.length` behaviour.
        if (mb_strlen($markdown) > self::MAX_SOURCE_LENGTH) {
            throw ValidationException::withMessages([
                'markdown_source' => [
                    __('The markdown source may not be greater than :max characters.', [
                        'max' => number_format(self::MAX_SOURCE_LENGTH),
                    ]),
                ],
            ])->errorBag('default');
        }
    }

    /**
     * Remove inline event-handler attributes (on*="…") from rendered HTML.
     *
     * The regex matches the attribute name followed by an optional = and a
     * quoted or unquoted value, then removes the entire attribute token.
     * It is applied globally so multiple attributes on one element are all
     * stripped.
     *
     * Pattern breakdown:
     *   \s+          – one or more whitespace characters before the attribute
     *   on\w+        – attribute name starting with "on" (onclick, onload, …)
     *   (?:          – non-capturing group for the optional value part
     *     \s*=\s*    – optional equals sign surrounded by optional spaces
     *     (?:        – non-capturing group for the value
     *       "[^"]*"  – double-quoted value
     *       |'[^']*' – or single-quoted value
     *       |[^\s>]* – or unquoted value up to the next whitespace or >
     *     )
     *   )?           – the value part is optional (bare attribute)
     */
    private function stripEventHandlerAttributes(string $html): string
    {
        return (string) preg_replace(
            '/\s+on\w+\s*(?:=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]*))?/i',
            '',
            $html
        );
    }
}
