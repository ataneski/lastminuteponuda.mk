<?php

use App\Http\Controllers\ListingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ListingController::class, 'home'])->name('home');

Route::get('/oglasi', [ListingController::class, 'index'])->name('listings.index');
Route::get('/agencija/nov-oglas', [ListingController::class, 'create'])->name('listings.create');
Route::get('/oglasi/{listing}', [ListingController::class, 'show'])->name('listings.show');
