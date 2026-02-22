<?php

namespace App\Console\Commands;

use App\Models\Photo;
use App\Models\PhotoGallery;
use Fiber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateThumbnails extends Command
{
    protected $signature = 'app:generate-thumbnails {--gallery= : The ID of a specific gallery to generate thumbnails for}';

    protected $description = 'Generate thumbnails for photos that are missing them';

    public function handle(): void
    {
        $galleryId = $this->option('gallery');

        if ($galleryId) {
            $gallery = PhotoGallery::find($galleryId);
            if (! $gallery) {
                $this->error("Gallery with ID {$galleryId} not found.");

                return;
            }
            $this->info("Generating thumbnails for gallery: {$gallery->name} (ID: {$galleryId})");
            $photos = Photo::where('photo_gallery_id', $galleryId)->get();
        } else {
            $this->info('Generating thumbnails for all photos...');
            $photos = Photo::all();
        }

        $fibers = [];
        $processedCount = 0;
        $skippedCount = 0;
        $currentGalleryId = null;

        foreach ($photos as $photo) {
            $thumbnailPath = Storage::disk('private')->path('thumbnails/' . $photo->path);

            if (! file_exists($thumbnailPath)) {
                if ($photo->photo_gallery_id !== $currentGalleryId) {
                    $currentGalleryId = $photo->photo_gallery_id;
                    $this->info("Processing gallery ID: {$currentGalleryId}");
                }
                $fibers[] = new Fiber(function () use ($photo) {
                    $photo->generateThumbnail();
                    $photo->save();
                });
                $processedCount++;
            } else {
                $skippedCount++;
            }
        }

        if ($processedCount === 0) {
            $this->info('All thumbnails already exist. Nothing to generate.');

            return;
        }

        $this->info("Generating {$processedCount} thumbnails...");

        foreach ($fibers as $fiber) {
            $fiber->start();
        }

        while ($fibers) {
            foreach ($fibers as $index => $fiber) {
                if ($fiber->isTerminated()) {
                    unset($fibers[$index]);
                } elseif (! $fiber->isStarted() || $fiber->isSuspended()) {
                    $fiber->resume();
                }
            }
        }

        $this->info("Thumbnails generated successfully! ({$processedCount} generated, {$skippedCount} skipped)");
    }
}
