<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UniversResource\Pages\CreateUnivers;
use App\Filament\Resources\UniversResource\Pages\EditUnivers;
use App\Filament\Resources\UniversResource\Pages\ListUnivers;
use App\Models\Univers;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UniversResource extends Resource
{
    protected static ?string $model = Univers::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';

    public static function getNavigationLabel(): string
    {
        return __('admin.univers.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.univers.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.univers.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
                FileUpload::make('path')
                    ->label(__('admin.univers.file'))
                    ->required()
                    ->disk('photo')
                    ->directory('univers')
                    ->acceptedFileTypes(['image/*'])
                    ->image()
                    ->columnSpanFull(),
                TextInput::make('title')
                    ->maxLength(255)
                    ->label(__('admin.univers.title')),
                Textarea::make('description')
                    ->maxLength(65535)
                    ->label(__('admin.univers.description')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position', 'asc')
            ->columns([
                TextColumn::make('position')
                    ->label(__('admin.common.position'))
                    ->sortable(),
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                ImageColumn::make('preview_url')
                    ->label(__('admin.univers.file'))
                    ->circular(),
                TextColumn::make('title')
                    ->label(__('admin.univers.title'))
                    ->searchable(),
                TextColumn::make('description')
                    ->label(__('admin.univers.description'))
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('admin.univers.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
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
