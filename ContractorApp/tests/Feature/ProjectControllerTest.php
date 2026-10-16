<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Project;
use App\Models\ProjectLocation;
use App\Models\Role;
use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_numbers_the_project_automatically_and_keeps_merged_fields(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $client = Client::factory()->for($contractor)->create();

        $this->actingAs($owner)->post(route('projects.store'), [
            'name' => 'Green Valley Villas', 'client_id' => $client->id, 'status' => 'active',
            'type_of_work' => 'Residential – Villas', 'brand_preferred' => 'UltraTech', 'expected_qty' => 4500,
            'expected_maturity_date' => today()->addDays(20)->toDateString(), 'budget' => 3200000,
        ])->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'name' => 'Green Valley Villas', 'project_number' => 'PRJ-0001', 'contractor_id' => $contractor->id,
            'brand_preferred' => 'UltraTech', 'expected_qty' => 4500,
        ]);
    }

    public function test_project_numbers_are_unique_per_contractor_only(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $client = Client::factory()->for($contractor)->create();
        Project::factory()->forClient($client)->create(['project_number' => 'JOB-1']);
        Project::factory()->for(Contractor::factory())->create(['project_number' => 'JOB-2']);

        $payload = ['name' => 'X', 'client_id' => $client->id, 'status' => 'planning'];
        $this->actingAs($owner)->post(route('projects.store'), [...$payload, 'project_number' => 'JOB-1'])
            ->assertSessionHasErrors(['project_number' => 'Another project already uses this number.']);
        $this->actingAs($owner)->post(route('projects.store'), [...$payload, 'project_number' => 'JOB-2'])
            ->assertSessionHasNoErrors();
    }

    public function test_end_date_cannot_be_before_start_date(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $client = Client::factory()->for($contractor)->create();

        $this->actingAs($owner)->post(route('projects.store'), [
            'name' => 'X', 'client_id' => $client->id, 'status' => 'planning',
            'start_date' => '2026-10-10', 'expected_end_date' => '2026-10-01',
        ])->assertSessionHasErrors(['expected_end_date' => 'The expected end date must be on or after the start date.']);
    }

    public function test_status_change_to_completed_is_logged_as_completion(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $project = Project::factory()->for($contractor)->create(['name' => 'Tower']);

        $this->actingAs($owner)->put(route('projects.update', $project), [
            'name' => 'Tower', 'client_id' => $project->client_id, 'status' => 'completed',
        ])->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('activity_logs', ['action' => 'completed', 'entity_id' => $project->id, 'description' => "{$owner->name} marked \"Tower\" as completed"]);
    }

    public function test_deleting_a_project_soft_deletes_its_locations_and_works(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $project = Project::factory()->for($contractor)->create();
        $location = ProjectLocation::factory()->forProject($project)->create();
        $work = Work::factory()->forProject($project)->create();

        $this->actingAs($owner)->delete(route('projects.destroy', $project))->assertRedirect(route('projects.index'));

        $this->assertSoftDeleted($project);
        $this->assertSoftDeleted($location);
        $this->assertSoftDeleted($work);
    }

    public function test_index_filters_by_client_and_status(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $client = Client::factory()->for($contractor)->create();
        Project::factory()->forClient($client)->create(['name' => 'Wanted', 'status' => 'active']);
        Project::factory()->for($contractor)->create(['name' => 'Other client', 'status' => 'active']);
        Project::factory()->forClient($client)->create(['name' => 'Wrong status', 'status' => 'planning']);

        $this->actingAs($owner)->get(route('projects.index', ['client_id' => $client->id, 'status' => 'active']))
            ->assertSee('Wanted')->assertDontSee('Other client')->assertDontSee('Wrong status');
    }

    public function test_show_page_has_all_sections(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $project = Project::factory()->for($contractor)->create();

        $this->actingAs($owner)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSeeInOrder(['Project Locations', 'Works / Tasks', 'Documents'])
            ->assertSee('Client Information')
            ->assertSee('Material Requirement')
            ->assertSee('Activity');
    }

    public function test_quick_status_change_updates_project_and_sets_end_date_on_completion(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $project = Project::factory()->for($contractor)->create(['name' => 'Tower', 'status' => 'active', 'actual_end_date' => null]);

        $this->actingAs($owner)->from(route('projects.index'))
            ->patch(route('projects.status', $project), ['status' => 'on_hold'])
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('toast', '"Tower" is now On Hold.');
        $this->assertSame('on_hold', $project->fresh()->status->value);

        $this->actingAs($owner)->patch(route('projects.status', $project), ['status' => 'completed']);
        $this->assertTrue($project->fresh()->actual_end_date->isToday());
        $this->assertDatabaseHas('activity_logs', ['action' => 'completed', 'entity_id' => $project->id]);
    }

    public function test_quick_status_change_is_protected(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $project = Project::factory()->for($contractor)->create(['status' => 'active']);
        [, $otherOwner] = $this->contractorWithOwner();
        $viewer = Role::factory()->withPermissions(['projects.view'])->create();
        $viewOnly = User::factory()->ownerOf($contractor)->withRole($viewer)->create();

        $this->actingAs($otherOwner)->patch(route('projects.status', $project), ['status' => 'cancelled'])->assertNotFound();
        $this->actingAs($viewOnly)->patch(route('projects.status', $project), ['status' => 'cancelled'])->assertForbidden();
        $this->actingAs($owner)->patch(route('projects.status', $project), ['status' => 'bogus'])->assertSessionHasErrors('status');
        $this->assertSame('active', $project->fresh()->status->value);
    }

    public function test_project_page_shows_one_click_status_buttons_only_to_editors(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $project = Project::factory()->for($contractor)->create(['status' => 'active']);
        $viewer = \App\Models\Role::factory()->withPermissions(['projects.view'])->create();
        $viewOnly = \App\Models\User::factory()->ownerOf($contractor)->withRole($viewer)->create();

        $this->actingAs($owner)->get(route('projects.show', $project))
            ->assertSee('Project Status')
            ->assertSee('Click a status to update it.')
            ->assertSee('name="status" value="completed"', false)
            ->assertDontSee('name="status" value="active"', false);

        $this->actingAs($viewOnly)->get(route('projects.show', $project))
            ->assertSee('Project Status')
            ->assertDontSee('Click a status to update it.')
            ->assertDontSee(route('projects.status', $project));
    }
}
