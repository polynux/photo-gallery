<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhotoResource\Pages;
use App\Filament\Resources\PhotoResource\RelationManagers;
use App\Models\Photo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PhotoResource extends Resource
{
    protected static ?string $model = Photo::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('photo_gallery_id')
                    ->relationship('photoGallery', 'name')
                    ->required(),
                Forms\Components\FileUpload::make('path')
                    ->directory('photos')
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
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->square(),
                Tables\Columns\TextColumn::make('alt')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('photo_gallery_id')
                    ->relationship('photoGallery', 'name')
                    ->label('Photo Gallery')
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (request()->has('photo_gallery_id')) {
            return $query->where('photo_gallery_id', request()->get('photo_gallery_id'));
        }

        return $query->whereHas('photoGallery', function (Builder $query) {
            $query;
        });
    }
}
