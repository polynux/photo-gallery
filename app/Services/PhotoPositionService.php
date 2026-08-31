<?php

namespace App\Services;

use App\Models\Photo;
use Illuminate\Support\Facades\DB;

class PhotoPositionService
{
    /**
     * Append a photo to the end of its section.
     */
    public function appendPosition(int $sectionId): int
    {
        return (Photo::where('photo_section_id', $sectionId)->max('position') ?? 0) + 1;
    }

    /**
     * Assign sequential positions (1..N) to an ordered list of photo IDs
     * scoped to a single section.
     *
     * @param  list<int|string>  $orderIds
     */
    public function reindexSection(array $orderIds, int $sectionId): void
    {
        DB::transaction(function () use ($orderIds, $sectionId): void {
            $position = 1;

            foreach ($orderIds as $id) {
                Photo::where('id', $id)
                    ->where('photo_section_id', $sectionId)
                    ->update(['position' => $position++]);
            }
        });
    }

    /**
     * Reorder photos across a whole gallery from a global (cross-section)
     * order. Each section keeps consecutive positions, and relative order
     * of photos within each section follows the global order.
     *
     * @param  list<int|string>  $orderIds  photo ids in the desired global order
     */
    public function reindexGallery(array $orderIds, int $galleryId): void
    {
        DB::transaction(function () use ($orderIds, $galleryId): void {
            $positionsBySection = [];

            foreach ($orderIds as $id) {
                $sectionId = Photo::where('id', $id)
                    ->where('photo_gallery_id', $galleryId)
                    ->value('photo_section_id');

                if ($sectionId === null) {
                    continue;
                }

                $nextPosition = ($positionsBySection[$sectionId] ?? 0) + 1;
                $positionsBySection[$sectionId] = $nextPosition;

                Photo::where('id', $id)
                    ->where('photo_gallery_id', $galleryId)
                    ->update(['position' => $nextPosition]);
            }

            foreach ($positionsBySection as $sectionId => $maxPosition) {
                // Fill gaps left by photos not present in the reorder payload
                $missing = Photo::where('photo_section_id', $sectionId)
                    ->whereNotIn('id', $orderIds)
                    ->orderBy('position')
                    ->get();

                foreach ($missing as $photo) {
                    $maxPosition++;
                    $photo->update(['position' => $maxPosition]);
                }
            }
        });
    }

    /**
     * Move a photo to a different section, appending it at the end and
     * reindexing the section it left.
     */
    public function moveToSection(Photo $photo, int $targetSectionId): void
    {
        $previousSectionId = $photo->photo_section_id;

        if ($previousSectionId === $targetSectionId) {
            return;
        }

        $photo->photo_section_id = $targetSectionId;
        $photo->position = $this->appendPosition($targetSectionId);
        $photo->save();

        if ($previousSectionId !== null) {
            $this->reindexAllInSection($previousSectionId);
        }
    }

    /**
     * Compact positions in a section to 1..N preserving current order.
     */
    public function reindexAllInSection(int $sectionId): void
    {
        Photo::where('photo_section_id', $sectionId)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->each(function (Photo $photo, int $index): void {
                $expected = $index + 1;

                if ($photo->position !== $expected) {
                    $photo->update(['position' => $expected]);
                }
            });
    }
}
