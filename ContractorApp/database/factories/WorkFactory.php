<?php

namespace Database\Factories;

use App\Enums\WorkPriority;
use App\Enums\WorkStatus;
use App\Models\Project;
use App\Models\Work;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Work>
 */
class WorkFactory extends Factory
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
            'client_id' => fn (array $attributes) => Project::withoutGlobalScopes()->find($attributes['project_id'])->client_id,
            'assigned_to' => null,
            'title' => fake()->randomElement(['Site inspection', 'Foundation pour', 'Material delivery', 'Electrical rough-in', 'Client walkthrough']),
            'description' => fake()->sentence(),
            'priority' => WorkPriority::Medium,
            'status' => WorkStatus::Pending,
            'start_date' => today(),
            'due_date' => today()->addDays(fake()->numberBetween(3, 30)),
        ];
    }

    public function forProject(Project $project): static
    {
        return $this->state(fn (): array => [
            'project_id' => $project->id,
            'contractor_id' => $project->contractor_id,
            'client_id' => $project->client_id,
        ]);
    }

    public function withStatus(WorkStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }

    public function dueInDays(int $days): static
    {
        return $this->state(fn (): array => ['due_date' => today()->addDays($days)]);
    }
}
