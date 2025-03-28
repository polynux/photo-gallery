<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhotoGalleryResource\Pages;
use App\Filament\Resources\PhotoGalleryResource\RelationManagers;
use App\Models\PhotoGallery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PhotoGalleryResource extends Resource
{
    protected static ?string $model = PhotoGallery::class;

    protected static ?string $navigationIcon = 'heroicon-o-camera';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('password')
                    ->required()
                    ->password()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn ($state) => $state ? $state : null),
                Forms\Components\Select::make('cover_photo_id')
                    ->label('Cover Photo')
                    ->relationship('coverPhoto', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "Photo #{$record->id}")
                    ->searchable()
                    ->preload()
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('coverPhoto.path')
                    ->label('Cover')
                    ->defaultImageUrl(fn ($record) => $record->photos()->first() ?
                        asset('storage/' . $record->photos()->first()->path) : null)
                    ->circular(),
                Tables\Columns\TextColumn::make('access_code')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('photos_count')
                    ->counts('photos')
                    ->label('Photos'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('photos')
                    ->label('Manage Photos')
                    ->icon('heroicon-o-photo')
                    ->url(fn (PhotoGallery $record) => PhotoResource::getUrl('index', ['photo_gallery_id' => $record->id])),
                Tables\Actions\Action::make('upload_photos')
                    ->label('Upload Photos')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->url(fn (PhotoGallery $record) => static::getUrl('upload-photos', ['record' => $record->id])),
                Tables\Actions\Action::make('view_gallery')
                    ->label('View Gallery')
                    ->icon('heroicon-o-eye')
                    ->url(fn (PhotoGallery $record) => route('public.show', $record->access_code))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPhotoGalleries::route('/'),
            'create' => Pages\CreatePhotoGallery::route('/create'),
            'edit' => Pages\EditPhotoGallery::route('/{record}/edit'),
            'upload-photos' => Pages\UploadPhotos::route('/{record}/upload-photos'),
        ];
    }
}
