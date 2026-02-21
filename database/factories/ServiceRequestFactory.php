<?php

namespace Database\Factories;

use App\Models\ShopLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $serviceDate = fake()->dateTimeBetween('+1 week', '+2 months');
        $startTime = clone $serviceDate;
        $startTime->setTime(fake()->numberBetween(6, 14), 0);
        $endTime = clone $startTime;
        $endTime->modify('+'.fake()->numberBetween(4, 8).' hours');

        return [
            'shop_location_id' => ShopLocation::factory(),
            'title' => fake()->randomElement([
                'Need experienced barista',
                'Looking for morning shift barista',
                'Barista needed for weekend',
                'Experienced coffee maker required',
            ]),
            'description' => fake()->paragraph(),
            'skills_required' => fake()->randomElements([
                'espresso',
                'latte_art',
                'customer_service',
                'cash_handling',
                'inventory',
            ], fake()->numberBetween(2, 4)),
            'service_date' => $serviceDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'price' => fake()->randomFloat(2, 50, 500),
            'status' => 'open',
            'expires_at' => now()->addHours(config('marketplace.service_request.expiration_hours', 72)),
        ];
    }

    /**
     * Indicate that the service request is filled.
     */
    public function filled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'filled',
        ]);
    }

    /**
     * Indicate that the service request is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subHours(1),
        ]);
    }

    /**
     * Indicate that the service request is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
