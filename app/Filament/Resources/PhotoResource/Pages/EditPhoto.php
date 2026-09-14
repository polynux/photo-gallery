<?php

namespace App\Filament\Resources\PhotoResource\Pages;

use App\Filament\Resources\PhotoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPhoto extends EditRecord
{
    protected static string $resource = PhotoResource::class;

    public function getTitle(): string
    {
        return __('admin.photo.page_title_edit');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
