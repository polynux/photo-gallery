<?php

namespace App\Filament\Resources\UniversResource\Pages;

use App\Filament\Resources\UniversResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUnivers extends ListRecords
{
    protected static string $resource = UniversResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
