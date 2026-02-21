<?php

namespace Database\Factories;

use App\Models\Industry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IndustryTemplate>
 */
class IndustryTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'industry_id' => Industry::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'skills' => fake()->words(3),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
