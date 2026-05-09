<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ListingController::class, 'home'])->name('home');

Route::get('/oglasi', [ListingController::class, 'index'])->name('listings.index');
Route::get('/oglasi/{listing}', [ListingController::class, 'show'])->name('listings.show');
Route::get('/oglasi/{listing}/social-card.png', [ListingController::class, 'socialCard'])->name('listings.social-card');

Route::get('/sitemap.xml', [ListingController::class, 'sitemap'])->name('sitemap');

// WhatsApp Cloud API webhook (no auth — signature-verified by controller).
// CSRF is excepted in bootstrap/app.php. Webhooks always return 200 to
// avoid Meta retry storms; spoofing is silently dropped + logged.
Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])
    ->name('whatsapp.verify');
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle'])
    ->name('whatsapp.handle');

Route::view('/planovi', 'agency.upgrade')->name('upgrade');

Route::middleware('auth')->group(function () {
    Route::get('/agencija/nov-oglas', [ListingController::class, 'create'])->name('listings.create');
    Route::get('/agencija/nov-oglas-ai', [ListingController::class, 'createAi'])->name('listings.create-ai');
    Route::get('/agencija/moi-oglasi', [ListingController::class, 'mine'])->name('listings.mine');
    Route::get('/agencija/profil', [AgencyController::class, 'edit'])->name('agency.profile.edit');
    Route::get('/agencija/analitika', [AgencyController::class, 'analytics'])->name('agency.analytics');
    Route::get('/agencija/oglas/{listing}/uredi', [ListingController::class, 'edit'])->name('listings.edit');
    Route::get('/agencija/oglas/{listing}/analitika', [ListingController::class, 'analytics'])->name('listings.analytics');
    Route::delete('/agencija/oglas/{listing}', [ListingController::class, 'destroy'])->name('listings.destroy');

    Route::redirect('/dashboard', '/agencija/moi-oglasi')->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::patch('/users/{user}/tier', [AdminController::class, 'updateUserTier'])->name('users.update-tier');
    Route::get('/listings', [AdminController::class, 'listings'])->name('listings');
    Route::post('/listings/{listing}/feature', [AdminController::class, 'featureListing'])->name('listings.feature');
    Route::delete('/listings/{listing}/feature', [AdminController::class, 'unfeatureListing'])->name('listings.unfeature');
});

// Public agency profile (must be defined AFTER specific /agencija/* routes
// because the slug param would otherwise greedily match those keywords).
Route::get('/agencija/{agency:slug}', [AgencyController::class, 'show'])
    ->name('agency.show');

require __DIR__.'/auth.php';
