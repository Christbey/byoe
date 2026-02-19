<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LegalController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::prefix('legal')->name('legal.')->group(function () {
    Route::get('terms', [LegalController::class, 'terms'])->name('terms');
    Route::get('privacy', [LegalController::class, 'privacy'])->name('privacy');
    Route::get('cookies', [LegalController::class, 'cookies'])->name('cookies');
    Route::get('contractor', [LegalController::class, 'contractor'])->name('contractor');
});

require __DIR__.'/settings.php';
require __DIR__.'/marketplace.php';
require __DIR__.'/impersonate.php';
