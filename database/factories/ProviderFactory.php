<?php

namespace Database\Factories;

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
}
