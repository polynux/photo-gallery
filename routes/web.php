<?php

use App\Http\Controllers\PublicController;
use App\Models\Univers;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('home');
})->name('home');

// Public routes
Route::prefix('gallery')->name('public.')->group(function () {
    Route::get('', [PublicController::class, 'showForm'])->name('select');
    Route::post('', [PublicController::class, 'authenticateSelect'])
        ->middleware('throttle:gallery-auth')
        ->name('authenticate-select');
    Route::get('{access_code}', [PublicController::class, 'show'])->name('show');
    Route::post('{access_code}', [PublicController::class, 'authenticate'])
        ->middleware('throttle:gallery-auth')
        ->name('authenticate');
    Route::get('{access_code}/photos', [PublicController::class, 'gallery'])->name('gallery');
    Route::get('{access_code}/download', [PublicController::class, 'download'])->name('download');
});

Route::get('photos/{gallery}/{photo}', [PublicController::class, 'showPhoto'])
    ->name('photos.show');
Route::get('thumbnails/{gallery}/{photo}', [PublicController::class, 'showThumbnail'])
    ->name('thumbnails.show');

Route::get('univers/{univers}/source', function (Univers $univers) {
    $disk = Storage::disk('photo')->exists($univers->source_path)
        ? Storage::disk('photo')
        : Storage::disk('public');

    abort_unless($disk->exists($univers->source_path), 404);

    return response()->file($disk->path($univers->source_path));
})->name('univers.source');
