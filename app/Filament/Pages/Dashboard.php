<?php

namespace App\Filament\Pages;

use App\Jobs\GeneratePhotoThumbnail;
use App\Models\Photo;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Storage;

class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_thumbnails')
                ->label('Générer les miniatures')
                ->color('warning')
                ->action(function () {
                    $count = 0;
                    Photo::chunk(100, function ($photos) use (&$count) {
                        foreach ($photos as $photo) {
                            $thumbnailPath = Storage::disk('private')->path('thumbnails/'.$photo->path);
                            if (! file_exists($thumbnailPath)) {
                                GeneratePhotoThumbnail::dispatch($photo);
                                $count++;
                            }
                        }
                    });

                    Notification::make()
                        ->title('Miniatures en file d\'attente')
                        ->body($count > 0
                            ? "{$count} miniatures ont été mises en file d'attente pour la génération."
                            : 'Toutes les miniatures existent déjà.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Générer les miniatures manquantes')
                ->modalDescription('Lancer la génération des miniatures manquantes pour toutes les galeries ? Cette opération se fera en arrière-plan.')
                ->modalSubmitActionLabel('Lancer la génération'),
        ];
    }
}
