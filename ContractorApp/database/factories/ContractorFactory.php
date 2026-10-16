<?php

namespace Database\Factories;

use App\Enums\ContractorStatus;
use App\Models\Contractor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contractor>
 */
class ContractorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => fake()->company().' Constructions',
            'contact_person' => fake()->name(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => '98'.fake()->numerify('########'),
            'address' => fake()->streetAddress(),
            'city' => 'Kochi',
            'state' => 'Kerala',
            'country' => 'India',
            'postal_code' => '682'.fake()->numerify('###'),
            'status' => ContractorStatus::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => ContractorStatus::Inactive]);
    }
}
