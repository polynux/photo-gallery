<?php

use App\Models\Photo;
use App\Models\PhotoGallery;
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

    $path = $gallery->id.'/sample-'.$width.'x'.$height.'.png';
    Storage::disk('photo')->put($path, $imageContents);

    return Photo::factory()->forGallery($gallery)->create([
        'path' => $path,
        'position' => 1,
    ]);
}

test('thumbnail is generated as JPEG scaled down to 1920 max dimension', function () {
    Storage::fake('photo');
    Storage::fake('thumbnails');

    $gallery = PhotoGallery::factory()->create();
    $photo = createPhotoFromPng($gallery, 3000, 2400);

    $photo->generateThumbnail();

    expect(Storage::disk('thumbnails')->exists($photo->path))->toBeTrue();

    $contents = Storage::disk('thumbnails')->get($photo->path);

    expect($contents)->toStartWith("\xFF\xD8");

    [$width, $height] = getimagesizefromstring($contents);

    expect($width)->toBe(1920);
    expect($height)->toBe(1536);
});

test('small photos are not upscaled when generating thumbnails', function () {
    Storage::fake('photo');
    Storage::fake('thumbnails');

    $gallery = PhotoGallery::factory()->create();
    $photo = createPhotoFromPng($gallery, 800, 600);

    $photo->generateThumbnail();

    $contents = Storage::disk('thumbnails')->get($photo->path);
    [$width, $height] = getimagesizefromstring($contents);

    expect($width)->toBe(800);
    expect($height)->toBe(600);
});
