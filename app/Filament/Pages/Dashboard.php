<?php

namespace App\Filament\Pages;

use App\Services\ThumbnailService;
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
                ->action(function (ThumbnailService $thumbnails) {
                    $count = $thumbnails->queueMissing();

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
