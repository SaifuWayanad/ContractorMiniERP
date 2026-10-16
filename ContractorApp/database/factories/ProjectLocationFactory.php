<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectLocation>
 */
class ProjectLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'contractor_id' => fn (array $attributes) => Project::withoutGlobalScopes()->find($attributes['project_id'])->contractor_id,
            'name' => 'Main site',
            'address' => fake()->streetAddress(),
            'city' => 'Kochi',
            'district' => 'Ernakulam',
            'state' => 'Kerala',
            'country' => 'India',
            'postal_code' => '682'.fake()->numerify('###'),
            'contact_person' => fake()->name(),
            'contact_phone' => '98'.fake()->numerify('########'),
        ];
    }

    public function forProject(Project $project): static
    {
        return $this->state(fn (): array => [
            'project_id' => $project->id,
            'contractor_id' => $project->contractor_id,
        ]);
    }
}
