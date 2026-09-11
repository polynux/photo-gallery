<?php

use App\Filament\Pages\UniversLayout;
use App\Jobs\GenerateUniversDerivatives;
use App\Models\Univers;
use App\Models\UniversLayout as UniversLayoutModel;
use App\Models\User;
use App\Services\UniversLayoutService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->create());
});

test('admin can open the Univers layout page', function () {
    $this->get('/administrafion/univers-layout')
        ->assertSuccessful()
        ->assertSee('Homepage Univers')
        ->assertSee('Custom 12-column grid');
});

test('custom layout rejects overlapping tiles', function () {
    $first = Univers::withoutEvents(fn (): Univers => Univers::query()->create(['path' => 'univers/one.jpg']));
    $second = Univers::withoutEvents(fn (): Univers => Univers::query()->create(['path' => 'univers/two.jpg']));

    Livewire::test(UniversLayout::class)
        ->set('mode', 'custom')
        ->set('layoutItems', [
            ['univers_id' => $first->id, 'x' => 0, 'y' => 0, 'width' => 6, 'height' => 3],
            ['univers_id' => $second->id, 'x' => 3, 'y' => 0, 'width' => 6, 'height' => 3],
        ])
        ->call('saveLayout')
        ->assertStatus(422);
});

test('admin can queue all Univers images for processing', function () {
    Queue::fake();
    Univers::withoutEvents(fn (): Univers => Univers::query()->create(['path' => 'univers/one.jpg']));

    Livewire::test(UniversLayout::class)->call('processAll');

    Queue::assertPushed(GenerateUniversDerivatives::class);
});

test('custom layout rejects duplicate images', function () {
    $univers = Univers::withoutEvents(fn (): Univers => Univers::query()->create(['path' => 'univers/one.jpg']));

    Livewire::test(UniversLayout::class)
        ->set('mode', 'custom')
        ->set('layoutItems', [
            ['univers_id' => $univers->id, 'x' => 0, 'y' => 0, 'width' => 3, 'height' => 3],
            ['univers_id' => $univers->id, 'x' => 3, 'y' => 0, 'width' => 3, 'height' => 3],
        ])
        ->call('saveLayout')
        ->assertStatus(422);
});

test('custom layout keeps resized tile dimensions in its saved document', function () {
    $univers = Univers::withoutEvents(fn (): Univers => Univers::query()->create(['path' => 'univers/one.jpg']));

    Livewire::test(UniversLayout::class)
        ->set('mode', 'custom')
        ->set('layoutItems', [
            ['univers_id' => $univers->id, 'x' => 0, 'y' => 0, 'width' => 6, 'height' => 3],
        ])
        ->call('saveLayout');

    expect(UniversLayoutModel::singleton()->layout['items'][0])->toMatchArray([
        'width' => 6,
        'height' => 3,
    ]);
});

test('choosing a compatible preset updates the editor items immediately', function () {
    $univers = collect(range(1, 4))->map(fn (int $index): Univers => Univers::withoutEvents(fn (): Univers => Univers::query()->create([
        'path' => "univers/{$index}.jpg",
    ])));

    Livewire::test(UniversLayout::class)
        ->call('chooseMode', 'preset')
        ->assertSet('mode', 'preset')
        ->assertSet('preset', 'variation-4')
        ->assertCount('layoutItems', 4);
});

test('preset slots keep their geometry when image assignments are swapped', function () {
    $univers = collect(range(1, 4))->map(fn (int $index): Univers => Univers::withoutEvents(fn (): Univers => Univers::query()->create([
        'path' => "univers/{$index}.jpg",
    ])));

    $component = Livewire::test(UniversLayout::class)->call('chooseMode', 'preset');
    $before = $component->get('layoutItems');

    $component->call('swapPresetItems', $univers[0]->id, $univers[1]->id);
    $after = $component->get('layoutItems');

    expect($after[0]['univers_id'])->toBe($univers[1]->id)
        ->and($after[1]['univers_id'])->toBe($univers[0]->id)
        ->and($after[0]['x'])->toBe($before[0]['x'])
        ->and($after[0]['y'])->toBe($before[0]['y'])
        ->and($after[0]['width'])->toBe($before[0]['width'])
        ->and($after[0]['height'])->toBe($before[0]['height']);
});

test('generic order is persisted without spatial layout data', function () {
    $univers = collect(range(1, 3))->map(fn (int $index): Univers => Univers::withoutEvents(fn (): Univers => Univers::query()->create([
        'path' => "univers/{$index}.jpg",
    ])));

    Livewire::test(UniversLayout::class)
        ->call('chooseMode', 'generic')
        ->call('reorderGeneric', [$univers[2]->id, $univers[0]->id, $univers[1]->id])
        ->call('saveLayout');

    $layout = UniversLayoutModel::singleton();

    expect($layout->mode)->toBe('generic')
        ->and($layout->layout['order'])->toBe([$univers[2]->id, $univers[0]->id, $univers[1]->id])
        ->and($layout->layout['items'])->toBeEmpty();
});

test('preset assignments are restored from the saved document', function () {
    $univers = collect(range(1, 4))->map(fn (int $index): Univers => Univers::withoutEvents(fn (): Univers => Univers::query()->create([
        'path' => "univers/{$index}.jpg",
    ])));

    $component = Livewire::test(UniversLayout::class)->call('chooseMode', 'preset');
    $component->call('swapPresetItems', $univers[0]->id, $univers[1]->id)->call('saveLayout');

    $resolved = app(UniversLayoutService::class)->resolve(
        Univers::query()->orderBy('position')->get(),
        UniversLayoutModel::singleton(),
    );

    expect($resolved['items'][0]['univers_id'])->toBe($univers[1]->id)
        ->and($resolved['items'][1]['univers_id'])->toBe($univers[0]->id);
});

test('square presets are restored as square layouts after reload', function () {
    $univers = collect(range(1, 4))->map(fn (int $index): Univers => Univers::withoutEvents(fn (): Univers => Univers::query()->create([
        'path' => "univers/{$index}.jpg",
    ])));

    $layout = UniversLayoutModel::singleton();
    $layout->update([
        'mode' => 'preset',
        'layout' => [
            'preset' => 'square-4',
            'assignments' => $univers->pluck('id')->all(),
            'items' => [],
        ],
    ]);

    $resolved = app(UniversLayoutService::class)->resolve(
        Univers::query()->orderBy('position')->get(),
        $layout->refresh(),
    );

    expect($resolved['mode'])->toBe('preset')
        ->and($resolved['preset'])->toBe('square-4')
        ->and($resolved['items'])->toHaveCount(4)
        ->and($resolved['items'][0])->toMatchArray(['width' => 3, 'height' => 3]);
});

test('custom tiles save with the full geometry even when a dimension is one', function () {
    $univers = Univers::withoutEvents(fn (): Univers => Univers::query()->create(['path' => 'univers/one.jpg']));

    Livewire::test(UniversLayout::class)
        ->set('mode', 'custom')
        ->set('layoutItems', [
            ['univers_id' => $univers->id, 'x' => 5, 'y' => 2, 'width' => 1, 'height' => 2],
        ])
        ->call('saveLayout');

    expect(UniversLayoutModel::singleton()->layout['items'][0])->toMatchArray([
        'univers_id' => $univers->id,
        'x' => 5,
        'y' => 2,
        'width' => 1,
        'height' => 2,
    ]);
});

test('setting layout items does not re-dispatch the editor event', function () {
    $univers = Univers::withoutEvents(fn (): Univers => Univers::query()->create(['path' => 'univers/one.jpg']));

    $component = Livewire::test(UniversLayout::class)
        ->set('mode', 'custom')
        ->set('layoutItems', [
            ['univers_id' => $univers->id, 'x' => 0, 'y' => 0, 'width' => 3, 'height' => 2],
        ])
        ->call('setLayoutItems', [
            ['univers_id' => $univers->id, 'x' => 2, 'y' => 1, 'width' => 4, 'height' => 3],
        ]);

    expect($component)->assertNotDispatched('univers-layout-updated')
        ->and($component->get('isDirty'))->toBeTrue();
});

test('setPreview stores the preview and dispatches the editor event', function () {
    Livewire::test(UniversLayout::class)
        ->call('setPreview', 'mobile')
        ->assertSet('preview', 'mobile')
        ->assertDispatched('univers-layout-updated');
});

test('choosing custom mode restores the saved custom geometry', function () {
    $univers = collect(range(1, 2))->map(fn (int $index): Univers => Univers::withoutEvents(fn (): Univers => Univers::query()->create([
        'path' => "univers/{$index}.jpg",
    ])));

    UniversLayoutModel::singleton()->update([
        'mode' => 'custom',
        'layout' => [
            'preset' => null,
            'items' => [
                ['univers_id' => $univers[1]->id, 'x' => 4, 'y' => 3, 'width' => 5, 'height' => 2],
                ['univers_id' => $univers[0]->id, 'x' => 0, 'y' => 0, 'width' => 4, 'height' => 3],
            ],
        ],
    ]);

    Livewire::test(UniversLayout::class)
        ->call('chooseMode', 'custom')
        ->assertSet('mode', 'custom')
        ->assertSet('layoutItems', fn (array $items): bool => $items[0]['univers_id'] === $univers[0]->id
            && $items[0]['x'] === 0
            && $items[0]['y'] === 0
            && $items[0]['width'] === 4
            && $items[1]['univers_id'] === $univers[1]->id
            && $items[1]['x'] === 4
            && $items[1]['y'] === 3
            && $items[1]['width'] === 5);
});

test('adding an image to a saved custom layout keeps the saved tiles in place', function () {
    $first = Univers::withoutEvents(fn (): Univers => Univers::query()->create(['path' => 'univers/one.jpg']));
    $second = Univers::withoutEvents(fn (): Univers => Univers::query()->create(['path' => 'univers/two.jpg']));

    UniversLayoutModel::singleton()->update([
        'mode' => 'custom',
        'layout' => [
            'preset' => null,
            'items' => [
                ['univers_id' => $first->id, 'x' => 0, 'y' => 0, 'width' => 6, 'height' => 4],
            ],
        ],
    ]);

    $resolved = app(UniversLayoutService::class)->resolve(
        Univers::query()->orderBy('position')->get(),
        UniversLayoutModel::singleton(),
    );

    expect($resolved['mode'])->toBe('custom')
        ->and($resolved['notice'])->toBeNull()
        ->and(collect($resolved['items'])->firstWhere('univers_id', $first->id))->toMatchArray(['x' => 0, 'y' => 0, 'width' => 6, 'height' => 4])
        ->and(collect($resolved['items'])->firstWhere('univers_id', $second->id))->toMatchArray(['x' => 6, 'y' => 0, 'width' => 3, 'height' => 2]);
});

test('invalid custom layouts surface a notice when falling back to generic', function () {
    $first = Univers::withoutEvents(fn (): Univers => Univers::query()->create(['path' => 'univers/one.jpg']));
    $second = Univers::withoutEvents(fn (): Univers => Univers::query()->create(['path' => 'univers/two.jpg']));

    $layout = UniversLayoutModel::singleton();
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

    $resolved = app(UniversLayoutService::class)->resolve(
        Univers::query()->orderBy('position')->get(),
        $layout,
    );

    expect($resolved['mode'])->toBe('generic')
        ->and($resolved['notice'])->toContain('custom layout');
});

test('swapping preset slots marks the layout dirty', function () {
    $univers = collect(range(1, 4))->map(fn (int $index): Univers => Univers::withoutEvents(fn (): Univers => Univers::query()->create([
        'path' => "univers/{$index}.jpg",
    ])));

    Livewire::test(UniversLayout::class)
        ->call('chooseMode', 'preset')
        ->call('swapPresetItems', $univers[0]->id, $univers[1]->id)
        ->assertSet('isDirty', true);
});

test('focal point setters clamp values between zero and one', function () {
    $univers = Univers::withoutEvents(fn (): Univers => Univers::query()->create(['path' => 'univers/one.jpg']));

    Livewire::test(UniversLayout::class)
        ->call('selectFocalPoint', $univers->id)
        ->call('setFocalX', 1.5)
        ->call('setFocalY', -0.5)
        ->assertSet('focalX', 1.0)
        ->assertSet('focalY', 0.0);
});

test('saving the focal point persists clamped coordinates', function () {
    $univers = Univers::withoutEvents(fn (): Univers => Univers::query()->create(['path' => 'univers/one.jpg']));

    Livewire::test(UniversLayout::class)
        ->call('selectFocalPoint', $univers->id)
        ->call('setFocalX', 0.25)
        ->call('setFocalY', 0.75)
        ->call('saveFocalPoint');

    expect($univers->refresh()->focal_x)->toBe(0.25)
        ->and($univers->refresh()->focal_y)->toBe(0.75);
});
