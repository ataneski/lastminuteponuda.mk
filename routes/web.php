<?php

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ListingController::class, 'home'])->name('home');

Route::get('/oglasi', [ListingController::class, 'index'])->name('listings.index');
Route::get('/oglasi/{listing}', [ListingController::class, 'show'])->name('listings.show');

Route::get('/sitemap.xml', [ListingController::class, 'sitemap'])->name('sitemap');

Route::middleware('auth')->group(function () {
    Route::get('/agencija/nov-oglas', [ListingController::class, 'create'])->name('listings.create');
    Route::get('/agencija/moi-oglasi', [ListingController::class, 'mine'])->name('listings.mine');
    Route::get('/agencija/profil', [AgencyController::class, 'edit'])->name('agency.profile.edit');
    Route::get('/agencija/oglas/{listing}/uredi', [ListingController::class, 'edit'])->name('listings.edit');
    Route::delete('/agencija/oglas/{listing}', [ListingController::class, 'destroy'])->name('listings.destroy');

    // Default landing for authenticated users; Breeze redirects here after login.
    Route::redirect('/dashboard', '/agencija/moi-oglasi')->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Public agency profile (must be defined AFTER specific /agencija/* routes
// because the slug param would otherwise greedily match those keywords).
Route::get('/agencija/{agency:slug}', [AgencyController::class, 'show'])
    ->name('agency.show');

require __DIR__.'/auth.php';
