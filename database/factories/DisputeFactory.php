<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dispute>
 */
class DisputeFactory extends Factory
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
            'filed_by_user_id' => User::factory(),
            'dispute_type' => fake()->randomElement(['payment', 'service_quality', 'cancellation', 'no_show']),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['open', 'under_review', 'resolved', 'closed']),
            'resolved_by_user_id' => null,
            'resolution_notes' => null,
            'resolved_at' => null,
        ];
    }
}
