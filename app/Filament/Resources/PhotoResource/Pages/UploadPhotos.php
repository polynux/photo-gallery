<?php

namespace App\Filament\Resources\PhotoGalleryResource\Pages;

use App\Filament\Resources\PhotoGalleryResource;
use App\Models\Photo;
use App\Models\PhotoGallery;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;

class UploadPhotos extends Page
{
    protected static string $resource = PhotoGalleryResource::class;
    protected static string $view = 'filament.resources.photo-resource.pages.upload-photos';

    public ?array $data = [];
    public PhotoGallery $photoGallery;

    public function mount(PhotoGallery $record): void
    {
        $this->photoGallery = $record;
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('photos')
                    ->label('Upload Photos')
                    ->multiple()
                    ->image()
                    ->maxFiles(50)
                    ->directory("photos/{$this->photoGallery->id}")
                    ->required(),
                TextInput::make('default_alt')
                    ->label('Default Alt Text')
                    ->helperText('This will be applied to all uploaded photos. You can change individual alt texts later.')
                    ->maxLength(255),
            ]);
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $photos = [];
        foreach ($data['photos'] as $path) {
            $photo = Photo::create([
                'photo_gallery_id' => $this->record->id,
                'path' => $path,
                'alt' => $data['default_alt'] ?? null,
            ]);

            $photos[] = $photo;
        }

        // If this is the first upload and no cover photo is set, use the first uploaded photo
        if (!$this->record->cover_photo_id && count($photos) > 0) {
            $this->record->update(['cover_photo_id' => $photos[0]->id]);
        }

        $this->reset('data');

        $this->notify('success', 'Photos uploaded successfully');
    }
}
