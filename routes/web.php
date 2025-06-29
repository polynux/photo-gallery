<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;

Route::get('/', function () {
    return view('home');
})->name('home');

// Public routes
Route::prefix('gallery')->name('public.')->group(function () {
    Route::get('', [PublicController::class, 'showForm'])->name('select');
    Route::post('', [PublicController::class, 'authenticateSelect'])->name('authenticate-select');
    Route::get('{access_code}', [PublicController::class, 'show'])->name('show');
    Route::post('{access_code}', [PublicController::class, 'authenticate'])->name('authenticate');
    Route::get('{access_code}/photos', [PublicController::class, 'gallery'])->name('gallery');
    Route::get('{access_code}/download', [PublicController::class, 'download'])->name('download');
});

Route::get('/photos/{gallery}/{photo}', [PublicController::class, 'showPhoto'])
    ->name('photos.show');
Route::get('/thumbnails/{gallery}/{photo}', [PublicController::class, 'showThumbnail'])
    ->name('thumbnails.show');
