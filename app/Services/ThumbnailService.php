<?php

namespace App\Services;

use App\Jobs\GeneratePhotoThumbnail;
use App\Models\Photo;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use RuntimeException;
use Throwable;

class ThumbnailService
{
    public function __construct(
        private readonly ImageManager $images,
    ) {}

    public function exists(string $photoPath): bool
    {
        return Storage::disk('thumbnails')->exists($photoPath);
    }

    public function delete(string $photoPath): void
    {
        Storage::disk('thumbnails')->delete($photoPath);
    }

    /**
     * Generate a JPEG thumbnail (max 1920px, never upscaled) for the given photo.
     *
     * @throws Throwable when the source file cannot be read or encoded
     */
    public function generate(Photo $photo): void
    {
        $photoDisk = Storage::disk('photo');

        if (! $photoDisk->exists($photo->path)) {
            throw new RuntimeException("Photo file not found on disk: {$photo->path}");
        }

        $image = $this->images->decodeBinary($photoDisk->get($photo->path));
        $image->scaleDown(config('gallery.thumbnail_max_dimension', 1920));

        Storage::disk('thumbnails')->put(
            $photo->path,
            $image->encodeUsingFormat(Format::JPEG, quality: config('gallery.thumbnail_quality', 80))->toString(),
        );
    }

    /**
     * Dispatch thumbnail generation for every photo missing one.
     *
     * @return int number of jobs queued
     */
    public function queueMissing(?int $galleryId = null): int
    {
        $queued = 0;

        Photo::query()
            ->when($galleryId, fn ($query) => $query->where('photo_gallery_id', $galleryId))
            ->whereHas('photoSection')
            ->chunkById(100, function ($photos) use (&$queued): void {
                foreach ($photos as $photo) {
                    if ($this->exists($photo->path)) {
                        continue;
                    }

                    GeneratePhotoThumbnail::dispatch($photo)->afterCommit();
                    $queued++;
                }
            });

        return $queued;
    }
}
