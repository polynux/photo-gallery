<?php

namespace App\Filament\Pages;

use App\Services\ThumbnailService;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_thumbnails')
                ->label(__('admin.dashboard.generate_thumbnails'))
                ->color('warning')
                ->form([
                    Toggle::make('force')
                        ->label(__('admin.dashboard.force_label'))
                        ->helperText(__('admin.dashboard.force_helper'))
                        ->default(false)
                        ->live(),
                ])
                ->action(function (ThumbnailService $thumbnails, array $data) {
                    $count = $data['force'] ?? false
                        ? $thumbnails->queueAll()
                        : $thumbnails->queueMissing();

                    Notification::make()
                        ->title(__('admin.dashboard.queued'))
                        ->body($count > 0
                            ? trans_choice('admin.dashboard.queued_count', $count, ['count' => $count])
                            : __('admin.dashboard.already_exist'))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading(__('admin.dashboard.modal_heading'))
                ->modalDescription(__('admin.dashboard.modal_description'))
                ->modalSubmitActionLabel(__('admin.dashboard.modal_submit')),
        ];
    }
}
