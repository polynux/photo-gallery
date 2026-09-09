<?php

use App\Models\Univers;
use App\Models\UniversLayout;
use App\UniversLayoutPresets;

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

test('univers source path falls back to its display path for legacy records', function () {
    $univers = Univers::query()->create(['path' => 'univers/example.jpg']);

    expect($univers->source_path)->toBe('univers/example.jpg');
});
