<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Enums\WorkStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectLocation;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_cards_count_real_records(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $client = Client::factory()->for($contractor)->create();
        $active = Project::factory()->forClient($client)->withStatus(ProjectStatus::Active)->create();
        Project::factory()->forClient($client)->withStatus(ProjectStatus::Completed)->create();
        Project::factory()->forClient($client)->withStatus(ProjectStatus::Planning)->create();
        ProjectLocation::factory()->forProject($active)->count(2)->create();
        Work::factory()->forProject($active)->count(2)->create();
        Work::factory()->forProject($active)->withStatus(WorkStatus::Completed)->create();

        $summary = $this->actingAs($owner)->get(route('dashboard'))->assertOk()->viewData('summary');

        $this->assertSame(1, $summary['clients']);
        $this->assertSame(3, $summary['projects']);
        $this->assertSame(1, $summary['active_projects']);
        $this->assertSame(1, $summary['completed_projects']);
        $this->assertSame(2, $summary['pending_works']);
        $this->assertSame(2, $summary['locations']);
    }

    public function test_greets_the_user_and_shows_status_distribution(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        Project::factory()->for($contractor)->withStatus(ProjectStatus::OnHold)->count(2)->create();

        $response = $this->actingAs($owner)->get(route('dashboard'))->assertSee(', '.$owner->firstName());

        $this->assertSame(2, $response->viewData('statusDistribution')['On Hold']);
        $this->assertSame(0, $response->viewData('statusDistribution')['Active']);
    }

    public function test_period_filter_counts_only_records_created_in_range(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        Client::factory()->for($contractor)->create(['created_at' => now()->subMonths(5)]);
        Client::factory()->for($contractor)->create(['created_at' => now()]);

        $this->assertSame(1, $this->actingAs($owner)->get(route('dashboard', ['period' => 'today']))->viewData('summary')['new_clients']);
        $this->assertSame(2, $this->actingAs($owner)->get(route('dashboard', ['period' => '6m']))->viewData('summary')['new_clients']);
        $this->assertSame(2, $this->actingAs($owner)->get(route('dashboard', ['period' => 'custom', 'from' => now()->subMonths(6)->toDateString(), 'to' => today()->toDateString()]))->viewData('summary')['new_clients']);
    }

    public function test_trend_endpoint_returns_filled_buckets(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        Project::factory()->for($contractor)->create(['created_at' => now()->subDays(2)]);
        Project::factory()->for($contractor)->create(['created_at' => now()->subDays(40)]);

        $week = $this->actingAs($owner)->getJson(route('dashboard.trend', ['range' => '7d']))->assertOk();
        $this->assertCount(7, $week->json('labels'));
        $this->assertSame(1, array_sum($week->json('values')));

        $this->assertSame(2, array_sum($this->actingAs($owner)->getJson(route('dashboard.trend', ['range' => '6m']))->json('values')));
    }

    public function test_upcoming_deadlines_include_projects_and_works_due_soon(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $project = Project::factory()->for($contractor)->dueInDays(2)->create(['name' => 'Project Alpha']);
        Work::factory()->forProject($project)->dueInDays(1)->create(['title' => 'Site Inspection']);
        Work::factory()->forProject($project)->dueInDays(60)->create(['title' => 'Far Away Task']);

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertSeeInOrder(['Site Inspection', 'Due tomorrow', 'Project Alpha', 'Due in 2 days'])
            ->assertDontSee('Far Away Task');
    }

    public function test_new_contractor_sees_empty_states(): void
    {
        [, $owner] = $this->contractorWithOwner();

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertSee("You haven't created any projects yet.")
            ->assertSee('Nothing due in the next');
    }
}
