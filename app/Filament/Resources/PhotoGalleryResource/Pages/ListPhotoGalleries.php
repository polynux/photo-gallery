<?php

namespace App\Filament\Resources\PhotoGalleryResource\Pages;

use App\Filament\Resources\PhotoGalleryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPhotoGalleries extends ListRecords
{
    protected static string $resource = PhotoGalleryResource::class;

    public function getTitle(): string
    {
        return __('admin.gallery.page_title_index');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
