<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ImportSourceController;
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

    // Auto-import from agency website
    Route::get('/agencija/auto-import', [ImportSourceController::class, 'index'])->name('agency.import-sources');
    Route::get('/agencija/auto-import/nov', [ImportSourceController::class, 'create'])->name('agency.import-sources.create');
    Route::post('/agencija/auto-import', [ImportSourceController::class, 'store'])->name('agency.import-sources.store');
    Route::get('/agencija/auto-import/{importSource}', [ImportSourceController::class, 'show'])->name('agency.import-sources.show');
    Route::post('/agencija/auto-import/{importSource}/sync', [ImportSourceController::class, 'sync'])->name('agency.import-sources.sync');
    Route::post('/agencija/auto-import/{importSource}/toggle', [ImportSourceController::class, 'toggle'])->name('agency.import-sources.toggle');
    Route::delete('/agencija/auto-import/{importSource}', [ImportSourceController::class, 'destroy'])->name('agency.import-sources.destroy');

    // Default landing for authenticated users; differs by role.
    Route::get('/dashboard', function () {
        $user = auth()->user();

        return $user->isAgency()
            ? redirect()->route('listings.mine')
            : redirect()->route('customer.profile');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/moj-profil', [CustomerController::class, 'profile'])->name('customer.profile');
    Route::patch('/moj-profil', [CustomerController::class, 'updateProfile'])->name('customer.profile.update');

    Route::get('/dopolni-profil', [CustomerController::class, 'completeProfile'])
        ->name('customer.complete-profile');
    Route::post('/dopolni-profil', [CustomerController::class, 'storeCompleteProfile'])
        ->name('customer.complete-profile.store');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');

    // Agencies
    Route::get('/agencii', [AdminController::class, 'agencies'])->name('agencies');
    Route::get('/agencii/nova', [AdminController::class, 'createAgencyForm'])->name('agencies.create');
    Route::post('/agencii', [AdminController::class, 'storeAgency'])->name('agencies.store');
    Route::patch('/users/{user}/tier', [AdminController::class, 'updateUserTier'])->name('users.update-tier');
    Route::post('/users/{user}/suspend', [AdminController::class, 'suspendAgency'])->name('users.suspend');
    Route::delete('/users/{user}/suspend', [AdminController::class, 'unsuspendAgency'])->name('users.unsuspend');
    Route::delete('/users/{user}', [AdminController::class, 'destroyAgency'])->name('users.destroy');

    // Listings
    Route::get('/listings', [AdminController::class, 'listings'])->name('listings');
    Route::post('/listings/{listing}/feature', [AdminController::class, 'featureListing'])->name('listings.feature');
    Route::delete('/listings/{listing}/feature', [AdminController::class, 'unfeatureListing'])->name('listings.unfeature');
    Route::post('/listings/{listing}/suspend', [AdminController::class, 'suspendListing'])->name('listings.suspend');
    Route::delete('/listings/{listing}/suspend', [AdminController::class, 'unsuspendListing'])->name('listings.unsuspend');
    Route::delete('/listings/{listing}', [AdminController::class, 'destroyListing'])->name('listings.destroy');

    // Tiers
    Route::get('/tiers', [AdminController::class, 'tiers'])->name('tiers');
    Route::get('/tiers/nov', [AdminController::class, 'createTierForm'])->name('tiers.create');
    Route::post('/tiers', [AdminController::class, 'storeTier'])->name('tiers.store');
    Route::get('/tiers/{tier:key}/uredi', [AdminController::class, 'editTierForm'])->name('tiers.edit');
    Route::patch('/tiers/{tier:key}', [AdminController::class, 'updateTier'])->name('tiers.update');
    Route::delete('/tiers/{tier:key}', [AdminController::class, 'destroyTier'])->name('tiers.destroy');
});

// Public agency profile (must be defined AFTER specific /agencija/* routes
// because the slug param would otherwise greedily match those keywords).
Route::get('/agencija/{agency:slug}', [AgencyController::class, 'show'])
    ->name('agency.show');

require __DIR__.'/auth.php';
