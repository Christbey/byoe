<?php

use App\Http\Middleware\EnsureHasProviderProfile;
use App\Http\Middleware\EnsureHasShopProfile;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Define API rate limiters
            RateLimiter::for('api.auth', function (Request $request) {
                return Limit::perMinute(10)->by($request->ip());
            });

            RateLimiter::for('api', function (Request $request) {
                return $request->user()
                    ? Limit::perMinute(120)->by($request->user()->id)
                    : Limit::perMinute(60)->by($request->ip());
            });

            // Marketplace routes - Web middleware (Inertia)
            Route::middleware('web')
                ->group(base_path('routes/marketplace.php'));

            // API routes - Supports BOTH web (Inertia) and API (mobile) auth
            Route::prefix('api')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Configure Sanctum for stateful/session auth (web) AND token auth (mobile)
        $middleware->statefulApi();

        // Force JSON responses for all API routes
        $middleware->api(append: [
            ForceJsonResponse::class,
        ]);

        // Register middleware aliases
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'shop.profile' => EnsureHasShopProfile::class,
            'provider.profile' => EnsureHasProviderProfile::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Force JSON responses for API routes
        $exceptions->shouldRenderJsonWhen(function ($request) {
            return $request->is('api/*');
        });

        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if (! $request->inertia()) {
                return $response;
            }

            return match ($response->getStatusCode()) {
                403 => Inertia::render('errors/Forbidden')->toResponse($request)->setStatusCode(403),
                404 => Inertia::render('errors/NotFound')->toResponse($request)->setStatusCode(404),
                500, 503 => Inertia::render('errors/ServerError')->toResponse($request)->setStatusCode($response->getStatusCode()),
                default => $response,
            };
        });
    })->create();
