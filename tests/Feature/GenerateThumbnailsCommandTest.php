<?php

use App\Models\Photo;
use App\Models\PhotoGallery;
use Illuminate\Support\Facades\Storage;

test('thumbnail command generates missing thumbnails for a gallery', function () {
    config()->set('gallery.generate_thumbnails', false);

    $gallery = PhotoGallery::factory()->create();
    $path = $gallery->id . '/command-test.png';
    $image = imagecreatetruecolor(20, 20);
    $background = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $background);

    ob_start();
    imagepng($image);
    $imageContents = (string) ob_get_clean();
    imagedestroy($image);

    Storage::disk('photo')->put($path, $imageContents);

    Photo::factory()->forGallery($gallery)->create([
        'path' => $path,
    ]);

    Storage::disk('thumbnails')->delete($path);

    $this->artisan('app:generate-thumbnails', ['--gallery' => $gallery->id])
        ->assertExitCode(0);

    expect(Storage::disk('thumbnails')->exists($path))->toBeTrue();
});
