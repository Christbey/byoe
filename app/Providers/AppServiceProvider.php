<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->validateStripeConfiguration();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    /**
     * Validate required Stripe configuration is present in production.
     */
    protected function validateStripeConfiguration(): void
    {
        if (! app()->isProduction()) {
            return;
        }

        $requiredKeys = [
            'stripe.secret_key' => 'STRIPE_SECRET_KEY',
            'stripe.publishable_key' => 'STRIPE_PUBLISHABLE_KEY',
            'stripe.webhook_secret' => 'STRIPE_WEBHOOK_SECRET',
        ];

        $missing = [];

        foreach ($requiredKeys as $configKey => $envName) {
            if (empty(config($configKey))) {
                $missing[] = $envName;
            }
        }

        if (! empty($missing)) {
            throw new \RuntimeException(
                'Missing required Stripe configuration: '.implode(', ', $missing).
                '. Please set these environment variables before running in production.'
            );
        }

        // Warn if using test keys in production
        $secretKey = config('stripe.secret_key');
        if ($secretKey && str_starts_with($secretKey, 'sk_test_')) {
            logger()->warning('Using Stripe TEST keys in production environment. Please switch to live keys.');
        }
    }
}
