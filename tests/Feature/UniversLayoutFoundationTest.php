<?php

use App\Models\Univers;
use App\Models\UniversLayout;
use App\Services\UniversLayoutService;
use App\UniversLayoutPresets;
use Illuminate\Support\Facades\Storage;

test('the layout singleton has a versioned document', function () {
    $layout = UniversLayout::singleton();

    expect($layout->exists)->toBeTrue()
        ->and($layout->mode)->toBeString()
        ->and($layout->version)->toBe(1)
        ->and($layout->layout)->toHaveKeys(['preset', 'items']);
});

test('legacy presets expose the current image count patterns', function () {
    expect(UniversLayoutPresets::all())->toHaveKeys([
        'legacy-9',
        'legacy-10',
        'legacy-11',
        'legacy-12',
        'legacy-13',
    ])
        ->and(UniversLayoutPresets::dimensions('wide'))->toBe(['width' => 6, 'height' => 3])
        ->and(UniversLayoutPresets::dimensions('tall'))->toBe(['width' => 3, 'height' => 6]);
});

test('standard tiles use a four by three footprint', function () {
    expect(UniversLayoutPresets::dimensions('standard'))->toBe(['width' => 4, 'height' => 3]);
});

test('preset options include a compatible layout for smaller Univers collections', function () {
    expect(collect(UniversLayoutPresets::all())->filter(fn (array $preset): bool => count($preset['items']) === 3))->not->toBeEmpty();
});

test('univers source path falls back to its display path for legacy records', function () {
    $univers = Univers::withoutEvents(fn (): Univers => Univers::query()->create([
        'path' => 'univers/example.jpg',
    ]));

    expect($univers->source_path)->toBe('univers/example.jpg');
});

test('the homepage renders responsive optimized image markup for Univers', function () {
    Storage::fake('public');

    foreach ([300, 500, 800] as $width) {
        Storage::disk('public')->put("univers/1/{$width}.jpg", 'jpeg');
        Storage::disk('public')->put("univers/1/{$width}.webp", 'webp');
    }

    $univers = Univers::withoutEvents(fn (): Univers => Univers::query()->create([
        'path' => 'univers/example.jpg',
        'derivatives' => [
            '300' => ['jpg' => 'univers/1/300.jpg', 'webp' => 'univers/1/300.webp'],
            '500' => ['jpg' => 'univers/1/500.jpg', 'webp' => 'univers/1/500.webp'],
            '800' => ['jpg' => 'univers/1/800.jpg', 'webp' => 'univers/1/800.webp'],
        ],
        'processing_status' => 'processed',
    ]));

    $response = $this->get('/');

    $response->assertSuccessful()
        ->assertSee('type="image/webp"', false)
        ->assertSee('300w', false);
});

test('private Univers sources require a valid signature', function () {
    $univers = Univers::withoutEvents(fn (): Univers => Univers::query()->create([
        'path' => 'univers/example.jpg',
    ]));

    $this->get(route('univers.source', $univers))->assertForbidden();
});

test('invalid persisted custom layouts fall back to generic placement', function () {
    $first = Univers::withoutEvents(fn (): Univers => Univers::query()->create(['path' => 'univers/one.jpg']));
    $second = Univers::withoutEvents(fn (): Univers => Univers::query()->create(['path' => 'univers/two.jpg']));
    $layout = UniversLayout::singleton();
    $layout->update([
        'mode' => 'custom',
        'layout' => [
            'preset' => null,
            'items' => [
                ['univers_id' => $first->id, 'x' => 0, 'y' => 0, 'width' => 6, 'height' => 3],
                ['univers_id' => $second->id, 'x' => 3, 'y' => 0, 'width' => 6, 'height' => 3],
            ],
        ],
    ]);

    $resolved = app(UniversLayoutService::class)->resolve(Univers::query()->orderBy('position')->get(), $layout);

    expect($resolved['items'][0]['width'])->toBe(4)
        ->and($resolved['items'][1]['y'])->toBe(1);
});
