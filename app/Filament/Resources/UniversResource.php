<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UniversResource\Pages\CreateUnivers;
use App\Filament\Resources\UniversResource\Pages\EditUnivers;
use App\Filament\Resources\UniversResource\Pages\ListUnivers;
use App\Models\Univers;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UniversResource extends Resource
{
    protected static ?string $model = Univers::class;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                FileUpload::make('path')
                    ->label('Fichier')
                    ->required()
                    ->disk('public')
                    ->directory('univers')
                    ->acceptedFileTypes(['image/*'])
                    ->image()
                    ->maxSize(2048)
                    ->columnSpanFull(),
                TextInput::make('title')
                    ->maxLength(255)
                    ->label('Titre'),
                Textarea::make('description')
                    ->maxLength(65535)
                    ->label('Description'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position', 'asc')
            ->columns([
                TextColumn::make('position')
                    ->label('Position')
                    ->sortable(),
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                ImageColumn::make('path')
                    ->label('Fichier')
                    ->disk('public')
                    ->circular()
                    ->searchable(),
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('position');
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
            'index' => ListUnivers::route('/'),
            'create' => CreateUnivers::route('/create'),
            'edit' => EditUnivers::route('/{record}/edit'),
        ];
    }
}
