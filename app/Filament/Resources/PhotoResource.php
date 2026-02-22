<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhotoResource\Pages;
use App\Models\Photo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PhotoResource extends Resource
{
    protected static ?string $model = Photo::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('photo_gallery_id')
                    ->relationship('photoGallery', 'name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('photo_section_id', null)),
                Forms\Components\Select::make('photo_section_id')
                    ->relationship('photoSection', 'name', fn (Builder $query, Forms\Get $get) => $query->where('photo_gallery_id', $get('photo_gallery_id')))
                    ->required()
                    ->label('Section'),
                Forms\Components\FileUpload::make('path')
                    ->disk('photo')
                    ->directory(request()->route('photo_gallery_id'))
                    ->visibility('private')
                    ->image()
                    ->imageEditor()
                    ->required(),
                Forms\Components\TextInput::make('alt')
                    ->label('Alt Text')
                    ->helperText('Description of the image for accessibility')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position', 'asc')
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->disk('thumbnails')
                    ->visibility('private')
                    ->square(),
                Tables\Columns\TextColumn::make('photoSection.name')
                    ->label('Section')
                    ->sortable(),
                Tables\Columns\TextColumn::make('position')
                    ->label('Position')
                    ->sortable(),
                Tables\Columns\TextColumn::make('alt')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->reorderable('position')
            ->filters([
                Tables\Filters\SelectFilter::make('photo_gallery_id')
                    ->relationship('photoGallery', 'name')
                    ->label('Photo Gallery')
                    ->preload(),
                Tables\Filters\SelectFilter::make('photo_section_id')
                    ->relationship('photoSection', 'name')
                    ->label('Section')
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('set_as_cover')
                    ->label('Set as Cover')
                    ->icon('heroicon-o-star')
                    ->action(function (Photo $record) {
                        $record->photoGallery->update(['cover_photo_id' => $record->id]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('move_to_section')
                        ->label('Move to Section')
                        ->icon('heroicon-o-folder')
                        ->form([
                            Forms\Components\Select::make('photo_section_id')
                                ->label('Section')
                                ->required()
                                ->options(function () {
                                    $galleryId = request()->get('photo_gallery_id');
                                    if (! $galleryId) {
                                        return [];
                                    }

                                    return \App\Models\PhotoSection::where('photo_gallery_id', $galleryId)
                                        ->pluck('name', 'id');
                                }),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update(['photo_section_id' => $data['photo_section_id']]);
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPhotos::route('/'),
            'create' => Pages\CreatePhoto::route('/create'),
            'edit' => Pages\EditPhoto::route('/{record}/edit'),
        ];
    }
}
