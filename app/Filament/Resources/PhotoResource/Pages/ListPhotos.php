<?php

namespace App\Filament\Resources\PhotoResource\Pages;

use App\Filament\Resources\PhotoResource;
use App\Models\Photo;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPhotos extends ListRecords
{
    protected static string $resource = PhotoResource::class;

    public ?string $photo_gallery_id = null;

    public ?string $photo_section_id = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function mount(): void
    {
        parent::mount();

        $this->photo_gallery_id = request()->get('photo_gallery_id');
        $this->photo_section_id = request()->get('photo_section_id');
    }

    protected function getTableQuery(): ?Builder
    {
        $query = Photo::query()->orderBy('position');

        if ($this->photo_gallery_id) {
            $query->where('photo_gallery_id', $this->photo_gallery_id);
        }

        if ($this->photo_section_id) {
            $query->where('photo_section_id', $this->photo_section_id);
        }

        return $query;
    }

    public function reorder(array $orderIds): void
    {
        if ($this->photo_section_id) {
            $position = 1;
            foreach ($orderIds as $id) {
                Photo::where('id', $id)
                    ->where('photo_section_id', $this->photo_section_id)
                    ->update(['position' => $position++]);
            }
        } elseif ($this->photo_gallery_id) {
            $position = 1;
            foreach ($orderIds as $id) {
                Photo::where('id', $id)
                    ->where('photo_gallery_id', $this->photo_gallery_id)
                    ->update(['position' => $position++]);
            }
        } else {
            parent::reorder($orderIds);
        }
    }
}
