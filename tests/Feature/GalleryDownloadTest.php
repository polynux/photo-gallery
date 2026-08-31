<?php

use App\Models\Photo;
use App\Models\PhotoGallery;
use App\Models\PhotoSection;
use Illuminate\Support\Facades\Storage;

function addPhotoWithJpgBytes(PhotoGallery $gallery, PhotoSection $section, int $position, string $name): Photo
{
    $image = imagecreatetruecolor(64, 64);
    $background = imagecolorallocate($image, $position * 40 % 255, 100, 150);
    imagefill($image, 0, 0, $background);

    ob_start();
    imagejpeg($image);
    $contents = (string) ob_get_clean();
    imagedestroy($image);

    Storage::disk('photo')->put($name, $contents);

    return Photo::create([
        'photo_gallery_id' => $gallery->id,
        'photo_section_id' => $section->id,
        'path' => $name,
        'position' => $position,
        'alt' => 'Sample',
    ]);
}

test('gallery download streams a zip with slugged entry paths', function () {
    Storage::fake('photo');
    Storage::fake('thumbnails');
    config()->set('gallery.generate_thumbnails', false);

    $gallery = PhotoGallery::factory()->create([
        'name' => 'Mariage Juin <2026>',
        'access_code' => 'ZIPTEST1',
    ]);

    $defaultSection = $gallery->sections()->where('is_default', true)->firstOrFail();
    $extraSection = PhotoSection::factory()->create([
        'photo_gallery_id' => $gallery->id,
        'name' => 'Cérémonie / Église',
        'position' => 2,
    ]);

    addPhotoWithJpgBytes($gallery, $defaultSection, 1, $gallery->id . '/one.jpg');
    addPhotoWithJpgBytes($gallery, $extraSection, 1, $gallery->id . '/two.jpg');

    $response = $this->withSession([
        'authenticated_gallery_' . $gallery->id => true,
    ])->get(route('public.download', $gallery->access_code));

    $response->assertSuccessful();

    $tempResource = tmpfile();
    $zipPath = stream_get_meta_data($tempResource)['uri'];
    file_put_contents($zipPath, $response->streamedContent());

    $zip = new ZipArchive;
    expect($zip->open($zipPath))->toBeTrue();

    $entries = [];
    for ($index = 0; $index < $zip->numFiles; $index++) {
        $entries[] = $zip->getNameIndex($index);
    }
    $zip->close();

    expect($entries)->toHaveCount(2);
    expect($entries[0])->toBe('mariage-juin-2026/01.jpg');
    expect($entries[1])->toBe('mariage-juin-2026/ceremonie-eglise/01.jpg');
});

test('unauthenticated visitors are redirected away from photo and thumbnail endpoints', function () {
    $gallery = PhotoGallery::factory()->create([
        'access_code' => 'NOPHOTO1',
    ]);
    $section = $gallery->sections()->where('is_default', true)->firstOrFail();
    $photo = addPhotoWithJpgBytes($gallery, $section, 1, $gallery->id . '/secret.jpg');

    // Storage disk URLs are /photos/{galleryId}/{file}, so the URL
    // parameter is the gallery ID, not the access code
    $this->get(route('photos.show', [$gallery->id, 'secret.jpg']))
        ->assertRedirect(route('public.select'));

    $this->get(route('thumbnails.show', [$gallery->id, 'secret.jpg']))
        ->assertRedirect(route('public.select'));
});
