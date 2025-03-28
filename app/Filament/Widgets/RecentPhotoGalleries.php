<?php
// app/Filament/Widgets/RecentPhotoGalleries.php
namespace App\Filament\Widgets;

use App\Filament\Resources\PhotoGalleryResource;
use App\Models\PhotoGallery;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentPhotoGalleries extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PhotoGallery::query()
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\ImageColumn::make('coverPhoto.path')
                    ->label('Cover')
                    ->defaultImageUrl(fn ($record) => $record->photos()->first() ?
                        asset('storage/' . $record->photos()->first()->path) : null)
                    ->circular(),
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('access_code')
                    ->copyable(),
                Tables\Columns\TextColumn::make('photos_count')
                    ->counts('photos')
                    ->label('Photos'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_gallery')
                    ->label('View Gallery')
                    ->icon('heroicon-o-eye')
                    ->url(fn (PhotoGallery $record) => route('public.show', $record->access_code))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('manage')
                    ->label('Manage')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (PhotoGallery $record) => PhotoGalleryResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
