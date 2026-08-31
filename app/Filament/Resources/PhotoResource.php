<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhotoResource\Pages\CreatePhoto;
use App\Filament\Resources\PhotoResource\Pages\EditPhoto;
use App\Filament\Resources\PhotoResource\Pages\ListPhotos;
use App\Models\Photo;
use App\Models\PhotoSection;
use App\Services\PhotoPositionService;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
                Select::make('photo_gallery_id')
                    ->relationship('photoGallery', 'name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('photo_section_id', null)),
                Select::make('photo_section_id')
                    ->relationship('photoSection', 'name', fn (Builder $query, Forms\Get $get) => $query->where('photo_gallery_id', $get('photo_gallery_id')))
                    ->required()
                    ->label('Section'),
                FileUpload::make('path')
                    ->disk('photo')
                    ->directory(fn (Forms\Get $get): ?string => $get('photo_gallery_id'))
                    ->visibility('private')
                    ->image()
                    ->imageEditor()
                    ->required(),
                TextInput::make('alt')
                    ->label('Alt Text')
                    ->helperText('Description of the image for accessibility')
                    ->maxLength(255),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->orderBy('position')
            ->with(['photoSection', 'photoGallery']);

        $galleryId = request()->get('photo_gallery_id');

        if ($galleryId) {
            $query->where('photo_gallery_id', $galleryId);
        }

        $sectionId = request()->get('photo_section_id');

        if ($sectionId) {
            $query->where('photo_section_id', $sectionId);
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position', 'asc')
            ->columns([
                ImageColumn::make('path')
                    ->disk('thumbnails')
                    ->visibility('private')
                    ->square(),
                TextColumn::make('photoSection.name')
                    ->label('Section')
                    ->sortable(),
                TextColumn::make('position')
                    ->label('Position')
                    ->sortable(),
                TextColumn::make('alt')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->reorderable('position')
            ->filters([
                SelectFilter::make('photo_gallery_id')
                    ->relationship('photoGallery', 'name')
                    ->label('Photo Gallery')
                    ->preload(),
                SelectFilter::make('photo_section_id')
                    ->relationship('photoSection', 'name')
                    ->label('Section')
                    ->preload(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('set_as_cover')
                    ->label('Set as Cover')
                    ->icon('heroicon-o-star')
                    ->action(function (Photo $record) {
                        $record->photoGallery->update(['cover_photo_id' => $record->id]);
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('move_to_section')
                        ->label('Move to Section')
                        ->icon('heroicon-o-folder')
                        ->form([
                            Select::make('photo_section_id')
                                ->label('Section')
                                ->required()
                                ->options(function () {
                                    $galleryId = request()->get('photo_gallery_id');
                                    if (! $galleryId) {
                                        return [];
                                    }

                                    return PhotoSection::where('photo_gallery_id', $galleryId)
                                        ->pluck('name', 'id');
                                }),
                        ])
                        ->action(function (array $data, $records, PhotoPositionService $positionService) {
                            $targetSection = PhotoSection::query()->findOrFail($data['photo_section_id']);

                            foreach ($records as $record) {
                                if ($record->photo_gallery_id !== $targetSection->photo_gallery_id) {
                                    continue;
                                }

                                $positionService->moveToSection($record, $targetSection->id);
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
            'index' => ListPhotos::route('/'),
            'create' => CreatePhoto::route('/create'),
            'edit' => EditPhoto::route('/{record}/edit'),
        ];
    }
}
