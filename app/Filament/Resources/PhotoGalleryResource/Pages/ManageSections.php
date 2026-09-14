<?php

namespace App\Filament\Resources\PhotoGalleryResource\Pages;

use App\Filament\Resources\PhotoGalleryResource;
use App\Filament\Resources\PhotoResource;
use App\Models\PhotoGallery;
use App\Models\PhotoSection;
use Filament\Actions\Action as HeaderAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ManageSections extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = PhotoGalleryResource::class;

    public $record;

    protected string $view = 'filament.resources.photo-gallery-resource.pages.manage-sections';

    public function mount($record): void
    {
        $this->record = $record;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PhotoSection::query()
                    ->where('photo_gallery_id', $this->record)
                    ->orderBy('position')
            )
            ->columns([
                TextColumn::make('position')
                    ->label(__('admin.common.position'))
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('admin.common.section_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('photos_count')
                    ->label(__('admin.common.photos'))
                    ->counts('photos')
                    ->sortable(),
                IconColumn::make('is_default')
                    ->label(__('admin.sections.default'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
            ])
            ->reorderable('position')
            ->defaultSort('position', 'asc')
            ->recordActions([
                HeaderAction::make('manage_photos')
                    ->label(__('admin.gallery.manage_photos'))
                    ->icon('heroicon-o-photo')
                    ->url(fn (PhotoSection $record): string => PhotoResource::getUrl('index', [
                        'photo_gallery_id' => $this->record,
                        'photo_section_id' => $record->id,
                    ])),
                EditAction::make()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label(__('admin.common.section_name')),
                    ])
                    ->modalHeading(__('admin.sections.edit_modal_heading'))
                    ->modalButton(__('admin.common.save'))
                    ->visible(fn (PhotoSection $record): bool => ! $record->is_default),
                DeleteAction::make()
                    ->visible(fn (PhotoSection $record): bool => ! $record->is_default)
                    ->before(function (PhotoSection $record) {
                        $defaultSection = PhotoSection::where('photo_gallery_id', $record->photo_gallery_id)
                            ->where('is_default', true)
                            ->first();

                        if ($defaultSection) {
                            $record->photos()->update(['photo_section_id' => $defaultSection->id]);
                        }
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('admin.sections.create_section'))
                    ->modalHeading(__('admin.sections.create_modal_heading'))
                    ->modalButton(__('admin.common.create'))
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label(__('admin.common.section_name')),
                    ])
                    ->mutateDataUsing(function (array $data): array {
                        $data['photo_gallery_id'] = $this->record;
                        $data['position'] = (PhotoSection::where('photo_gallery_id', $this->record)
                            ->max('position') ?? 0) + 1;

                        return $data;
                    })
                    ->successNotification(
                        Notification::make()
                            ->title(__('admin.sections.created'))
                            ->success()
                            ->body(__('admin.sections.created_body'))
                    ),
            ])
            ->emptyStateHeading(__('admin.sections.empty_heading'))
            ->emptyStateDescription(__('admin.sections.empty_description'))
            ->emptyStateIcon('heroicon-o-folder');
    }

    public function getTitle(): string
    {
        $gallery = PhotoGallery::find($this->record);

        return __('admin.gallery.page_title_sections').' - '.$gallery?->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('back_to_gallery')
                ->label(__('admin.sections.back_to_gallery'))
                ->icon('heroicon-o-arrow-left')
                ->url(fn (): string => PhotoGalleryResource::getUrl('edit', ['record' => $this->record])),
        ];
    }
}
