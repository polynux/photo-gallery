<?php

// app/Filament/Widgets/RecentPhotoGalleries.php

namespace App\Filament\Widgets;

use App\Filament\Resources\PhotoGalleryResource;
use App\Models\PhotoGallery;
use Filament\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentPhotoGalleries extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PhotoGallery::query()
                    ->with('coverPhoto')
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                ImageColumn::make('coverPhoto.path')
                    ->label(__('admin.gallery.cover'))
                    ->disk('photo'),
                TextColumn::make('name')
                    ->label(__('admin.common.name')),
                TextColumn::make('access_code')
                    ->label(__('admin.gallery.access_code'))
                    ->copyable(),
                TextColumn::make('photos_count')
                    ->counts('photos')
                    ->label(__('admin.gallery.photos_count')),
                TextColumn::make('created_at')
                    ->label(__('admin.common.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('view_gallery')
                    ->label(__('admin.gallery.view_gallery'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (PhotoGallery $record) => route('public.show', $record->access_code))
                    ->openUrlInNewTab(),
                Action::make('manage')
                    ->label(__('admin.gallery.manage'))
                    ->icon('heroicon-o-pencil')
                    ->url(fn (PhotoGallery $record) => PhotoGalleryResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
