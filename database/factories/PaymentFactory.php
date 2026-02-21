<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'stripe_payment_intent_id' => 'pi_'.fake()->unique()->regexify('[A-Za-z0-9]{24}'),
            'amount' => fake()->randomFloat(2, 50, 500),
            'currency' => 'usd',
            'status' => 'pending',
            'payment_method_type' => null,
            'last_four' => null,
            'paid_at' => null,
            'failure_message' => null,
        ];
    }

    /**
     * Indicate that the payment is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    /**
     * Indicate that the payment succeeded.
     */
    public function succeeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'succeeded',
            'payment_method_type' => 'card',
            'last_four' => fake()->numerify('####'),
            'paid_at' => now(),
        ]);
    }

    /**
     * Indicate that the payment failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'failure_message' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the payment is refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
            'payment_method_type' => 'card',
            'last_four' => fake()->numerify('####'),
            'paid_at' => now(),
        ]);
    }
}
