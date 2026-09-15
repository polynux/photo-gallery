<?php

use App\Models\Photo;
use App\Models\PhotoGallery;
use App\Services\ThumbnailService;
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

    $service = app(ThumbnailService::class);
    Storage::disk('thumbnails')->delete($service->thumbnailPath($path));
    Storage::disk('thumbnails')->delete($service->displayPath($path));

    $this->artisan('app:generate-thumbnails', ['--gallery' => $gallery->id])
        ->assertExitCode(0);

    expect(Storage::disk('thumbnails')->exists($service->thumbnailPath($path)))->toBeTrue();
    expect(Storage::disk('thumbnails')->exists($service->displayPath($path)))->toBeTrue();
});

test('force flag deletes and regenerates derivatives including legacy files', function () {
    config()->set('gallery.generate_thumbnails', false);

    $gallery = PhotoGallery::factory()->create();
    $path = $gallery->id . '/force-test.png';
    $image = imagecreatetruecolor(2000, 1500);
    $background = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $background);

    ob_start();
    imagepng($image);
    $imageContents = (string) ob_get_clean();
    imagedestroy($image);

    Storage::disk('photo')->put($path, $imageContents);

    $photo = Photo::factory()->forGallery($gallery)->create([
        'path' => $path,
    ]);

    $service = app(ThumbnailService::class);

    Storage::disk('thumbnails')->put($service->legacyPath($path), 'legacy jpeg content');
    Storage::disk('thumbnails')->put($service->thumbnailPath($path), 'stale webp content');
    Storage::disk('thumbnails')->put($service->displayPath($path), 'stale webp content');

    $this->artisan('app:generate-thumbnails', ['--gallery' => $gallery->id, '--force' => true])
        ->assertExitCode(0);

    expect(Storage::disk('thumbnails')->get($service->thumbnailPath($path)))->not->toBe('stale webp content');
    expect(Storage::disk('thumbnails')->get($service->displayPath($path)))->not->toBe('stale webp content');
    expect(Storage::disk('thumbnails')->exists($service->legacyPath($path)))->toBeFalse();
});
