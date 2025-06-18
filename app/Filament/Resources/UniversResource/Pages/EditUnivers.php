<?php

namespace App\Filament\Resources\UniversResource\Pages;

use App\Filament\Resources\UniversResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnivers extends EditRecord
{
    protected static string $resource = UniversResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
