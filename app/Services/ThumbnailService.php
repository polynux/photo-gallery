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
    public const DISPLAY_PATH_PREFIX = 'display/';

    public const DERIVATIVE_EXTENSION = '.webp';

    public function __construct(
        private readonly ImageManager $images,
    ) {}

    public function thumbnailPath(string $photoPath): string
    {
        return $this->derivativePath($photoPath, '');
    }

    public function displayPath(string $photoPath): string
    {
        return $this->derivativePath($photoPath, self::DISPLAY_PATH_PREFIX);
    }

    public function legacyPath(string $photoPath): string
    {
        return $photoPath;
    }

    public function exists(string $photoPath): bool
    {
        return Storage::disk('thumbnails')->exists($this->thumbnailPath($photoPath));
    }

    public function displayExists(string $photoPath): bool
    {
        return Storage::disk('thumbnails')->exists($this->displayPath($photoPath));
    }

    public function delete(string $photoPath): void
    {
        Storage::disk('thumbnails')->delete([
            $this->thumbnailPath($photoPath),
            $this->displayPath($photoPath),
            $this->legacyPath($photoPath),
        ]);
    }

    public function deleteFor(Photo $photo): void
    {
        $this->delete($photo->path);
    }

    /**
     * Generate the WebP derivatives (grid thumbnail and display image) for the given photo.
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
        $quality = config('gallery.derivative_quality', 80);

        $grid = (clone $image)->scaleDown(config('gallery.thumbnail_max_dimension', 500));
        Storage::disk('thumbnails')->put(
            $this->thumbnailPath($photo->path),
            $grid->encodeUsingFormat(Format::WEBP, quality: $quality)->toString(),
        );

        $display = (clone $image)->scaleDown(config('gallery.display_max_dimension', 2560));
        Storage::disk('thumbnails')->put(
            $this->displayPath($photo->path),
            $display->encodeUsingFormat(Format::WEBP, quality: $quality)->toString(),
        );
    }

    /**
     * Dispatch thumbnail generation for every photo missing one.
     *
     * @return int number of jobs queued
     */
    public function queueMissing(?int $galleryId = null): int
    {
        return $this->queuePhotos(
            Photo::query()
                ->when($galleryId, fn ($query) => $query->where('photo_gallery_id', $galleryId))
                ->whereHas('photoSection'),
            fn (Photo $photo): bool => $this->exists($photo->path) && $this->displayExists($photo->path)
        );
    }

    /**
     * Clear every derivative (grid, display and legacy files) for the scope, then requeue all photos.
     *
     * @return int number of jobs queued
     */
    public function queueAll(?int $galleryId = null): int
    {
        return $this->queuePhotos(
            Photo::query()
                ->when($galleryId, fn ($query) => $query->where('photo_gallery_id', $galleryId))
                ->whereHas('photoSection'),
            fn (Photo $photo): bool => false
        );
    }

    private function queuePhotos($query, callable $skip): int
    {
        $queued = 0;

        $query->chunkById(100, function ($photos) use (&$queued, $skip): void {
            foreach ($photos as $photo) {
                if ($skip($photo)) {
                    continue;
                }

                GeneratePhotoThumbnail::dispatch($photo)->afterCommit();
                $queued++;
            }
        });

        return $queued;
    }

    private function derivativePath(string $photoPath, string $prefix): string
    {
        $extensionPosition = strrpos($photoPath, '.');

        if ($extensionPosition === false) {
            $name = $photoPath;
        } else {
            $name = substr($photoPath, 0, $extensionPosition);
        }

        return $prefix.$name.self::DERIVATIVE_EXTENSION;
    }
}
