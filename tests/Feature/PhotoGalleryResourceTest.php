<?php

use App\Filament\Resources\PhotoGalleryResource\Pages\CreatePhotoGallery;
use App\Filament\Resources\PhotoGalleryResource\Pages\EditPhotoGallery;
use App\Filament\Resources\PhotoGalleryResource\Pages\ListPhotoGalleries;
use App\Models\Photo;
use App\Models\PhotoGallery;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('gallery.generate_thumbnails', false);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->create(['email' => 'admin@example.com']));
});

test('edit form displays the gallery password as plain text', function () {
    $gallery = PhotoGallery::factory()->create([
        'access_code' => 'EDITPWD1',
        'password' => 'secret-password',
    ]);

    $page = Livewire::test(EditPhotoGallery::class, ['record' => $gallery->getKey()]);

    $page->assertSuccessful();

    expect($page->instance()->data['password'] ?? null)->toBe('secret-password');
    expect($gallery->refresh()->password)->toBe('secret-password');
});

test('listing table renders a view gallery action pointing to the public gallery', function () {
    $gallery = PhotoGallery::factory()->create([
        'access_code' => 'LISTING1',
        'password' => 'secret-password',
    ]);

    Livewire::test(ListPhotoGalleries::class)
        ->assertSuccessful()
        ->callTableAction('view_gallery', $gallery);
});

test('listing table resolves a cover image url from the photo disk', function () {
    Storage::fake('photo');

    $gallery = PhotoGallery::factory()->create([
        'access_code' => 'COVERURL',
    ]);
    $section = $gallery->sections()->where('is_default', true)->firstOrFail();
    Storage::disk('photo')->put($gallery->id.'/cover.jpg', 'cover-content');
    $photo = Photo::create([
        'photo_gallery_id' => $gallery->id,
        'photo_section_id' => $section->id,
        'path' => $gallery->id.'/cover.jpg',
        'position' => 1,
    ]);
    $gallery->update(['cover_photo_id' => $photo->id]);

    $page = Livewire::test(ListPhotoGalleries::class);

    $column = $page->instance()->getTable()->getColumns()['coverPhoto.path'];

    expect($column->getDiskName())->toBe('photo');

    $imageUrl = $column->getImageUrl($gallery->coverPhoto->path);

    expect($imageUrl)->toContain($gallery->id.'/cover.jpg');
});

test('create form is pre-filled with a random 12 character password', function () {
    $page = Livewire::test(CreatePhotoGallery::class);

    $password = $page->instance()->data['password'] ?? null;

    expect($password)->toBeString();
    expect($password)->toHaveLength(12);
    expect(ctype_alnum($password))->toBeTrue();
});

test('creating a gallery stores the pre-filled random password', function () {
    $page = Livewire::test(CreatePhotoGallery::class);

    $password = $page->instance()->data['password'];

    $page->fillForm([
        'name' => 'Random Password Gallery',
        'password' => $password,
    ])
        ->call('create');

    $gallery = PhotoGallery::query()->where('name', 'Random Password Gallery')->firstOrFail();

    expect($gallery->password)->toBe($password);
});
