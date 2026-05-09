<?php

use App\Http\Controllers\ListingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ListingController::class, 'home'])->name('home');

Route::get('/oglasi', [ListingController::class, 'index'])->name('listings.index');
Route::get('/oglasi/{listing}', [ListingController::class, 'show'])->name('listings.show');

Route::middleware('auth')->group(function () {
    Route::get('/agencija/nov-oglas', [ListingController::class, 'create'])->name('listings.create');
    Route::get('/agencija/moi-oglasi', [ListingController::class, 'mine'])->name('listings.mine');

    // Default landing for authenticated users; Breeze redirects here after login.
    Route::redirect('/dashboard', '/agencija/moi-oglasi')->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
