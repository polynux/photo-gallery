<?php

namespace App\Filament\Resources\UniversResource\Pages;

use App\Filament\Resources\UniversResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUnivers extends ListRecords
{
    protected static string $resource = UniversResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
