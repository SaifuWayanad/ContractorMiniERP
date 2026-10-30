<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Contractor;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state. The client belongs to the same contractor as the project.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contractor_id' => Contractor::factory(),
            'client_id' => fn (array $attributes) => Client::factory()->state(['contractor_id' => $attributes['contractor_id']]),
            'name' => fake()->streetName().' Residency',
            'project_number' => 'PRJ-'.fake()->unique()->numerify('#####'),
            'type_of_work' => fake()->randomElement(['Residential – House', 'Residential – Apartment', 'Commercial – Office', 'Renovation']),
            'description' => fake()->sentence(),
            'start_date' => today()->subDays(fake()->numberBetween(5, 60)),
            'expected_end_date' => today()->addDays(fake()->numberBetween(40, 200)),
            'actual_end_date' => null,
            'status' => ProjectStatus::Active,
            'budget' => fake()->numberBetween(5, 500) * 10000,
            'brand_preferred' => fake()->randomElement(['UltraTech', 'ACC', 'Ramco', null]),
            'expected_qty' => fake()->numberBetween(100, 20000),
            'specification_details' => null,
            'notes' => null,
        ];
    }

    /**
     * A project for the given client (and its contractor).
     */
    public function forClient(Client $client): static
    {
        return $this->state(fn (): array => [
            'contractor_id' => $client->contractor_id,
            'client_id' => $client->id,
        ]);
    }

    public function withStatus(ProjectStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }

    /**
     * A project whose expected end date is the given number of days from today.
     */
    public function dueInDays(int $days): static
    {
        return $this->state(fn (): array => ['expected_end_date' => today()->addDays($days)]);
    }
}
