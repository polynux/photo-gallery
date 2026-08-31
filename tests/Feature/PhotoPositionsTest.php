<?php

use App\Models\Photo;
use App\Models\PhotoGallery;
use App\Models\PhotoSection;
use App\Services\PhotoPositionService;
use Illuminate\Support\Facades\Storage;

test('gallery-scope reorder keeps per-section positions intact', function () {
    Storage::fake('photo');
    Storage::fake('thumbnails');
    config()->set('gallery.generate_thumbnails', false);

    $gallery = PhotoGallery::factory()->create();
    $sectionA = $gallery->sections()->where('is_default', true)->firstOrFail();
    $sectionB = PhotoSection::factory()->create([
        'photo_gallery_id' => $gallery->id,
        'position' => 2,
        'name' => 'Exterieur',
    ]);

    $a1 = Photo::factory()->forGallery($gallery)->create(['photo_section_id' => $sectionA->id, 'position' => 1]);
    $a2 = Photo::factory()->forGallery($gallery)->create(['photo_section_id' => $sectionA->id, 'position' => 2]);
    $b1 = Photo::factory()->forGallery($gallery)->create(['photo_section_id' => $sectionB->id, 'position' => 1]);

    app(PhotoPositionService::class)->reindexGallery(
        [$b1->id, $a1->id, $a2->id],
        $gallery->id,
    );

    expect($a1->refresh()->position)->toBe(1);
    expect($a2->refresh()->position)->toBe(2);
    expect($b1->refresh()->position)->toBe(1);

    // Public ordering is per-section: section A photos stay after section B
    $sectionAPositions = $sectionA->photos()->orderBy('position')->pluck('position')->values();
    expect($sectionAPositions->toArray())->toBe([1, 2]);
});

test('moving a photo to another section appends it and compacts the source section', function () {
    Storage::fake('photo');
    Storage::fake('thumbnails');
    config()->set('gallery.generate_thumbnails', false);

    $gallery = PhotoGallery::factory()->create();
    $sectionA = $gallery->sections()->where('is_default', true)->firstOrFail();
    $sectionB = PhotoSection::factory()->create([
        'photo_gallery_id' => $gallery->id,
        'position' => 2,
        'name' => 'Studio',
    ]);

    $p1 = Photo::factory()->forGallery($gallery)->create(['photo_section_id' => $sectionA->id, 'position' => 1]);
    $p2 = Photo::factory()->forGallery($gallery)->create(['photo_section_id' => $sectionA->id, 'position' => 2]);
    $p3 = Photo::factory()->forGallery($gallery)->create(['photo_section_id' => $sectionA->id, 'position' => 3]);

    app(PhotoPositionService::class)->moveToSection($p2, $sectionB->id);

    expect($p2->refresh()->photo_section_id)->toBe($sectionB->id);
    expect($p2->position)->toBe(1);

    expect($p1->refresh()->position)->toBe(1);
    expect($p3->refresh()->position)->toBe(2);
});

test('deleting a photo removes its original and thumbnail files', function () {
    Storage::fake('photo');
    Storage::fake('thumbnails');
    config()->set('gallery.generate_thumbnails', false);

    $gallery = PhotoGallery::factory()->create();
    Storage::disk('photo')->put($gallery->id.'/doomed.jpg', 'content');
    Storage::disk('thumbnails')->put($gallery->id.'/doomed.jpg', 'content');

    $photo = Photo::factory()->forGallery($gallery)->create([
        'path' => $gallery->id.'/doomed.jpg',
        'position' => 1,
    ]);
    $photo->delete();

    Storage::disk('photo')->assertMissing($gallery->id.'/doomed.jpg');
    Storage::disk('thumbnails')->assertMissing($gallery->id.'/doomed.jpg');
});

test('deleting a gallery removes all photo files', function () {
    Storage::fake('photo');
    Storage::fake('thumbnails');
    config()->set('gallery.generate_thumbnails', false);

    $gallery = PhotoGallery::factory()->create();
    Storage::disk('photo')->put($gallery->id.'/a.jpg', 'content-a');
    Storage::disk('photo')->put($gallery->id.'/b.jpg', 'content-b');

    $section = $gallery->sections()->where('is_default', true)->firstOrFail();
    Photo::factory()->forGallery($gallery)->create(['path' => $gallery->id.'/a.jpg', 'position' => 1]);
    Photo::factory()->forGallery($gallery)->create(['path' => $gallery->id.'/b.jpg', 'position' => 2]);

    $gallery->delete();

    Storage::disk('photo')->assertMissing($gallery->id.'/a.jpg');
    Storage::disk('photo')->assertMissing($gallery->id.'/b.jpg');
});
