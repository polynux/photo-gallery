<?php

namespace App\Filament\Resources\PhotoGalleryResource\Pages;

use App\Filament\Resources\PhotoGalleryResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePhotoGallery extends CreateRecord
{
    protected static string $resource = PhotoGalleryResource::class;

    public function getTitle(): string
    {
        return __('admin.gallery.page_title_create');
    }
}
