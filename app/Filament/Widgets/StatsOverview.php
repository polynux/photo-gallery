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
            Stat::make(__('admin.stats.total_galleries'), PhotoGallery::count())
                ->description(__('admin.stats.total_galleries_description'))
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary'),

            Stat::make(__('admin.stats.total_photos'), Photo::count())
                ->description(__('admin.stats.total_photos_description'))
                ->descriptionIcon('heroicon-m-photo')
                ->color('success'),

            Stat::make(__('admin.stats.recent_photos'), Photo::where('created_at', '>=', now()->subDays(7))->count())
                ->description(__('admin.stats.recent_photos_description'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning'),
        ];
    }
}
