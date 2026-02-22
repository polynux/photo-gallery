<?php

namespace App\Filament\Resources\PhotoResource\Pages;

use App\Filament\Resources\PhotoResource;
use App\Models\Photo;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPhotos extends ListRecords
{
    protected static string $resource = PhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function reorder(array $orderIds): void
    {
        $sectionId = request()->get('photo_section_id');

        if ($sectionId) {
            $startingPosition = Photo::where('photo_section_id', $sectionId)
                ->whereNotIn('id', $orderIds)
                ->max('position') + 1 ?? 1;

            $position = $startingPosition;
            foreach ($orderIds as $id) {
                Photo::where('id', $id)
                    ->where('photo_section_id', $sectionId)
                    ->update(['position' => $position++]);
            }
        } else {
            $galleryId = request()->get('photo_gallery_id');

            if ($galleryId) {
                $startingPosition = Photo::where('photo_gallery_id', $galleryId)
                    ->whereNotIn('id', $orderIds)
                    ->max('position') + 1 ?? 1;

                $position = $startingPosition;
                foreach ($orderIds as $id) {
                    Photo::where('id', $id)
                        ->where('photo_gallery_id', $galleryId)
                        ->update(['position' => $position++]);
                }
            } else {
                parent::reorder($orderIds);
            }
        }
    }
}
