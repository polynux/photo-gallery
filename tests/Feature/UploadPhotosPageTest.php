<?php

use App\Filament\Resources\PhotoResource\Pages\UploadPhotos;
use App\Models\Photo;
use App\Models\PhotoGallery;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('gallery.generate_thumbnails', false);
});

test('upload page stores photos in the selected section and assigns a cover photo', function () {
    Storage::disk('photo')->put('gallery-upload/one.jpg', 'photo-one');
    Storage::disk('photo')->put('gallery-upload/two.jpg', 'photo-two');

    $gallery = PhotoGallery::factory()->create();
    $section = $gallery->sections()->where('is_default', true)->firstOrFail();
    $user = User::factory()->create(['email' => 'admin@example.com']);

    config()->set('admin.emails', ['admin@example.com']);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs($user);

    Livewire::test(UploadPhotos::class, ['record' => $gallery->getKey()])
        ->set('data', [
            'photo_section_id' => $section->id,
            'photos' => [
                'gallery-upload/one.jpg',
                'gallery-upload/two.jpg',
            ],
            'default_alt' => 'Mariage',
        ])
        ->call('submit');

    expect(Photo::query()->count())->toBe(2);

    expect(Photo::query()->pluck('photo_section_id')->unique()->all())
        ->toBe([$section->id]);

    expect($gallery->refresh()->cover_photo_id)->not->toBeNull();
    expect(Photo::query()->pluck('alt')->unique()->all())->toBe(['Mariage']);
});
