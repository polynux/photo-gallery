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
        ->and(UniversLayoutPresets::dimensions('wide'))->toBe(['width' => 6, 'height' => 2])
        ->and(UniversLayoutPresets::dimensions('tall'))->toBe(['width' => 3, 'height' => 4]);
});

test('standard tiles use a three by two footprint', function () {
    expect(UniversLayoutPresets::dimensions('standard'))->toBe(['width' => 3, 'height' => 2]);
});

test('rectangular preset dimensions are available alongside square variations', function () {
    expect(UniversLayoutPresets::dimensions('large'))->toBe(['width' => 6, 'height' => 4])
        ->and(UniversLayoutPresets::dimensions('wide'))->toBe(['width' => 6, 'height' => 2])
        ->and(UniversLayoutPresets::dimensions('tall'))->toBe(['width' => 3, 'height' => 4])
        ->and(UniversLayoutPresets::dimensions('standard'))->toBe(['width' => 3, 'height' => 2])
        ->and(UniversLayoutPresets::dimensions('square'))->toBe(['width' => 3, 'height' => 3])
        ->and(UniversLayoutPresets::all())->toHaveKey('square-13');
});

test('preset options include a compatible layout for smaller Univers collections', function () {
    expect(collect(UniversLayoutPresets::all())->filter(fn (array $preset): bool => count($preset['items']) === 3))->not->toBeEmpty();
});

test('preset positions fill the twelve column editor without overlap', function () {
    $items = [
        UniversLayoutPresets::dimensions('large'),
        UniversLayoutPresets::dimensions('wide'),
        UniversLayoutPresets::dimensions('tall'),
        UniversLayoutPresets::dimensions('standard'),
    ];

    $positions = UniversLayoutPresets::positions($items);

    expect($positions)->toHaveCount(4)
        ->and($positions[0])->toBe(['x' => 0, 'y' => 0])
        ->and($positions[1])->toBe(['x' => 6, 'y' => 0])
        ->and($positions[2])->toBe(['x' => 6, 'y' => 2])
        ->and($positions[3])->toBe(['x' => 9, 'y' => 2]);
});

test('legacy preset positions use the original three-unit row and column rhythm', function () {
    $preset = UniversLayoutPresets::all()['legacy-13'];
    $positions = UniversLayoutPresets::positions($preset['items'], 3, 3);

    expect($positions)->toHaveCount(13)
        ->and(collect($positions)->every(fn (array $position): bool => $position['x'] % 3 === 0))->toBeTrue();
});

test('variation thirteen uses a complete explicit rectangular template', function () {
    $preset = UniversLayoutPresets::all()['variation-13'];
    $positions = UniversLayoutPresets::positionsFor('variation-13', $preset['items']);
    $occupied = [];

    foreach ($preset['items'] as $index => $item) {
        $position = $positions[$index];

        for ($y = $position['y']; $y < $position['y'] + $item['height']; $y++) {
            for ($x = $position['x']; $x < $position['x'] + $item['width']; $x++) {
                expect($occupied[$y][$x] ?? false)->toBeFalse();
                $occupied[$y][$x] = true;
            }
        }
    }

    for ($y = 0; $y < 10; $y++) {
        for ($x = 0; $x < 12; $x++) {
            expect($occupied[$y][$x] ?? false)->toBeTrue();
        }
    }
});

test('legacy thirteen uses a complete explicit rectangular template', function () {
    $preset = UniversLayoutPresets::all()['legacy-13'];
    $positions = UniversLayoutPresets::positionsFor('legacy-13', $preset['items']);
    $occupied = [];

    foreach ($preset['items'] as $index => $item) {
        $position = $positions[$index];

        for ($y = $position['y']; $y < $position['y'] + $item['height']; $y++) {
            for ($x = $position['x']; $x < $position['x'] + $item['width']; $x++) {
                expect($occupied[$y][$x] ?? false)->toBeFalse();
                $occupied[$y][$x] = true;
            }
        }
    }

    for ($y = 0; $y < 12; $y++) {
        for ($x = 0; $x < 12; $x++) {
            expect($occupied[$y][$x] ?? false)->toBeTrue();
        }
    }
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

    expect($resolved['items'][0]['width'])->toBe(3)
        ->and($resolved['items'][1]['y'])->toBe(1);
});
