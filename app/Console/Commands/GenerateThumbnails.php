<?php

namespace App\Console\Commands;

use App\Models\Photo;
use App\Models\PhotoGallery;
use App\Services\ThumbnailService;
use Illuminate\Console\Command;

class GenerateThumbnails extends Command
{
    protected $signature = 'app:generate-thumbnails {--gallery= : The ID of a specific gallery to generate thumbnails for} {--sync : Generate synchronously instead of dispatching queue jobs}';

    protected $description = 'Generate thumbnails for photos that are missing them';

    public function handle(ThumbnailService $thumbnails): void
    {
        $galleryId = $this->option('gallery');
        $currentGalleryId = null;
        $processedCount = 0;
        $skippedCount = 0;

        if ($galleryId) {
            $gallery = PhotoGallery::query()->find($galleryId);

            if (! $gallery) {
                $this->error("Gallery with ID {$galleryId} not found.");

                return;
            }

            $this->info("Generating thumbnails for gallery: {$gallery->name} (ID: {$galleryId})");
        } else {
            $this->info('Generating thumbnails for all photos...');
        }

        Photo::query()
            ->when($galleryId, fn ($query) => $query->where('photo_gallery_id', $galleryId))
            ->whereHas('photoSection')
            ->orderBy('photo_gallery_id')
            ->orderBy('id')
            ->chunkById(100, function ($photos) use ($thumbnails, &$currentGalleryId, &$processedCount, &$skippedCount): void {
                foreach ($photos as $photo) {
                    if ($thumbnails->exists($photo->path)) {
                        $skippedCount++;

                        continue;
                    }

                    if ($photo->photo_gallery_id !== $currentGalleryId) {
                        $currentGalleryId = $photo->photo_gallery_id;
                        $this->info("Processing gallery ID: {$currentGalleryId}");
                    }

                    try {
                        $thumbnails->generate($photo);
                    } catch (Throwable $exception) {
                        report($exception);
                        $this->warn("Failed to generate thumbnail for photo ID: {$photo->id}");

                        continue;
                    }

                    $processedCount++;
                }
            });

        if ($processedCount === 0) {
            $this->info('All thumbnails already exist. Nothing to generate.');

            return;
        }

        $this->info("Thumbnails generated successfully! ({$processedCount} generated, {$skippedCount} skipped)");
    }
}
