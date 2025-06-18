<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UniversResource\Pages;
use App\Filament\Resources\UniversResource\RelationManagers;
use App\Models\Univers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UniversResource extends Resource
{
    protected static ?string $model = Univers::class;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Forms\Components\FileUpload::make('path')
                    ->label('Fichier')
                    ->required()
                    ->disk('public')
                    ->directory('univers')
                    ->acceptedFileTypes(['image/*', 'video/*'])
                    ->maxSize(2048)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('title')
                    ->maxLength(255)
                    ->label('Titre'),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->label('Description'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\ImageColumn::make('path')
                    ->label('Fichier')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => $record->path ? asset('storage/univers/' . $record->path) : null)
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListUnivers::route('/'),
            'create' => Pages\CreateUnivers::route('/create'),
            'edit' => Pages\EditUnivers::route('/{record}/edit'),
        ];
    }
}
