<?php

use App\Models\Univers;
use App\Services\UniversImageService;
use Illuminate\Support\Facades\Storage;

function createUniversPng(int $width, int $height): string
{
    $image = imagecreatetruecolor($width, $height);
    $background = imagecolorallocate($image, 120, 140, 180);
    imagefill($image, 0, 0, $background);

    ob_start();
    imagepng($image);
    $contents = (string) ob_get_clean();
    imagedestroy($image);

    return $contents;
}

test('univers images generate public jpeg and webp derivatives without changing the source', function () {
    Storage::fake('photo');
    Storage::fake('public');

    $sourcePath = 'univers/source.png';
    Storage::disk('photo')->put($sourcePath, createUniversPng(1600, 1200));
    $univers = Univers::withoutEvents(fn (): Univers => Univers::query()->create([
        'path' => $sourcePath,
        'source_path' => $sourcePath,
    ]));

    app(UniversImageService::class)->generate($univers);

    $univers->refresh();

    expect($univers->processing_status)->toBe('processed')
        ->and(Storage::disk('photo')->get($sourcePath))->toStartWith("\x89PNG")
        ->and(Storage::disk('public')->exists("univers/{$univers->id}/300.jpg"))->toBeTrue()
        ->and(Storage::disk('public')->exists("univers/{$univers->id}/300.webp"))->toBeTrue();

    [$width, $height] = getimagesizefromstring(Storage::disk('public')->get("univers/{$univers->id}/800.jpg"));

    expect($width)->toBe(800)->and($height)->toBe(600);
});
