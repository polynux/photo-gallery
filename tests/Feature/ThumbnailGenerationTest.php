<?php

use App\Models\Photo;
use App\Models\PhotoGallery;
use App\Services\ThumbnailService;
use Illuminate\Support\Facades\Storage;

function createPhotoFromPng(PhotoGallery $gallery, int $width, int $height): Photo
{
    $image = imagecreatetruecolor($width, $height);
    $background = imagecolorallocate($image, 120, 140, 180);
    imagefill($image, 0, 0, $background);

    ob_start();
    imagepng($image);
    $imageContents = (string) ob_get_clean();
    imagedestroy($image);

    $path = $gallery->id . '/sample-' . $width . 'x' . $height . '.png';
    Storage::disk('photo')->put($path, $imageContents);

    return Photo::factory()->forGallery($gallery)->create([
        'path' => $path,
        'position' => 1,
    ]);
}

test('grid and display derivatives are generated as WebP scaled down to their max dimensions', function () {
    Storage::fake('photo');
    Storage::fake('thumbnails');

    $gallery = PhotoGallery::factory()->create();
    $photo = createPhotoFromPng($gallery, 3000, 2400);
    $service = app(ThumbnailService::class);

    $photo->generateThumbnail();

    $gridPath = $service->thumbnailPath($photo->path);
    $displayPath = $service->displayPath($photo->path);

    expect(Storage::disk('thumbnails')->exists($gridPath))->toBeTrue();
    expect(Storage::disk('thumbnails')->exists($displayPath))->toBeTrue();

    $gridContents = Storage::disk('thumbnails')->get($gridPath);

    expect($gridContents)->toStartWith('RIFF');

    [$gridWidth, $gridHeight] = getimagesizefromstring($gridContents);

    expect($gridWidth)->toBe(500);
    expect($gridHeight)->toBe(400);

    $displayContents = Storage::disk('thumbnails')->get($displayPath);

    expect($displayContents)->toStartWith('RIFF');

    [$displayWidth, $displayHeight] = getimagesizefromstring($displayContents);

    expect($displayWidth)->toBe(2560);
    expect($displayHeight)->toBe(2048);
});

test('small photos are not upscaled when generating thumbnails', function () {
    Storage::fake('photo');
    Storage::fake('thumbnails');

    $gallery = PhotoGallery::factory()->create();
    $photo = createPhotoFromPng($gallery, 800, 600);
    $service = app(ThumbnailService::class);

    $photo->generateThumbnail();

    $contents = Storage::disk('thumbnails')->get($service->thumbnailPath($photo->path));
    [$width, $height] = getimagesizefromstring($contents);

    expect($width)->toBe(500);
    expect($height)->toBe(375);

    $displayContents = Storage::disk('thumbnails')->get($service->displayPath($photo->path));
    [$displayWidth, $displayHeight] = getimagesizefromstring($displayContents);

    expect($displayWidth)->toBe(800);
    expect($displayHeight)->toBe(600);
});
