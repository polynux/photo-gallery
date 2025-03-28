<?php
namespace App\Filament\Widgets;

use App\Models\Photo;
use App\Models\PhotoGallery;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Galleries', PhotoGallery::count())
                ->description('All photo galleries')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary'),

            Stat::make('Total Photos', Photo::count())
                ->description('All photos in galleries')
                ->descriptionIcon('heroicon-m-photo')
                ->color('success'),

            Stat::make('Recent Uploads', Photo::where('created_at', '>=', now()->subDays(7))->count())
                ->description('Photos uploaded in the last 7 days')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning'),
        ];
    }
}
