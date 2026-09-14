<?php

namespace App\Filament\Resources\PhotoGalleryResource\Pages;

use App\Filament\Resources\PhotoGalleryResource;
use App\Filament\Resources\PhotoResource;
use App\Services\ThumbnailService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPhotoGallery extends EditRecord
{
    protected static string $resource = PhotoGalleryResource::class;

    public function getTitle(): string
    {
        return __('admin.gallery.page_title_edit');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['password'] = $this->getRecord()->password;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_gallery')
                ->label(__('admin.gallery.view_gallery'))
                ->icon('heroicon-o-eye')
                ->url(fn () => route('public.show', $this->record->access_code))
                ->openUrlInNewTab(),
            Action::make('manage_sections')
                ->label(__('admin.gallery.manage_sections'))
                ->icon('heroicon-o-folder')
                ->url(fn () => PhotoGalleryResource::getUrl('sections', ['record' => $this->record->id])),
            Action::make('manage_photos')
                ->label(__('admin.gallery.manage_photos'))
                ->icon('heroicon-o-photo')
                ->url(fn () => PhotoResource::getUrl('index', ['photo_gallery_id' => $this->record->id])),
            Action::make('generate_thumbnails')
                ->label(__('admin.gallery.generate_thumbnails'))
                ->icon('heroicon-o-photo')
                ->color('warning')
                ->action(function (ThumbnailService $thumbnails) {
                    $count = $thumbnails->queueMissing($this->record->id);

                    Notification::make()
                        ->title(__('admin.dashboard.queued'))
                        ->body($count > 0
                            ? trans_choice('admin.dashboard.queued_count', $count, ['count' => $count])
                            : __('admin.dashboard.already_exist_gallery'))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading(__('admin.dashboard.edit_modal_heading'))
                ->modalDescription(fn () => __('admin.dashboard.edit_modal_description', ['name' => $this->record->name])),
            DeleteAction::make(),
        ];
    }
}
