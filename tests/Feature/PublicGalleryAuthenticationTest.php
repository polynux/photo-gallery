<?php

use App\Models\PhotoGallery;

beforeEach(function () {
    config()->set('gallery.generate_thumbnails', false);
});

test('customers can authenticate with a hashed gallery password', function () {
    $gallery = PhotoGallery::factory()->create([
        'access_code' => 'ACCESS12',
        'password' => 'secret-password',
    ]);

    $this->post(route('public.authenticate', $gallery->access_code), [
        'password' => 'secret-password',
    ])->assertRedirect(route('public.gallery', $gallery->access_code))
        ->assertSessionHas('authenticated_gallery_'.$gallery->id, true);

    expect($gallery->refresh()->password)->not->toBe('secret-password');
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
