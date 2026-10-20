<?php

declare(strict_types=1);

namespace App\Support;

use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

/**
 * Media on the `private` disk has no public URL. Point getUrl() (and
 * getFullUrl(), which builds on it) at the authorised download route
 * instead, so every existing caller keeps working without leaking a
 * direct storage path.
 */
class MediaUrlGenerator extends DefaultUrlGenerator
{
    public const PRIVATE_DISK = 'private';

    public function getUrl(): string
    {
        if ($this->media->disk === self::PRIVATE_DISK) {
            return route('media.download.file', ['uuid' => $this->media->uuid]);
        }

        return parent::getUrl();
    }
}
