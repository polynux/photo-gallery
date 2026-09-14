<?php

use App\Filament\Resources\PhotoGalleryResource\Pages\EditPhotoGallery;
use App\Filament\Resources\PhotoGalleryResource\Pages\ListPhotoGalleries;
use App\Models\PhotoGallery;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
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
