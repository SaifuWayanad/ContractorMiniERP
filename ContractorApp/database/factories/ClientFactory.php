<?php

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\Contractor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
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
            'name' => fake()->name(),
            'company' => fake()->optional()->company(),
            'contact_person' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '98'.fake()->numerify('########'),
            'address' => fake()->streetAddress(),
            'city' => 'Kochi',
            'state' => 'Kerala',
            'country' => 'India',
            'postal_code' => '682'.fake()->numerify('###'),
            'status' => ClientStatus::Active,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => ClientStatus::Inactive]);
    }
}
