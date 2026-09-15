<?php

namespace App\Services;

use App\Models\Photo;
use App\Models\PhotoGallery;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipStream\ZipStream;

class GalleryZipStream
{
    /**
     * Stream a gallery as a ZIP archive.
     *
     * @param  array<int, int|string>  $photoIds  When non-empty, only photos
     *                                            with these IDs (scoped to the given
     *                                            gallery) are included.
     */
    public function stream(PhotoGallery $photoGallery, string $outputName, array $photoIds = []): void
    {
        $photoGallery->load(['sections.photos']);

        $photoIds = collect($photoIds)->map(fn ($id) => (int) $id)->unique()->flip();

        $zip = new ZipStream(
            outputName: $outputName,
            sendHttpHeaders: false,
        );

        $sections = $photoGallery->sections;
        $galleryFolder = Str::slug($photoGallery->name) ?: 'gallery';

        // Single section (the default one) -> photos go directly into
        // the gallery folder. Multiple sections -> each section gets
        // its own sub-folder, including the default section.
        $hasMultipleSections = $sections->count() > 1;

        $photoDisk = Storage::disk('photo');

        foreach ($sections as $section) {
            $sectionFolder = $hasMultipleSections
                ? $galleryFolder . '/' . (Str::slug($section->name) ?: 'section')
                : $galleryFolder;

            $photos = $section->photos;

            if ($photoIds->isNotEmpty()) {
                $photos = $photos->filter(fn (Photo $photo) => $photoIds->has($photo->getKey()));
            }

            $maxPosition = $photos->count();
            $paddingLength = max(2, strlen((string) $maxPosition));

            foreach ($photos as $photo) {
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
