<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $servicePrice = fake()->randomFloat(2, 50, 500);
        $platformFeePercentage = config('marketplace.platform_fee_percentage', 15.0);
        $platformFee = round($servicePrice * ($platformFeePercentage / 100), 2);
        $providerPayout = round($servicePrice - $platformFee, 2);

        return [
            'service_request_id' => ServiceRequest::factory(),
            'provider_id' => Provider::factory(),
            'service_price' => $servicePrice,
            'platform_fee' => $platformFee,
            'provider_payout' => $providerPayout,
            'status' => 'pending',
            'accepted_at' => now(),
            'completed_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ];
    }

    /**
     * Indicate that the booking is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    /**
     * Indicate that the booking is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    /**
     * Indicate that the booking is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the booking is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => fake()->sentence(),
        ]);
    }
}
