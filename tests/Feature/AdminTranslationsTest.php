<?php

use App\Filament\Pages\UniversLayout;
use App\Filament\Resources\PhotoGalleryResource;
use App\Filament\Resources\PhotoResource;
use App\Filament\Resources\UniversResource;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->create(['email' => 'admin@example.com']));
});

function flattenTranslations(array $array): array
{
    $keys = [];

    foreach ($array as $key => $value) {
        if (is_array($value)) {
            foreach (flattenTranslations($value) as $nested) {
                $keys[] = "{$key}.{$nested}";
            }
        } else {
            $keys[] = $key;
        }
    }

    return $keys;
}

test('english and french admin translations have identical key sets', function () {
    $english = require lang_path('en/admin.php');
    $french = require lang_path('fr/admin.php');

    $englishKeys = collect(flattenTranslations($english))->sort()->values()->all();
    $frenchKeys = collect(flattenTranslations($french))->sort()->values()->all();

    expect($englishKeys)->toBe($frenchKeys);
});

test('admin panel renders translated labels in the application locale', function () {
    app()->setLocale('fr');

    $response = $this->get('/' . config('admin.path') . '/photo-galleries');

    $response->assertSuccessful();

    expect(__('admin.gallery.upload_photos'))->toBe('Téléverser des photos');
    expect(__('admin.univers.file'))->toBe('Fichier');
    expect(__('admin.gallery.manage_sections'))->toBe('Gérer les sections');
});

test('every univers processing status has a translation', function () {
    app()->setLocale('fr');

    collect(['unprocessed', 'queued', 'processing', 'processed', 'failed', 'partially_processed'])
        ->each(function (string $status): void {
            expect(__("admin.univers.status_{$status}"))->not->toBe("admin.univers.status_{$status}");
        });
});

test('resources resolve model and navigation labels in french', function () {
    app()->setLocale('fr');

    expect(PhotoGalleryResource::getNavigationLabel())->toBe('Galeries photo');
    expect(PhotoGalleryResource::getModelLabel())->toBe('Galerie photo');
    expect(PhotoResource::getNavigationLabel())->toBe('Photos');
    expect(UniversResource::getNavigationLabel())->toBe('Univers');
    expect(UniversLayout::getNavigationLabel())->toBe('Mise en page des univers');
    expect(__('admin.layout.presets'))->toBe('Modèles prédéfinis');
});

test('english translations resolve for every french value key', function () {
    $french = require lang_path('fr/admin.php');

    collect(flattenTranslations($french))
        ->each(function (string $key): void {
            expect(__("admin.{$key}"))->not->toBe("admin.{$key}");
        });
});
