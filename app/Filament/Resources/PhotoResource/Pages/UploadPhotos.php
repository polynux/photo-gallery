<?php

namespace App\Filament\Resources\PhotoResource\Pages;

use App\Filament\Resources\PhotoGalleryResource;
use App\Models\Photo;
use App\Models\PhotoGallery;
use App\Models\PhotoSection;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class UploadPhotos extends Page
{
    protected static string $resource = PhotoGalleryResource::class;

    protected string $view = 'filament.resources.photo-resource.pages.upload-photos';

    public ?array $data = [];

    public PhotoGallery $photoGallery;

    public function mount(PhotoGallery $record): void
    {
        $this->photoGallery = $record;
        $this->form->fill([
            'data' => [
                'photo_section_id' => $this->getDefaultSection()->id,
            ],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('data.photo_section_id')
                    ->label('Section')
                    ->options(fn (): array => $this->photoGallery->sections()
                        ->orderBy('position')
                        ->pluck('name', 'id')
                        ->all())
                    ->required()
                    ->preload(),
                FileUpload::make('data.photos')
                    ->label('Upload Photos')
                    ->multiple()
                    ->image()
                    ->maxFiles(50)
                    ->disk('photo')
                    ->visibility('private')
                    ->directory($this->photoGallery->id)
                    ->required(),
                TextInput::make('data.default_alt')
                    ->label('Default Alt Text')
                    ->helperText('This will be applied to all uploaded photos. You can change individual alt texts later.')
                    ->maxLength(255),
            ]);
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $photos = [];

        DB::transaction(function () use ($data, &$photos): void {
            $section = $this->photoGallery->sections()
                ->whereKey($data['data']['photo_section_id'])
                ->firstOrFail();

            foreach ($data['data']['photos'] as $path) {
                $photos[] = Photo::create([
                    'photo_gallery_id' => $this->photoGallery->id,
                    'photo_section_id' => $section->id,
                    'path' => $path,
                    'alt' => $data['data']['default_alt'] ?? null,
                ]);
            }

            if (! $this->photoGallery->cover_photo_id && $photos !== []) {
                $this->photoGallery->update(['cover_photo_id' => $photos[0]->id]);
            }
        });

        $this->form->fill([
            'data' => [
                'photo_section_id' => $data['data']['photo_section_id'],
            ],
        ]);

        Notification::make()
            ->title('Photos Uploaded')
            ->success()
            ->body('Photos uploaded successfully')
            ->send();
    }

    private function getDefaultSection(): PhotoSection
    {
        return $this->photoGallery->sections()
            ->where('is_default', true)
            ->firstOrFail();
    }
}
