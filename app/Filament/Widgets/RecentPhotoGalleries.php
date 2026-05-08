<?php

// app/Filament/Widgets/RecentPhotoGalleries.php

namespace App\Filament\Widgets;

use App\Filament\Resources\PhotoGalleryResource;
use App\Models\PhotoGallery;
use Filament\Tables\Actions\Action;
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
                    ->label('Cover')
                    ->defaultImageUrl(fn (PhotoGallery $record): ?string => $record->coverPhoto
                        ? asset('thumbnails/' . $record->coverPhoto->path)
                        : null)
                    ->circular(),
                TextColumn::make('name'),
                TextColumn::make('access_code')
                    ->copyable(),
                TextColumn::make('photos_count')
                    ->counts('photos')
                    ->label('Photos'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Action::make('view_gallery')
                    ->label('View Gallery')
                    ->icon('heroicon-o-eye')
                    ->url(fn (PhotoGallery $record) => route('public.show', $record->access_code))
                    ->openUrlInNewTab(),
                Action::make('manage')
                    ->label('Manage')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (PhotoGallery $record) => PhotoGalleryResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
