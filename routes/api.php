<?php

use App\Http\Controllers\Payment\CreatePaymentIntentController;
use App\Http\Controllers\Payment\StripeWebhookController;
use App\Http\Controllers\Provider\AcceptServiceRequestController;
use App\Http\Controllers\Provider\ListAvailableRequestsController;
use App\Http\Controllers\Shop\ServiceRequestController;
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
Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

// ============================================================================
// API V1 Routes - Token-based authentication (Sanctum) for mobile apps
// ============================================================================
Route::prefix('v1')->group(function () {

    // Public endpoints (no auth required)
    Route::post('/auth/login', function () {
        return response()->json(['message' => 'Login endpoint - to be implemented']);
    });
    Route::post('/auth/register', function () {
        return response()->json(['message' => 'Register endpoint - to be implemented']);
    });

    // Protected endpoints (Sanctum token authentication)
    Route::middleware(['auth:sanctum'])->group(function () {

        // Provider routes (mobile app)
        Route::prefix('provider')->middleware('role:provider|admin')->group(function () {
            Route::get('/dashboard', function () {
                return response()->json(['message' => 'Provider dashboard - to be implemented']);
            });
            Route::get('/bookings', function () {
                return response()->json(['message' => 'Provider bookings - to be implemented']);
            });
            Route::get('/available-requests', function () {
                return response()->json(['message' => 'Available requests - to be implemented']);
            });
            Route::post('/requests/{serviceRequest}/accept', function () {
                return response()->json(['message' => 'Accept request - to be implemented']);
            });
        });

        // Shop routes (mobile app)
        Route::prefix('shop')->middleware('role:shop_owner|shop_manager|admin')->group(function () {
            Route::get('/dashboard', function () {
                return response()->json(['message' => 'Shop dashboard - to be implemented']);
            });
            Route::post('/service-requests', function () {
                return response()->json(['message' => 'Create service request - to be implemented']);
            });
        });
    });
});

// ============================================================================
// Legacy API Routes - Session-based auth (for current Inertia web app)
// These will be deprecated once we refactor web controllers
// ============================================================================
Route::middleware(['auth', 'verified'])->group(function () {

    // Provider routes (Inertia web - session auth)
    Route::prefix('provider')->name('provider.')->middleware('role:provider|admin')->group(function () {
        Route::get('/available-requests', ListAvailableRequestsController::class)->name('available-requests');
        Route::post('/requests/{serviceRequest}/accept', AcceptServiceRequestController::class)->name('accept-request');
    });

    // Service Request routes (Shop - Inertia web)
    Route::prefix('service-requests')->name('service-requests.')->middleware('role:shop_owner|shop_manager|admin')->group(function () {
        Route::post('/', [ServiceRequestController::class, 'store'])->name('create');
    });

    // Payment routes
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::post('/bookings/{booking}/payment-intent', CreatePaymentIntentController::class)->name('create-intent');
    });
});
