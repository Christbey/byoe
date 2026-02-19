<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DisputeController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Provider\AcceptServiceRequestController;
use App\Http\Controllers\Provider\AvailabilityController;
use App\Http\Controllers\Provider\AvailableRequestsController;
use App\Http\Controllers\Provider\BookingController as ProviderBookingController;
use App\Http\Controllers\Provider\CertificationsController;
use App\Http\Controllers\Provider\CompleteBookingController;
use App\Http\Controllers\Provider\DashboardController as ProviderDashboardController;
use App\Http\Controllers\Provider\EarningsController;
use App\Http\Controllers\Provider\ProfileController as ProviderProfileController;
use App\Http\Controllers\Provider\StripeSetupController;
use App\Http\Controllers\Provider\StripeSetupDashboardLinkController;
use App\Http\Controllers\Provider\StripeSetupSessionController;
use App\Http\Controllers\Provider\StripeSetupSyncController;
use App\Http\Controllers\Shop\BookingController;
use App\Http\Controllers\Shop\DashboardController;
use App\Http\Controllers\Shop\LocationController;
use App\Http\Controllers\Shop\PaymentController;
use App\Http\Controllers\Shop\PaymentMethodSaveController;
use App\Http\Controllers\Shop\ServiceRequestController;
use App\Http\Controllers\Shop\ShopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketplace Routes
|--------------------------------------------------------------------------
|
| Routes for the Coffee Shop Marketplace - shops, providers, and admin.
|
*/

// Shop/Buyer Portal
Route::prefix('shop')->name('shop.')->middleware(['auth', 'verified', 'role:shop_owner|shop_manager|admin'])->group(function () {
    // Profile setup — available before a shop profile exists
    Route::get('/profile/edit', [ShopController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ShopController::class, 'update'])->name('profile.update');

    // All other shop routes — require an existing shop profile
    Route::middleware('shop.profile')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/profile', [ShopController::class, 'show'])->name('profile');

        // Locations Resource — index redirects to profile locations tab
        Route::get('/locations', fn () => redirect()->route('shop.profile', ['tab' => 'locations']))->name('locations');
        Route::resource('locations', LocationController::class)->except(['index']);

        // Service Requests Resource
        Route::resource('service-requests', ServiceRequestController::class)->except(['edit', 'update']);
        Route::post('/service-requests/{serviceRequest}/cancel', [ServiceRequestController::class, 'destroy'])->name('service-requests.cancel');
        Route::post('/service-requests/{serviceRequest}/confirm-payment', [ServiceRequestController::class, 'confirmPayment'])->name('service-requests.confirm-payment');

        // Bookings Resource
        Route::resource('bookings', BookingController::class)->only(['index', 'show', 'destroy']);
        Route::post('/bookings/{booking}/complete', [BookingController::class, 'complete'])->name('bookings.complete');
        Route::post('/bookings/{booking}/rate', [BookingController::class, 'rate'])->name('bookings.rate');

        // Payments Resource
        Route::resource('payments', PaymentController::class)->only(['index']);
        Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');

        // Payment Method Management — page redirects to payments method tab
        Route::get('/payment', fn () => redirect()->route('shop.payments.index', ['tab' => 'method']))->name('payment');
        Route::post('/payment/save', PaymentMethodSaveController::class)->name('payment.save');
    });
});

// Provider Portal
Route::prefix('provider')->name('provider.')->middleware(['auth', 'verified', 'role:provider|admin'])->group(function () {
    // Profile setup — available before a provider profile exists
    Route::get('/profile/edit', [ProviderProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProviderProfileController::class, 'update'])->name('profile.update');

    // All other provider routes — require an existing provider profile
    Route::middleware('provider.profile')->group(function () {
        Route::get('/dashboard', ProviderDashboardController::class)->name('dashboard');
        Route::get('/profile', [ProviderProfileController::class, 'show'])->name('profile');
        Route::get('/available-requests', AvailableRequestsController::class)->name('available-requests');
        Route::post('/requests/{serviceRequest}/accept', AcceptServiceRequestController::class)->name('requests.accept');

        // Bookings Resource
        Route::resource('bookings', ProviderBookingController::class)->only(['index', 'show']);
        Route::post('/bookings/{booking}/complete', CompleteBookingController::class)->name('bookings.complete');
        Route::post('/bookings/{booking}/rate', [ProviderBookingController::class, 'rate'])->name('bookings.rate');

        Route::get('/earnings', EarningsController::class)->name('earnings');
        // Legacy redirects — payouts and ratings are now embedded in their respective pages
        Route::get('/payouts', fn () => redirect()->route('provider.earnings', ['tab' => 'payouts']))->name('payouts');
        Route::get('/ratings', fn () => redirect()->route('provider.profile'))->name('ratings');
        Route::get('/availability', fn () => redirect()->route('provider.profile'))->name('availability');
        Route::put('/availability', [AvailabilityController::class, 'update'])->name('availability.update');

        Route::get('/certifications', [CertificationsController::class, 'show'])->name('certifications');
        Route::post('/certifications', [CertificationsController::class, 'store'])->name('certifications.store');
        Route::delete('/certifications/{certId}', [CertificationsController::class, 'destroy'])->name('certifications.destroy');

        Route::get('/stripe-setup', StripeSetupController::class)->name('stripe-setup');
        Route::post('/stripe-setup/session', StripeSetupSessionController::class)->name('stripe-setup.session');
        Route::post('/stripe-setup/dashboard-link', StripeSetupDashboardLinkController::class)->name('stripe-setup.dashboard-link');
        Route::post('/stripe-setup/sync', StripeSetupSyncController::class)->name('stripe-setup.sync');
    });
});

// Admin Portal
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'role:admin|support|ops'])->group(function () {
    Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
    Route::get('/users', UserController::class)->name('users');
    Route::get('/disputes', DisputeController::class)->name('disputes');
    Route::get('/audit-logs', AuditLogController::class)->name('audit-logs');
    Route::get('/settings', SettingController::class)->name('settings');
});
