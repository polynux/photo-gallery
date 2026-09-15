<?php

use App\Models\Photo;
use App\Models\PhotoGallery;
use App\Services\ThumbnailService;
use Illuminate\Support\Facades\Storage;

function createServingPhoto(PhotoGallery $gallery, int $width, int $height): Photo
{
    $image = imagecreatetruecolor($width, $height);
    $background = imagecolorallocate($image, 120, 140, 180);
    imagefill($image, 0, 0, $background);

    ob_start();
    imagepng($image);
    $imageContents = (string) ob_get_clean();
    imagedestroy($image);

    $path = $gallery->id.'/serving-'.$width.'x'.$height.'.png';
    Storage::disk('photo')->put($path, $imageContents);

    return Photo::factory()->forGallery($gallery)->create([
        'path' => $path,
        'position' => 1,
    ]);
}

function withGallerySession(PhotoGallery $gallery): array
{
    return [
        'authenticated_gallery_'.$gallery->id => true,
    ];
}

test('thumbnail route serves webp with correct mime and private cache', function () {
    Storage::fake('photo');
    Storage::fake('thumbnails');

    $gallery = PhotoGallery::factory()->create();
    $photo = createServingPhoto($gallery, 2000, 1500);

    $photo->generateThumbnail();

    $response = $this->withSession(withGallerySession($gallery))->get(route('thumbnails.show', [
        'gallery' => $gallery->id,
        'photo' => basename($photo->path),
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/webp');
    expect($response->headers->get('Cache-Control'))->toContain('private');
    expect($response->headers->get('Cache-Control'))->toContain('max-age=604800');
});

test('display route serves display derivative with correct headers', function () {
    Storage::fake('photo');
    Storage::fake('thumbnails');

    $gallery = PhotoGallery::factory()->create();
    $photo = createServingPhoto($gallery, 2000, 1500);

    $photo->generateThumbnail();

    $response = $this->withSession(withGallerySession($gallery))->get(route('display.show', [
        'gallery' => $gallery->id,
        'photo' => basename($photo->path),
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/webp');
    expect($response->headers->get('Cache-Control'))->toContain('private');
    expect($response->headers->get('Cache-Control'))->toContain('max-age=604800');
});

test('display route falls back to grid thumbnail when display derivative is missing', function () {
    Storage::fake('photo');
    Storage::fake('thumbnails');

    $gallery = PhotoGallery::factory()->create();
    $photo = createServingPhoto($gallery, 2000, 1500);
    $service = app(ThumbnailService::class);

    $photo->generateThumbnail();
    Storage::disk('thumbnails')->delete($service->displayPath($photo->path));

    $response = $this->withSession(withGallerySession($gallery))->get(route('display.show', [
        'gallery' => $gallery->id,
        'photo' => basename($photo->path),
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/webp');
});

test('display route returns 404 when no derivative exists', function () {
    Storage::fake('photo');
    Storage::fake('thumbnails');

    $gallery = PhotoGallery::factory()->create();
    $photo = createServingPhoto($gallery, 2000, 1500);
    app(ThumbnailService::class)->delete($photo->path);

    $displayResponse = $this->withSession(withGallerySession($gallery))->get(route('display.show', [
        'gallery' => $gallery->id,
        'photo' => basename($photo->path),
    ]));

    expect($displayResponse->getStatusCode())->toBeGreaterThanOrEqual(400);

    $this->withSession(withGallerySession($gallery))->get(route('thumbnails.show', [
        'gallery' => $gallery->id,
        'photo' => basename($photo->path),
    ]))->assertNotFound();
});
