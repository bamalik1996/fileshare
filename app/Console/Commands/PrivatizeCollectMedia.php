<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Share;
use App\Support\MediaUrlGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * One-off migration: move file-request ("collect") uploads that were saved
 * on the web-reachable `public` disk to the `private` disk.
 *
 * The media directory ({id}/{uuid}) is moved as-is and only the `disk`
 * columns change, so the uuid — and therefore virus-scan rows and existing
 * download links — stay valid.
 */
class PrivatizeCollectMedia extends Command
{
    protected $signature = 'media:privatize-collect {--dry-run : List what would move without changing anything}';

    protected $description = 'Move existing file-request uploads from the public disk to the private disk';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $private = MediaUrlGenerator::PRIVATE_DISK;

        $collectShareIds = Share::query()->where('is_collect', true)->pluck('id');

        $query = Media::query()
            ->where('model_type', (new Share())->getMorphClass())
            ->whereIn('model_id', $collectShareIds)
            ->where('disk', 'public');

        $moved = 0;
        $failed = 0;

        foreach ($query->cursor() as $media) {
            // Directory relative to the disk root, e.g. "123/9f1c…".
            $relativeDir = trim(dirname($media->getPathRelativeToRoot()), '/');
            $from = Storage::disk('public')->path($relativeDir);
            $to = Storage::disk($private)->path($relativeDir);

            if ($dryRun) {
                $this->line("would move media {$media->id}: {$relativeDir}");
                $moved++;
                continue;
            }

            try {
                if (! File::isDirectory($from)) {
                    $this->warn("media {$media->id}: source directory missing, skipped");
                    $failed++;
                    continue;
                }

                File::ensureDirectoryExists(dirname($to));

                if (! File::moveDirectory($from, $to)) {
                    throw new \RuntimeException('moveDirectory returned false');
                }

                $media->disk = $private;
                $media->conversions_disk = $private;
                $media->save();
                $moved++;
            } catch (Throwable $e) {
                $this->error("media {$media->id}: {$e->getMessage()}");
                $failed++;
            }
        }

        $verb = $dryRun ? 'Would move' : 'Moved';
        $this->info("{$verb} {$moved} file(s); {$failed} failed or skipped.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
