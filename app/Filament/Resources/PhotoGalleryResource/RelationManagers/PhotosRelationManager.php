<?php

namespace App\Filament\Resources\PhotoGalleryResource\RelationManagers;

use App\Filament\Resources\PhotoGalleryResource;
use App\Models\Photo;
use App\Models\PhotoSection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('photo_section_id')
                    ->label('Section')
                    ->options(fn () => PhotoSection::where('photo_gallery_id', $this->ownerRecord->id)->pluck('name', 'id'))
                    ->required()
                    ->default(fn () => PhotoSection::where('photo_gallery_id', $this->ownerRecord->id)->where('is_default', true)->first()?->id),
                Forms\Components\FileUpload::make('path')
                    ->disk('photo')
                    ->directory($this->ownerRecord->id)
                    ->visibility('private')
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
            ->defaultSort('position', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('position')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\ImageColumn::make('path')
                    ->disk('thumbnails')
                    ->visibility('private'),
                Tables\Columns\TextColumn::make('photoSection.name')
                    ->label('Section')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('alt')
                    ->limit(30),
            ])
            ->reorderable('position')
            ->filters([
                Tables\Filters\SelectFilter::make('photo_section_id')
                    ->label('Section')
                    ->options(fn () => PhotoSection::where('photo_gallery_id', $this->ownerRecord->id)->pluck('name', 'id')),
            ])
            ->headerActions([
                Tables\Actions\Action::make('manage_sections')
                    ->label('Manage Sections')
                    ->icon('heroicon-o-folder')
                    ->url(fn ($livewire) => PhotoGalleryResource::getUrl('sections', ['record' => $livewire->getOwnerRecord()->id])),
                Tables\Actions\Action::make('view_gallery')
                    ->label('View Gallery')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($livewire) => route('public.show', $livewire->getOwnerRecord()->access_code))
                    ->openUrlInNewTab(),
                Tables\Actions\CreateAction::make()
                    ->label('Upload Photo')
                    ->icon('heroicon-o-plus'),
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
                    Tables\Actions\BulkAction::make('move_to_section')
                        ->label('Move to Section')
                        ->icon('heroicon-o-folder')
                        ->form([
                            Forms\Components\Select::make('photo_section_id')
                                ->label('Section')
                                ->required()
                                ->options(fn ($livewire) => PhotoSection::where('photo_gallery_id', $livewire->getOwnerRecord()->id)->pluck('name', 'id')),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'photo_section_id' => $data['photo_section_id'],
                                    'position' => Photo::where('photo_section_id', $data['photo_section_id'])->max('position') + 1 ?? 1,
                                ]);
                            }
                        }),
                ]),
            ]);
    }

    public function reorder(array $orderIds): void
    {
        $sectionFilter = $this->getTableFilterState('photo_section_id');
        $sectionId = $sectionFilter['value'] ?? null;

        if ($sectionId) {
            $position = 1;
            foreach ($orderIds as $id) {
                Photo::where('id', $id)
                    ->where('photo_section_id', $sectionId)
                    ->update(['position' => $position++]);
            }
        } else {
            $galleryId = $this->ownerRecord->id;

            $position = 1;
            foreach ($orderIds as $id) {
                Photo::where('id', $id)
                    ->where('photo_gallery_id', $galleryId)
                    ->update(['position' => $position++]);
            }
        }
    }
}
