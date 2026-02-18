<?php

namespace Database\Factories;

use App\Models\Industry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shop>
 */
class ShopFactory extends Factory
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
            'name' => fake()->company(),
            'description' => fake()->paragraph(),
            'industry_id' => Industry::inRandomOrder()->value('id'),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->url(),
            'operating_hours' => [
                'monday' => ['open' => '07:00', 'close' => '18:00'],
                'tuesday' => ['open' => '07:00', 'close' => '18:00'],
                'wednesday' => ['open' => '07:00', 'close' => '18:00'],
                'thursday' => ['open' => '07:00', 'close' => '18:00'],
                'friday' => ['open' => '07:00', 'close' => '18:00'],
                'saturday' => ['open' => '08:00', 'close' => '16:00'],
                'sunday' => ['open' => '08:00', 'close' => '16:00'],
            ],
            'status' => 'active',
        ];
    }
}
