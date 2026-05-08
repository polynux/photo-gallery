<?php

namespace App\Console\Commands;

use App\Models\Photo;
use App\Models\PhotoGallery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateThumbnails extends Command
{
    protected $signature = 'app:generate-thumbnails {--gallery= : The ID of a specific gallery to generate thumbnails for}';

    protected $description = 'Generate thumbnails for photos that are missing them';

    public function handle(): void
    {
        $galleryId = $this->option('gallery');
        $processedCount = 0;
        $skippedCount = 0;
        $currentGalleryId = null;

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
            ->orderBy('photo_gallery_id')
            ->orderBy('id')
            ->chunkById(100, function ($photos) use (&$currentGalleryId, &$processedCount, &$skippedCount): void {
                foreach ($photos as $photo) {
                    $thumbnailPath = Storage::disk('private')->path('thumbnails/' . $photo->path);

                    if (file_exists($thumbnailPath)) {
                        $skippedCount++;

                        continue;
                    }

                    if ($photo->photo_gallery_id !== $currentGalleryId) {
                        $currentGalleryId = $photo->photo_gallery_id;
                        $this->info("Processing gallery ID: {$currentGalleryId}");
                    }

                    $photo->generateThumbnail();
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
