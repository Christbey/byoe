<?php

namespace Database\Factories;

use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProviderStripeAccount>
 */
class ProviderStripeAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_id' => Provider::factory(),
            'stripe_account_id' => 'acct_'.fake()->unique()->regexify('[A-Za-z0-9]{16}'),
            'details_submitted' => false,
            'charges_enabled' => false,
            'payouts_enabled' => false,
        ];
    }

    /**
     * Indicate that the account is fully onboarded.
     */
    public function onboarded(): static
    {
        return $this->state(fn (array $attributes) => [
            'details_submitted' => true,
            'charges_enabled' => true,
            'payouts_enabled' => true,
        ]);
    }
}
