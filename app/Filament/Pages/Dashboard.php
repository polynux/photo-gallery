<?php

namespace App\Filament\Pages;

use App\Jobs\GeneratePhotoThumbnail;
use App\Models\Photo;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;

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
                            GeneratePhotoThumbnail::dispatch($photo);
                            $count++;
                        }
                    });

                    Notification::make()
                        ->title('Miniatures en file d\'attente')
                        ->body("{$count} miniatures de photos ont été mises en file d'attente pour la génération.")
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Générer les miniatures manquantes')
                ->modalDescription('Êtes-vous sûr de vouloir lancer la génération des miniatures manquantes ? Cette opération se fera en arrière-plan.')
                ->modalSubmitActionLabel('Lancer la génération'),
        ];
    }
}
