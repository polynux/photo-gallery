<?php

use App\Filament\Pages\UniversLayout;
use App\Jobs\GenerateUniversDerivatives;
use App\Models\Univers;
use App\Models\User;
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
