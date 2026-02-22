<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\IndustryController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PayoutController;
use App\Http\Controllers\Api\V1\Provider\AcceptServiceRequestController;
use App\Http\Controllers\Api\V1\Provider\BookingController as ProviderBookingController;
use App\Http\Controllers\Api\V1\Provider\DashboardController as ProviderDashboardController;
use App\Http\Controllers\Api\V1\ServiceRequestController;
use App\Http\Controllers\Api\V1\Shop\BookingController as ShopBookingController;
use App\Http\Controllers\Api\V1\Shop\DashboardController as ShopDashboardController;
use App\Http\Controllers\Api\V1\Shop\LocationController;
use App\Http\Controllers\Payment\StripeWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Public Stripe webhook endpoint (no authentication required)
// Rate limited to 100 requests per minute to prevent abuse
Route::post('/stripe/webhook', StripeWebhookController::class)
    ->middleware('throttle:100,1')
    ->name('stripe.webhook');

// ============================================================================
// API V1 Routes - Token-based authentication (Sanctum) for mobile apps
// ============================================================================
Route::prefix('v1')->group(function () {

    // Public endpoints (no authentication required)
    Route::get('industries', [IndustryController::class, 'index']);
    Route::get('industries/{industry}', [IndustryController::class, 'show']);

    // Authentication endpoints
    Route::prefix('auth')->group(function () {
        // Public endpoints (no auth required)
        Route::post('/register', [RegisterController::class, 'store'])
            ->middleware('throttle:api.auth');
        Route::post('/login', [LoginController::class, 'store'])
            ->middleware('throttle:api.auth');

        // Protected endpoints
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', LogoutController::class);
            Route::get('/me', MeController::class);
        });
    });

    // Protected endpoints (supports both Sanctum token and session authentication)
    Route::middleware(['auth:sanctum,web'])->group(function () {

        // Service Request routes
        Route::apiResource('service-requests', ServiceRequestController::class);
        Route::post('service-requests/{serviceRequest}/confirm-payment', [ServiceRequestController::class, 'confirmPayment']);

        // Booking routes
        Route::apiResource('bookings', BookingController::class)->only(['index', 'show']);
        Route::post('bookings/{booking}/complete', [BookingController::class, 'complete']);
        Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel']);
        Route::post('bookings/{booking}/rate', [BookingController::class, 'rate']);
        Route::post('bookings/{booking}/payment-intent', [BookingController::class, 'createPaymentIntent']);

        // Payment routes (shop owners)
        Route::apiResource('payments', PaymentController::class)->only(['index', 'show']);

        // Payout routes (providers)
        Route::apiResource('payouts', PayoutController::class)->only(['index', 'show']);

        // Provider routes
        Route::prefix('provider')->middleware('role:provider|admin')->group(function () {
            Route::get('/dashboard', [ProviderDashboardController::class, 'index']);
            Route::get('/bookings', [ProviderBookingController::class, 'index']);
            Route::get('/available-requests', [ProviderBookingController::class, 'available']);
            Route::post('/service-requests/{serviceRequest}/accept', AcceptServiceRequestController::class);
        });

        // Shop routes
        Route::prefix('shop')->middleware('role:shop_owner|shop_manager|admin')->group(function () {
            Route::get('/dashboard', [ShopDashboardController::class, 'index']);
            Route::get('/bookings', [ShopBookingController::class, 'index']);
            Route::apiResource('locations', LocationController::class);
        });
    });
});
