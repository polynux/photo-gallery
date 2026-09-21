<?php

namespace App\Console\Commands;

use App\Models\Photo;
use App\Services\ThumbnailService;
use Illuminate\Console\Command;
use Throwable;

class BackfillPhotoDimensions extends Command
{
    protected $signature = 'app:backfill-photo-dimensions
        {--gallery= : The ID of a specific gallery to backfill dimensions for}';

    protected $description = 'Fill missing photo width/height by reading existing grid thumbnails';

    public function handle(ThumbnailService $thumbnails): int
    {
        $galleryId = $this->option('gallery');
        $filledCount = 0;
        $skippedCount = 0;

        Photo::query()
            ->when($galleryId, fn ($query) => $query->where('photo_gallery_id', $galleryId))
            ->whereNull('width')
            ->whereNull('height')
            ->whereHas('photoSection')
            ->orderBy('id')
            ->chunkById(100, function ($photos) use ($thumbnails, &$filledCount, &$skippedCount): void {
                foreach ($photos as $photo) {
                    try {
                        $dimensions = $thumbnails->gridDimensions($photo->path);
                    } catch (Throwable $exception) {
                        report($exception);
                        $skippedCount++;

                        continue;
                    }

                    if ($dimensions === null) {
                        $skippedCount++;

                        continue;
                    }

                    $photo->forceFill($dimensions)->saveQuietly();
                    $filledCount++;
                }
            });

        $this->info("Backfilled dimensions for {$filledCount} photos ({$skippedCount} skipped).");

        return self::SUCCESS;
    }
}
