<?php

namespace App\Filament\Resources\PhotoGalleryResource\Pages;

use App\Filament\Resources\PhotoGalleryResource;
use App\Filament\Resources\PhotoResource;
use App\Jobs\GeneratePhotoThumbnail;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditPhotoGallery extends EditRecord
{
    protected static string $resource = PhotoGalleryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_gallery')
                ->label('View Gallery')
                ->icon('heroicon-o-eye')
                ->url(fn () => route('public.show', $this->record->access_code))
                ->openUrlInNewTab(),
            Actions\Action::make('manage_sections')
                ->label('Manage Sections')
                ->icon('heroicon-o-folder')
                ->url(fn () => PhotoGalleryResource::getUrl('sections', ['record' => $this->record->id])),
            Actions\Action::make('manage_photos')
                ->label('Manage Photos')
                ->icon('heroicon-o-photo')
                ->url(fn () => PhotoResource::getUrl('index', ['photo_gallery_id' => $this->record->id])),
            Actions\Action::make('generate_thumbnails')
                ->label('Generate Thumbnails')
                ->icon('heroicon-o-photo')
                ->color('warning')
                ->action(function () {
                    $count = 0;
                    foreach ($this->record->sections as $section) {
                        foreach ($section->photos as $photo) {
                            $thumbnailPath = Storage::disk('private')->path('thumbnails/' . $photo->path);
                            if (! file_exists($thumbnailPath)) {
                                GeneratePhotoThumbnail::dispatch($photo);
                                $count++;
                            }
                        }
                    }

                    Notification::make()
                        ->title('Miniatures en file d\'attente')
                        ->body($count > 0
                            ? "{$count} miniatures ont été mises en file d'attente pour la génération."
                            : 'Toutes les miniatures existent déjà pour cette galerie.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Générer les miniatures')
                ->modalDescription(fn () => "Générer les miniatures manquantes pour la galerie \"{$this->record->name}\" ?"),
            Actions\DeleteAction::make(),
        ];
    }
}
