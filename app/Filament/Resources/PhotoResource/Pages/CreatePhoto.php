<?php

namespace App\Filament\Resources\PhotoResource\Pages;

use App\Filament\Resources\PhotoResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePhoto extends CreateRecord
{
    protected static string $resource = PhotoResource::class;

    public function getTitle(): string
    {
        return __('admin.photo.page_title_create');
    }
}
