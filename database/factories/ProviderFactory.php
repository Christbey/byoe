<?php

namespace Database\Factories;

use App\Models\Industry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Provider>
 */
class ProviderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'industry_id' => Industry::inRandomOrder()->value('id'),
            'bio' => fake()->paragraph(),
            'skills' => fake()->randomElements([
                'espresso',
                'latte_art',
                'customer_service',
                'cash_handling',
                'inventory',
                'food_safety',
                'barista_training',
            ], fake()->numberBetween(3, 5)),
            'years_experience' => fake()->numberBetween(1, 15),
            'average_rating' => fake()->randomFloat(2, 3.5, 5.0),
            'total_ratings' => fake()->numberBetween(0, 100),
            'completed_bookings' => fake()->numberBetween(0, 50),
            'is_active' => true,
            'vetting_status' => fake()->randomElement(['pending_review', 'approved', 'needs_attention']),
            'background_check_status' => fake()->randomElement(['pending', 'clear', 'flagged']),
            'identity_verified_at' => fake()->boolean(70) ? now()->subDays(fake()->numberBetween(1, 90)) : null,
            'vetting_completed_at' => fake()->boolean(65) ? now()->subDays(fake()->numberBetween(1, 60)) : null,
            'last_reviewed_at' => fake()->boolean(65) ? now()->subDays(fake()->numberBetween(1, 30)) : null,
            'trust_score' => fake()->numberBetween(45, 96),
            'reliability_score' => fake()->numberBetween(55, 99),
            'cancellation_count' => fake()->numberBetween(0, 10),
            'cancellation_rate' => fake()->randomFloat(2, 0, 35),
            'no_show_count' => fake()->numberBetween(0, 2),
            'dispute_count' => fake()->numberBetween(0, 4),
            'completed_without_issue_count' => fake()->numberBetween(0, 45),
            'trust_notes' => fake()->boolean(35) ? fake()->sentence() : null,
        ];
    }

    /**
     * Indicate that the provider is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'vetting_status' => 'approved',
            'background_check_status' => 'clear',
            'identity_verified_at' => now()->subDays(5),
            'vetting_completed_at' => now()->subDays(5),
            'last_reviewed_at' => now()->subDays(5),
            'trust_score' => 92,
            'reliability_score' => 95,
            'is_active' => true,
        ]);
    }

    public function needsReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'vetting_status' => 'pending_review',
            'background_check_status' => 'pending',
            'identity_verified_at' => null,
            'vetting_completed_at' => null,
            'last_reviewed_at' => null,
            'trust_score' => 48,
            'reliability_score' => 78,
        ]);
    }
}
