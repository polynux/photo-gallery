<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhotoGalleryResource\Pages\CreatePhotoGallery;
use App\Filament\Resources\PhotoGalleryResource\Pages\EditPhotoGallery;
use App\Filament\Resources\PhotoGalleryResource\Pages\ListPhotoGalleries;
use App\Filament\Resources\PhotoGalleryResource\Pages\ManageSections;
use App\Filament\Resources\PhotoGalleryResource\RelationManagers;
use App\Filament\Resources\PhotoResource\Pages\UploadPhotos;
use App\Models\PhotoGallery;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class PhotoGalleryResource extends Resource
{
    protected static ?string $model = PhotoGallery::class;

    protected static ?string $navigationIcon = 'heroicon-o-camera';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->label('Cover')
                    ->defaultImageUrl(fn (PhotoGallery $record): ?string => $record->coverPhoto
                        ? asset('thumbnails/' . $record->coverPhoto->path)
                        : null)
                    ->circular(),
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
            ->actions([
                EditAction::make(),
                Action::make('upload_photos')
                    ->label('Upload Photos')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->url(fn (PhotoGallery $record) => static::getUrl('upload-photos', ['record' => $record->id])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PhotosRelationManager::class,
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
