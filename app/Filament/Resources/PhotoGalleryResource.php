<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhotoGalleryResource\Pages;
use App\Filament\Resources\PhotoGalleryResource\RelationManagers;
use App\Filament\Resources\PhotoResource\Pages\UploadPhotos;
use App\Jobs\GeneratePhotoThumbnail;
use App\Models\PhotoGallery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

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
                        asset('thumbnails/' . $record->photos()->first()->path) : null)
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
                Tables\Actions\Action::make('sections')
                    ->label('Manage Sections')
                    ->icon('heroicon-o-folder')
                    ->url(fn (PhotoGallery $record) => static::getUrl('sections', ['record' => $record->id])),
                Tables\Actions\Action::make('photos')
                    ->label('Manage Photos')
                    ->icon('heroicon-o-photo')
                    ->url(fn (PhotoGallery $record) => PhotoResource::getUrl('index', ['photo_gallery_id' => $record->id])),
                Tables\Actions\Action::make('upload_photos')
                    ->label('Upload Photos')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->url(fn (PhotoGallery $record) => static::getUrl('upload-photos', ['record' => $record->id])),
                Tables\Actions\Action::make('generate_thumbnails')
                    ->label('Generate Thumbnails')
                    ->icon('heroicon-o-photo')
                    ->color('warning')
                    ->action(function (PhotoGallery $record) {
                        $count = 0;
                        foreach ($record->sections as $section) {
                            foreach ($section->photos as $photo) {
                                $thumbnailPath = Storage::disk('private')->path('thumbnails/' . $photo->path);
                                if (! file_exists($thumbnailPath)) {
                                    GeneratePhotoThumbnail::dispatch($photo);
                                    $count++;
                                }
                            }
                        }

                        Notification::make()
                            ->title('Miniatures en file d\'attente')
                            ->body($count > 0
                                ? "{$count} miniatures ont été mises en file d'attente pour la génération."
                                : 'Toutes les miniatures existent déjà pour cette galerie.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Générer les miniatures')
                    ->modalDescription(fn (PhotoGallery $record) => "Générer les miniatures manquantes pour la galerie \"{$record->name}\" ?"),
                Tables\Actions\Action::make('view_gallery')
                    ->label('View Gallery')
                    ->icon('heroicon-o-eye')
                    ->url(fn (PhotoGallery $record) => route('public.show', $record->access_code))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('generate_thumbnails_bulk')
                        ->label('Generate Thumbnails')
                        ->icon('heroicon-o-photo')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $gallery) {
                                foreach ($gallery->sections as $section) {
                                    foreach ($section->photos as $photo) {
                                        $thumbnailPath = Storage::disk('private')->path('thumbnails/' . $photo->path);
                                        if (! file_exists($thumbnailPath)) {
                                            GeneratePhotoThumbnail::dispatch($photo);
                                            $count++;
                                        }
                                    }
                                }
                            }

                            Notification::make()
                                ->title('Miniatures en file d\'attente')
                                ->body($count > 0
                                    ? "{$count} miniatures ont été mises en file d'attente pour la génération."
                                    : 'Toutes les miniatures existent déjà pour les galeries sélectionnées.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Générer les miniatures')
                        ->modalDescription('Générer les miniatures manquantes pour les galeries sélectionnées ?'),
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
            'sections' => Pages\ManageSections::route('/{record}/sections'),
            'upload-photos' => UploadPhotos::route('/{record}/upload-photos'),
        ];
    }
}
