<?php

use App\Models\Univers;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->create(['email' => 'admin@example.com']));
});

test('univers preview url points to the signed source route', function () {
    $univers = Univers::withoutEvents(fn (): Univers => Univers::query()->create([
        'path' => 'univers/source.jpg',
    ]));

    $url = $univers->preview_url;

    expect($url)->toContain('/univers/'.$univers->id.'/source?')
        ->and($url)->toContain('expires=')
        ->and($url)->toContain('signature=');
});

test('the signed preview url serves the univers source file', function () {
    Storage::fake('photo');

    $univers = Univers::withoutEvents(fn (): Univers => Univers::query()->create([
        'path' => 'univers/source.jpg',
        'source_path' => 'univers/source.jpg',
    ]));
    Storage::disk('photo')->put($univers->source_path, 'jpeg-bytes');

    $this->get($univers->preview_url)->assertSuccessful();
});

test('the univers listing renders image previews from the signed source url', function () {
    $univers = Univers::withoutEvents(fn (): Univers => Univers::query()->create([
        'path' => 'univers/source.jpg',
    ]));

    $this->get('/'.config('admin.path').'/univers')
        ->assertSuccessful()
        ->assertSee('/univers/'.$univers->id.'/source?', false);
});
