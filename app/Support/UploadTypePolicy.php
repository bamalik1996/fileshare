<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Central allowlist for uploadable file types (Option B expanded set).
 *
 * A file is accepted when its MIME matches a configured pattern OR its
 * extension is in the configured allowlist. Dangerous executables are simply
 * omitted from both lists — there is no separate denylist.
 */
class UploadTypePolicy
{
    public function isAllowed(?string $mime, ?string $filename = null): bool
    {
        $mime = strtolower(trim((string) $mime));
        $extension = $this->extension($filename);

        foreach ($this->mimePatterns() as $pattern) {
            if ($mime !== '' && Str::is(strtolower($pattern), $mime)) {
                return true;
            }
        }

        if ($extension !== '' && in_array($extension, $this->extensions(), true)) {
            return true;
        }

        return false;
    }

    public function label(): string
    {
        return (string) config('airtoshare.upload_types.label', 'Images, Office, PDF, Text/code, Archives, Audio, Video');
    }

    public function acceptAttribute(): string
    {
        return (string) config('airtoshare.upload_types.accept', '');
    }

    /**
     * @return list<string>
     */
    public function mimePatterns(): array
    {
        /** @var list<string>|mixed $patterns */
        $patterns = config('airtoshare.upload_types.mime_patterns', []);

        return is_array($patterns) ? array_values(array_map('strval', $patterns)) : [];
    }

    /**
     * @return list<string>
     */
    public function extensions(): array
    {
        /** @var list<string>|mixed $extensions */
        $extensions = config('airtoshare.upload_types.extensions', []);

        if (! is_array($extensions)) {
            return [];
        }

        return array_values(array_map(
            static fn ($ext): string => strtolower(ltrim((string) $ext, '.')),
            $extensions,
        ));
    }

    private function extension(?string $filename): string
    {
        if ($filename === null || $filename === '') {
            return '';
        }

        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
}
