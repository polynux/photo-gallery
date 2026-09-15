<?php

use App\Models\Photo;
use App\Models\PhotoGallery;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('gallery.generate_thumbnails', false);
});

function createDimensionsPhoto(PhotoGallery $gallery, int $width, int $height): Photo
{
    $image = imagecreatetruecolor($width, $height);
    $background = imagecolorallocate($image, 120, 140, 180);
    imagefill($image, 0, 0, $background);

    ob_start();
    imagepng($image);
    $imageContents = (string) ob_get_clean();
    imagedestroy($image);

    $path = $gallery->id.'/dimensions-'.$width.'x'.$height.'.png';
    Storage::disk('photo')->put($path, $imageContents);

    return Photo::factory()->forGallery($gallery)->create([
        'path' => $path,
        'position' => 1,
    ]);
}

test('generating thumbnails stores the grid thumbnail dimensions on the photo', function () {
    Storage::fake('photo');
    Storage::fake('thumbnails');

    $gallery = PhotoGallery::factory()->create();
    $photo = createDimensionsPhoto($gallery, 3000, 2400);

    $photo->generateThumbnail();

    expect($photo->refresh()->width)->toBe(500);
    expect($photo->refresh()->height)->toBe(400);
});

test('backfill command fills dimensions from existing thumbnails and skips photos without them', function () {
    Storage::fake('photo');
    Storage::fake('thumbnails');

    $gallery = PhotoGallery::factory()->create();
    $withThumbnail = createDimensionsPhoto($gallery, 2000, 1500);
    $withThumbnail->generateThumbnail();

    $withoutThumbnail = createDimensionsPhoto($gallery, 1000, 1000);

    expect($withThumbnail->refresh()->width)->toBe(500);

    $withThumbnail->forceFill(['width' => null, 'height' => null])->saveQuietly();

    $this->artisan('app:backfill-photo-dimensions')->assertSuccessful();

    expect($withThumbnail->refresh()->width)->toBe(500);
    expect($withThumbnail->refresh()->height)->toBe(375);
    expect($withoutThumbnail->refresh()->width)->toBeNull();
    expect($withoutThumbnail->refresh()->height)->toBeNull();
});

test('gallery view renders lazy-load markup with dimensions and keeps native fallback for unknown dimensions', function () {
    Storage::fake('photo');
    Storage::fake('thumbnails');

    $gallery = PhotoGallery::factory()->create();
    $known = createDimensionsPhoto($gallery, 3000, 2400);
    $known->generateThumbnail();
    $unknown = createDimensionsPhoto($gallery, 800, 600);

    $response = $this->withSession([
        'authenticated_gallery_'.$gallery->id => true,
    ])->get(route('public.gallery', $gallery->access_code));

    $response->assertOk();

    $knownImg = $known->fresh();
    expect($response->getContent())->toContain('data-src=');
    expect($response->getContent())->toContain('width="'.$knownImg->width.'"');
    expect($response->getContent())->toContain('height="'.$knownImg->height.'"');
    expect($response->getContent())->toContain('js-lazy-img');
    expect($response->getContent())->toContain('js-lazy-spinner');
    expect($response->getContent())->toContain('lazyRootMargin');

    expect($unknown->fresh()->width)->toBeNull();
});
