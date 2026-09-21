<?php

namespace App\Filament\Resources\PhotoGalleryResource\RelationManagers;

use App\Filament\Resources\PhotoGalleryResource;
use App\Models\Photo;
use App\Models\PhotoSection;
use App\Services\PhotoPositionService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('photo_section_id')
                    ->label(__('admin.common.section'))
                    ->options(fn () => PhotoSection::where('photo_gallery_id', $this->ownerRecord->id)->pluck('name', 'id'))
                    ->required()
                    ->default(fn () => PhotoSection::where('photo_gallery_id', $this->ownerRecord->id)->where('is_default', true)->first()?->id),
                FileUpload::make('path')
                    ->label(__('admin.common.path'))
                    ->disk('photo')
                    ->directory($this->ownerRecord->id)
                    ->visibility('private')
                    ->required()
                    ->image()
                    ->imageEditor(),
                TextInput::make('alt')
                    ->label(__('admin.common.alt'))
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('position', 'asc')
            ->columns([
                TextColumn::make('position')
                    ->label('#')
                    ->sortable(),
                ImageColumn::make('path')
                    ->state(fn (Photo $record): string => route('thumbnails.show', [
                        'gallery' => $record->photo_gallery_id,
                        'photo' => basename($record->path),
                    ]))
                    ->checkFileExistence(false),
                TextColumn::make('photoSection.name')
                    ->label(__('admin.common.section'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('alt')
                    ->label(__('admin.common.alt'))
                    ->limit(30),
            ])
            ->reorderable('position')
            ->filters([
                SelectFilter::make('photo_section_id')
                    ->label(__('admin.common.section'))
                    ->options(fn () => PhotoSection::where('photo_gallery_id', $this->ownerRecord->id)->pluck('name', 'id')),
            ])
            ->headerActions([
                Action::make('manage_sections')
                    ->label(__('admin.gallery.manage_sections'))
                    ->icon('heroicon-o-folder')
                    ->url(fn ($livewire) => PhotoGalleryResource::getUrl('sections', ['record' => $livewire->getOwnerRecord()->id])),
                Action::make('view_gallery')
                    ->label(__('admin.gallery.view_gallery'))
                    ->icon('heroicon-o-eye')
                    ->url(fn ($livewire) => route('public.show', $livewire->getOwnerRecord()->access_code))
                    ->openUrlInNewTab(),
                CreateAction::make()
                    ->label(__('admin.gallery.upload_photos'))
                    ->icon('heroicon-o-plus'),
                Action::make('upload_multiple')
                    ->label(__('admin.gallery.upload_photos'))
                    ->url(fn ($livewire) => PhotoGalleryResource::getUrl('upload-photos', ['record' => $livewire->getOwnerRecord()->id]))
                    ->icon('heroicon-o-arrow-up-tray'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('set_as_cover')
                    ->label(__('admin.photo.set_as_cover'))
                    ->icon('heroicon-o-star')
                    ->action(function ($record, $livewire) {
                        $livewire->getOwnerRecord()->update(['cover_photo_id' => $record->id]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('move_to_section')
                        ->label(__('admin.photo.move_to_section'))
                        ->icon('heroicon-o-folder')
                        ->form([
                            Select::make('photo_section_id')
                                ->label(__('admin.common.section'))
                                ->required()
                                ->options(fn ($livewire) => PhotoSection::where('photo_gallery_id', $livewire->getOwnerRecord()->id)->pluck('name', 'id')),
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

    public function reorder(array $orderIds): void
    {
        $sectionFilter = $this->getTableFilterState('photo_section_id');
        $sectionId = $sectionFilter['value'] ?? null;

        $positionService = app(PhotoPositionService::class);

        if ($sectionId) {
            $positionService->reindexSection($orderIds, (int) $sectionId);
        } else {
            $positionService->reindexGallery($orderIds, $this->ownerRecord->id);
        }
    }
}
