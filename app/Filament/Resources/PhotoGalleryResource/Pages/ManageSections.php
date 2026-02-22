<?php

namespace App\Filament\Resources\PhotoGalleryResource\Pages;

use App\Filament\Resources\PhotoGalleryResource;
use App\Models\PhotoSection;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ManageSections extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = PhotoGalleryResource::class;

    protected static string $view = 'filament.resources.photo-gallery-resource.pages.manage-sections';

    public $record;

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
                    ->label('Position')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Section Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('photos_count')
                    ->label('Photos')
                    ->counts('photos')
                    ->sortable(),
                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
            ])
            ->reorderable('position')
            ->defaultSort('position', 'asc')
            ->actions([
                Action::make('manage_photos')
                    ->label('Manage Photos')
                    ->icon('heroicon-o-photo')
                    ->url(fn (PhotoSection $record): string => \App\Filament\Resources\PhotoResource::getUrl('index', [
                        'photo_gallery_id' => $this->record,
                        'photo_section_id' => $record->id,
                    ])),
                EditAction::make()
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Section Name'),
                    ])
                    ->modalHeading('Edit Section')
                    ->modalButton('Save')
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
                    ->label('Create Section')
                    ->modalHeading('Create New Section')
                    ->modalButton('Create')
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Section Name'),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['photo_gallery_id'] = $this->record;
                        $data['position'] = PhotoSection::where('photo_gallery_id', $this->record)
                            ->max('position') + 1 ?? 1;

                        return $data;
                    })
                    ->successNotification(
                        Notification::make()
                            ->title('Section Created')
                            ->success()
                            ->body('The section has been created successfully.')
                    ),
            ])
            ->emptyStateHeading('No sections found')
            ->emptyStateDescription('Create a section to organize your photos.')
            ->emptyStateIcon('heroicon-o-folder');
    }

    public function getTitle(): string
    {
        $gallery = \App\Models\PhotoGallery::find($this->record);

        return "Manage Sections - {$gallery?->name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back_to_gallery')
                ->label('Back to Gallery')
                ->icon('heroicon-o-arrow-left')
                ->url(fn (): string => PhotoGalleryResource::getUrl('edit', ['record' => $this->record])),
        ];
    }
}
