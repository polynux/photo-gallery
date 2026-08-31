<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhotoGalleryResource\Pages\CreatePhotoGallery;
use App\Filament\Resources\PhotoGalleryResource\Pages\EditPhotoGallery;
use App\Filament\Resources\PhotoGalleryResource\Pages\ListPhotoGalleries;
use App\Filament\Resources\PhotoGalleryResource\Pages\ManageSections;
use App\Filament\Resources\PhotoGalleryResource\RelationManagers\PhotosRelationManager;
use App\Filament\Resources\PhotoResource\Pages\UploadPhotos;
use App\Models\PhotoGallery;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class PhotoGalleryResource extends Resource
{
    protected static ?string $model = PhotoGallery::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-camera';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                TextInput::make('password')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->password()
                    ->maxLength(255)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state)),
                Select::make('cover_photo_id')
                    ->label('Cover Photo')
                    ->options(fn (?PhotoGallery $record): array => $record
                        ? $record->photos()
                            ->orderBy('position')
                            ->pluck('id', 'id')
                            ->mapWithKeys(fn (int|string $id): array => [$id => "Photo #{$id}"])
                            ->all()
                        : [])
                    ->searchable()
                    ->preload()
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                ImageColumn::make('coverPhoto.path')
                    ->label('Cover'),
                TextColumn::make('access_code')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('photos_count')
                    ->counts('photos')
                    ->label('Photos'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('upload_photos')
                    ->label('Upload Photos')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->url(fn (PhotoGallery $record) => static::getUrl('upload-photos', ['record' => $record->id])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PhotosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPhotoGalleries::route('/'),
            'create' => CreatePhotoGallery::route('/create'),
            'edit' => EditPhotoGallery::route('/{record}/edit'),
            'sections' => ManageSections::route('/{record}/sections'),
            'upload-photos' => UploadPhotos::route('/{record}/upload-photos'),
        ];
    }
}
