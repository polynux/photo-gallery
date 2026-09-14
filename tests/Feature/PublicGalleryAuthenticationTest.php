<?php

use App\Models\Photo;
use App\Models\PhotoGallery;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('gallery.generate_thumbnails', false);
});

test('customers can authenticate with a plaintext gallery password', function () {
    $gallery = PhotoGallery::factory()->create([
        'access_code' => 'ACCESS12',
        'password' => 'secret-password',
    ]);

    $this->post(route('public.authenticate', $gallery->access_code), [
        'password' => 'secret-password',
    ])->assertRedirect(route('public.gallery', $gallery->access_code))
        ->assertSessionHas('authenticated_gallery_'.$gallery->id, true);

    expect($gallery->refresh()->password)->toBe('secret-password');
});

test('gallery selection rejects an invalid password', function () {
    $gallery = PhotoGallery::factory()->create([
        'access_code' => 'ACCESS34',
        'password' => 'secret-password',
    ]);

    $this->from(route('public.select'))
        ->post(route('public.authenticate-select'), [
            'access_code' => $gallery->access_code,
            'password' => 'wrong-password',
        ])->assertRedirect(route('public.select'))
        ->assertSessionHasErrors('password');
});

test('gallery selection accepts lowercase access code and authenticates', function () {
    $gallery = PhotoGallery::factory()->create([
        'access_code' => 'SELECT12',
        'password' => 'secret-password',
    ]);

    $this->post(route('public.authenticate-select'), [
        'access_code' => 'select12',
        'password' => 'secret-password',
    ])->assertRedirect(route('public.gallery', $gallery->access_code))
        ->assertSessionHas('authenticated_gallery_'.$gallery->id, true);
});

test('gallery authentication is throttled after repeated failed attempts', function () {
    $gallery = PhotoGallery::factory()->create([
        'access_code' => 'ACCESS56',
        'password' => 'secret-password',
    ]);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('public.authenticate', $gallery->access_code), [
            'password' => 'wrong-password',
        ])->assertRedirect();
    }

    $this->post(route('public.authenticate', $gallery->access_code), [
        'password' => 'wrong-password',
    ])->assertTooManyRequests();
});

test('authenticated customers can access their gallery page', function () {
    $gallery = PhotoGallery::factory()->create([
        'access_code' => 'ACCESS78',
    ]);

    $this->withSession([
        'authenticated_gallery_'.$gallery->id => true,
    ])->get(route('public.gallery', $gallery->access_code))
        ->assertSuccessful();
});

test('authenticated admin can view any gallery without password', function () {
    $gallery = PhotoGallery::factory()->create([
        'access_code' => 'ADMINVW1',
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->create(['email' => 'admin@example.com']));

    // Login page redirects straight to the gallery
    $this->get(route('public.show', $gallery->access_code))
        ->assertRedirect(route('public.gallery', $gallery->access_code));

    // Gallery page is viewable without a customer session
    $this->get(route('public.gallery', $gallery->access_code))
        ->assertSuccessful();

    // Download streams without a customer session
    $this->get(route('public.download', $gallery->access_code))
        ->assertSuccessful();
});

test('authenticated admin can view photos without customer session', function () {
    Storage::fake('photo');
    $gallery = PhotoGallery::factory()->create([
        'access_code' => 'ADMINVW2',
    ]);
    $section = $gallery->sections()->where('is_default', true)->firstOrFail();
    Storage::disk('photo')->put($gallery->id.'/pic.jpg', 'content');
    Photo::create([
        'photo_gallery_id' => $gallery->id,
        'photo_section_id' => $section->id,
        'path' => $gallery->id.'/pic.jpg',
        'position' => 1,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->create(['email' => 'admin@example.com']));

    // File paths are prefixed by gallery ID, so the URL uses the ID
    // (Storage disk URLs generate /photos/{galleryId}/{file})
    $this->get(route('photos.show', [$gallery->id, 'pic.jpg']))
        ->assertSuccessful();
});

test('guest without session still needs the password', function () {
    $gallery = PhotoGallery::factory()->create([
        'access_code' => 'NOBYPASS',
    ]);

    $this->get(route('public.show', $gallery->access_code))
        ->assertSuccessful()
        ->assertSee('Mot de passe');

    $this->get(route('public.gallery', $gallery->access_code))
        ->assertRedirect(route('public.show', $gallery->access_code));

    $this->get(route('public.download', $gallery->access_code))
        ->assertRedirect(route('public.show', $gallery->access_code));
});
