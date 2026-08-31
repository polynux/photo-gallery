<?php

namespace App\Services;

use App\Models\PhotoGallery;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipStream\ZipStream;

class GalleryZipStream
{
    /**
     * Stream a gallery as a ZIP archive.
     */
    public function stream(PhotoGallery $photoGallery, string $outputName): void
    {
        $photoGallery->load(['sections.photos']);

        $zip = new ZipStream(
            outputName: $outputName,
            sendHttpHeaders: false,
        );

        $sections = $photoGallery->sections;
        $galleryFolder = Str::slug($photoGallery->name) ?: 'gallery';
        $hasMultipleSections = $sections->count() > 1 || $sections->first()?->is_default === false;

        $photoDisk = Storage::disk('photo');

        foreach ($sections as $section) {
            $sectionSlug = Str::slug($section->name) ?: 'section';
            $sectionIsGalleryRoot = $sectionSlug === $galleryFolder;
            $sectionFolder = ! $hasMultipleSections || $sectionIsGalleryRoot
                ? $galleryFolder
                : $galleryFolder . '/' . $sectionSlug;

            $maxPosition = $section->photos->count();
            $paddingLength = max(2, strlen((string) $maxPosition));

            foreach ($section->photos as $photo) {
                if (! $photoDisk->exists($photo->path)) {
                    report(new RuntimeException("Photo file not found on disk: {$photo->path}"));

                    continue;
                }

                $position = str_pad((string) $photo->position, $paddingLength, '0', STR_PAD_LEFT);
                $entryName = "{$sectionFolder}/{$position}.jpg";

                $zip->addFileFromCallback($entryName, function () use ($photoDisk, $photo): string {
                    return $photoDisk->get($photo->path);
                });
            }
        }

        $zip->finish();
    }

    public function slugArchiveName(PhotoGallery $photoGallery): string
    {
        return (Str::slug($photoGallery->name) ?: 'gallery') . '.zip';
    }
}
