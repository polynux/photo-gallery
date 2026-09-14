<?php

namespace App\Filament\Resources\PhotoResource\Pages;

use App\Filament\Resources\PhotoResource;
use App\Services\PhotoPositionService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPhotos extends ListRecords
{
    protected static string $resource = PhotoResource::class;

    public ?string $photo_gallery_id = null;

    public function getTitle(): string
    {
        return __('admin.photo.page_title_index');
    }

    public ?string $photo_section_id = null;

    public function mount(): void
    {
        parent::mount();

        $this->photo_gallery_id = request()->get('photo_gallery_id');
        $this->photo_section_id = request()->get('photo_section_id');
    }

    public function reorder(array $orderIds): void
    {
        $positionService = app(PhotoPositionService::class);

        if ($this->photo_section_id) {
            $positionService->reindexSection($orderIds, (int) $this->photo_section_id);
        } elseif ($this->photo_gallery_id) {
            $positionService->reindexGallery($orderIds, (int) $this->photo_gallery_id);
        } else {
            parent::reorder($orderIds);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
