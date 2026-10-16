<?php

namespace Tests\Feature;

use App\Enums\WorkStatus;
use App\Models\Project;
use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_can_be_assigned_to_a_colleague(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $colleague = User::factory()->staffOf($contractor)->create();
        $project = Project::factory()->for($contractor)->create();

        $this->actingAs($owner)->post(route('works.store'), [
            'title' => 'Pour slab', 'project_id' => $project->id, 'assigned_to' => $colleague->id,
            'priority' => 'high', 'status' => 'pending', 'due_date' => today()->addDays(3)->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('works', ['title' => 'Pour slab', 'assigned_to' => $colleague->id, 'client_id' => $project->client_id]);
    }

    public function test_inactive_colleagues_cannot_be_assigned(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $inactive = User::factory()->staffOf($contractor)->inactive()->create();
        $project = Project::factory()->for($contractor)->create();

        $this->actingAs($owner)->post(route('works.store'), [
            'title' => 'X', 'project_id' => $project->id, 'assigned_to' => $inactive->id, 'priority' => 'low', 'status' => 'pending',
        ])->assertSessionHasErrors(['assigned_to' => 'Select a team member from the list.']);
    }

    public function test_mark_completed_sets_completion_time_and_logs_it(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $work = Work::factory()->forProject(Project::factory()->for($contractor)->create())->create(['title' => 'Site inspection']);

        $this->actingAs($owner)->patch(route('works.status', $work), ['status' => 'completed'])
            ->assertSessionHas('toast', '"Site inspection" marked as completed.');

        $work->refresh();
        $this->assertSame(WorkStatus::Completed, $work->status);
        $this->assertNotNull($work->completed_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'completed', 'entity_type' => 'work', 'entity_id' => $work->id, 'description' => "{$owner->name} marked \"Site inspection\" as completed"]);

        $this->actingAs($owner)->patch(route('works.status', $work), ['status' => 'in_progress']);
        $this->assertNull($work->fresh()->completed_at);
    }

    public function test_status_change_rejects_unknown_status(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $work = Work::factory()->forProject(Project::factory()->for($contractor)->create())->create();

        $this->actingAs($owner)->patch(route('works.status', $work), ['status' => 'hacked'])->assertSessionHasErrors('status');
    }

    public function test_filters_for_overdue_priority_and_pending(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $project = Project::factory()->for($contractor)->create();
        Work::factory()->forProject($project)->dueInDays(-3)->create(['title' => 'Late one', 'priority' => 'urgent']);
        Work::factory()->forProject($project)->dueInDays(-3)->withStatus(WorkStatus::Completed)->create(['title' => 'Late but done']);
        Work::factory()->forProject($project)->dueInDays(10)->create(['title' => 'Future one', 'priority' => 'low']);

        $this->actingAs($owner)->get(route('works.index', ['due' => 'overdue']))
            ->assertSee('Late one')->assertDontSee('Late but done')->assertDontSee('Future one');
        $this->actingAs($owner)->get(route('works.index', ['priority' => 'low']))
            ->assertSee('Future one')->assertDontSee('Late one');
        $this->actingAs($owner)->get(route('works.index', ['status' => 'open']))
            ->assertSee('Late one')->assertSee('Future one')->assertDontSee('Late but done');
    }
}
