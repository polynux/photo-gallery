<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;

Route::get('/', function () {
    return view('home');
});

// Public routes
Route::prefix('gallery')->name('public.')->group(function () {
    Route::get('{access_code}', [PublicController::class, 'show'])->name('show');
    Route::post('{access_code}', [PublicController::class, 'authenticate'])->name('authenticate');
    Route::get('{access_code}/photos', [PublicController::class, 'gallery'])->name('gallery');
    Route::get('{access_code}/download', [PublicController::class, 'download'])->name('download');
});
