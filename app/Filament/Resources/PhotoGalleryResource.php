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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PhotoGalleryResource extends Resource
{
    protected static ?string $model = PhotoGallery::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-camera';

    public static function getNavigationLabel(): string
    {
        return __('admin.gallery.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.gallery.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.gallery.plural_model_label');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('coverPhoto');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('admin.common.name'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('admin.common.description'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
                TextInput::make('password')
                    ->label(__('admin.gallery.password'))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->default(fn (): string => Str::random(12))
                    ->maxLength(255),
                Select::make('cover_photo_id')
                    ->label(__('admin.gallery.cover_photo'))
                    ->options(fn (?PhotoGallery $record): array => $record
                        ? $record->photos()
                            ->orderBy('position')
                            ->pluck('id', 'id')
                            ->mapWithKeys(fn (int|string $id): array => [$id => __('admin.gallery.photo_number', ['id' => $id])])
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
                    ->label(__('admin.common.name'))
                    ->searchable(),
                ImageColumn::make('coverPhoto.path')
                    ->label(__('admin.gallery.cover'))
                    ->disk('photo'),
                TextColumn::make('access_code')
                    ->label(__('admin.gallery.access_code'))
                    ->copyable()
                    ->searchable(),
                TextColumn::make('photos_count')
                    ->counts('photos')
                    ->label(__('admin.gallery.photos_count')),
                TextColumn::make('created_at')
                    ->label(__('admin.common.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('view_gallery')
                    ->label(__('admin.gallery.view_gallery'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (PhotoGallery $record) => route('public.show', $record->access_code))
                    ->openUrlInNewTab(),
                EditAction::make(),
                Action::make('upload_photos')
                    ->label(__('admin.gallery.upload_photos'))
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
