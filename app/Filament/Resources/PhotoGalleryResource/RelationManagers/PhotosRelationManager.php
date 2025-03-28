<?php

namespace App\Filament\Resources\PhotoGalleryResource\RelationManagers;

use App\Filament\Resources\PhotoGalleryResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('path')
                    ->required()
                    ->image()
                    ->imageEditor(),
                Forms\Components\TextInput::make('alt')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\ImageColumn::make('path'),
                Tables\Columns\TextColumn::make('alt')
                    ->limit(30),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('upload_multiple')
                    ->label('Upload Multiple')
                    ->url(fn ($livewire) => PhotoGalleryResource::getUrl('upload-photos', ['record' => $livewire->getOwnerRecord()->id]))
                    ->icon('heroicon-o-arrow-up-tray'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('set_as_cover')
                    ->label('Set as Cover')
                    ->icon('heroicon-o-star')
                    ->action(function ($record, $livewire) {
                        $livewire->getOwnerRecord()->update(['cover_photo_id' => $record->id]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
