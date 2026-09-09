<?php

use App\Filament\Pages\UniversLayout;
use App\Jobs\GenerateUniversDerivatives;
use App\Models\Univers;
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

    expect(App\Models\UniversLayout::singleton()->layout['items'][0])->toMatchArray([
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

    $layout = App\Models\UniversLayout::singleton();

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
        App\Models\UniversLayout::singleton(),
    );

    expect($resolved['items'][0]['univers_id'])->toBe($univers[1]->id)
        ->and($resolved['items'][1]['univers_id'])->toBe($univers[0]->id);
});

test('square presets are restored as square layouts after reload', function () {
    $univers = collect(range(1, 4))->map(fn (int $index): Univers => Univers::withoutEvents(fn (): Univers => Univers::query()->create([
        'path' => "univers/{$index}.jpg",
    ])));

    $layout = App\Models\UniversLayout::singleton();
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
