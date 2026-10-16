<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectLocationControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Main site', 'address' => '12 Lake Rd', 'city' => 'Alappuzha',
            'district' => 'Alappuzha', 'state' => 'Kerala', 'country' => 'India', 'postal_code' => '688001',
            ...$overrides,
        ];
    }

    public function test_location_is_added_to_the_project_from_the_url(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $project = Project::factory()->for($contractor)->create();
        $otherProject = Project::factory()->for($contractor)->create();

        $this->actingAs($owner)->post(route('projects.locations.store', $project), $this->payload(['project_id' => $otherProject->id]))
            ->assertRedirect(route('projects.show', $project).'#locations');

        $this->assertDatabaseHas('project_locations', ['name' => 'Main site', 'project_id' => $project->id, 'contractor_id' => $contractor->id]);
    }

    public function test_indian_district_must_belong_to_the_state_and_pin_is_checked(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $project = Project::factory()->for($contractor)->create();

        $this->actingAs($owner)->post(route('projects.locations.store', $project), $this->payload(['district' => 'Mysuru', 'postal_code' => '012345']))
            ->assertSessionHasErrors([
                'district' => 'Select a district in the chosen state.',
                'postal_code' => 'Enter a valid 6-digit PIN code.',
            ]);
    }

    public function test_locations_outside_india_accept_free_text(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $project = Project::factory()->for($contractor)->create();

        $this->actingAs($owner)->post(route('projects.locations.store', $project), $this->payload([
            'city' => 'Dubai', 'state' => 'Dubai', 'district' => 'Al Barsha', 'country' => 'United Arab Emirates', 'postal_code' => 'AB12',
        ]))->assertSessionHasNoErrors();
    }

    public function test_latitude_and_longitude_go_together(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $project = Project::factory()->for($contractor)->create();

        $this->actingAs($owner)->post(route('projects.locations.store', $project), $this->payload(['latitude' => '10.01']))
            ->assertSessionHasErrors('longitude');
    }

    public function test_project_picker_redirects_to_the_projects_location_form(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $project = Project::factory()->for($contractor)->create();

        $this->actingAs($owner)->get(route('locations.create'))->assertOk()->assertSee($project->name);
        $this->actingAs($owner)->get(route('locations.create', ['project_id' => $project->id]))
            ->assertRedirect(route('projects.locations.create', $project));
    }
}
