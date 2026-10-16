<?php

namespace Database\Factories;

use App\Models\Contractor;
use App\Models\Dealer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dealer>
 */
class DealerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contractor_id' => Contractor::factory(),
            'name' => fake()->company().' Traders',
            'city' => fake()->city(),
            'phone' => '94'.fake()->numerify('########'),
        ];
    }
}
