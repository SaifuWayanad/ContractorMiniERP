<?php

namespace Tests\Feature\Security;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Dealer;
use App\Models\Project;
use App\Models\ProjectLocation;
use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The spec's security scenario: two contractors, each with a client, project, location and work.
 * Each contractor sees only their own data, even when changing IDs in URLs or forms.
 */
class ContractorIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{contractor: Contractor, user: User, client: Client, project: Project, location: ProjectLocation, work: Work, dealer: Dealer}
     */
    private function tenant(string $label): array
    {
        [$contractor, $user] = $this->contractorWithOwner(['company_name' => "Contractor {$label}"]);
        $client = Client::factory()->for($contractor)->create(['name' => "Client {$label}1"]);
        $project = Project::factory()->forClient($client)->create(['name' => "Project {$label}1", 'project_number' => "PRJ-{$label}1"]);

        return [
            'contractor' => $contractor,
            'user' => $user,
            'client' => $client,
            'project' => $project,
            'location' => ProjectLocation::factory()->forProject($project)->create(['name' => "Location {$label}1"]),
            'work' => Work::factory()->forProject($project)->create(['title' => "Work {$label}1"]),
            'dealer' => Dealer::factory()->for($contractor)->create(['name' => "Dealer {$label}1"]),
        ];
    }

    public function test_each_contractor_sees_only_their_own_records_in_every_list(): void
    {
        $a = $this->tenant('A');
        $b = $this->tenant('B');

        foreach ([[$a, $b], [$b, $a]] as [$own, $other]) {
            $this->actingAs($own['user']);

            $this->get(route('clients.index'))->assertSee($own['client']->name)->assertDontSee($other['client']->name);
            $this->get(route('projects.index'))->assertSee($own['project']->name)->assertDontSee($other['project']->name);
            $this->get(route('locations.index'))->assertSee($own['location']->name)->assertDontSee($other['location']->name);
            $this->get(route('works.index'))->assertSee($own['work']->title)->assertDontSee($other['work']->title);
            $this->get(route('dealers.index'))->assertSee($own['dealer']->name)->assertDontSee($other['dealer']->name);
            $this->get(route('dashboard'))->assertSee($own['project']->name)->assertDontSee($other['project']->name);
        }
    }

    public function test_search_never_returns_another_contractors_records(): void
    {
        $a = $this->tenant('A');
        $b = $this->tenant('B');

        $this->actingAs($a['user'])
            ->get(route('projects.index', ['search' => 'Project B1']))
            ->assertDontSee(route('projects.show', $b['project']))
            ->assertSee('No projects found');
    }

    /**
     * Every contractor-portal URL that takes a record ID.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function recordRoutes(): array
    {
        return [
            'view client' => ['get', 'clients.show', 'client'],
            'edit client' => ['get', 'clients.edit', 'client'],
            'update client' => ['put', 'clients.update', 'client'],
            'delete client' => ['delete', 'clients.destroy', 'client'],
            'view project' => ['get', 'projects.show', 'project'],
            'edit project' => ['get', 'projects.edit', 'project'],
            'update project' => ['put', 'projects.update', 'project'],
            'delete project' => ['delete', 'projects.destroy', 'project'],
            'change project status' => ['patch', 'projects.status', 'project'],
            'project locations' => ['get', 'projects.locations.index', 'project'],
            'add location to project' => ['get', 'projects.locations.create', 'project'],
            'store location on project' => ['post', 'projects.locations.store', 'project'],
            'view location' => ['get', 'locations.show', 'location'],
            'edit location' => ['get', 'locations.edit', 'location'],
            'update location' => ['put', 'locations.update', 'location'],
            'delete location' => ['delete', 'locations.destroy', 'location'],
            'view work' => ['get', 'works.show', 'work'],
            'edit work' => ['get', 'works.edit', 'work'],
            'update work' => ['put', 'works.update', 'work'],
            'change work status' => ['patch', 'works.status', 'work'],
            'delete work' => ['delete', 'works.destroy', 'work'],
            'edit dealer' => ['get', 'dealers.edit', 'dealer'],
            'update dealer' => ['put', 'dealers.update', 'dealer'],
            'delete dealer' => ['delete', 'dealers.destroy', 'dealer'],
        ];
    }

    /**
     * Changing the ID in the URL to another contractor's record returns 404 (the record is not
     * revealed to exist), and nothing is changed.
     */
    #[DataProvider('recordRoutes')]
    public function test_another_contractors_record_url_returns_404(string $method, string $route, string $record): void
    {
        $a = $this->tenant('A');
        $b = $this->tenant('B');

        $this->actingAs($a['user'])
            ->{$method}(route($route, $b[$record]), ['status' => 'completed', 'name' => 'Hacked', 'title' => 'Hacked'])
            ->assertNotFound();

        $this->assertDatabaseMissing('clients', ['name' => 'Hacked']);
        $this->assertDatabaseMissing('works', ['title' => 'Hacked']);
        $this->assertNotSoftDeleted($b['client']);
        $this->assertNotSoftDeleted($b['project']);
        $this->assertNotSoftDeleted($b['work']);
        $this->assertSame('pending', $b['work']->fresh()->status->value);
    }

    public function test_contractor_id_in_the_request_is_ignored_on_create(): void
    {
        $a = $this->tenant('A');
        $b = $this->tenant('B');

        $this->actingAs($a['user'])->post(route('clients.store'), [
            'name' => 'Sneaky Client',
            'country' => 'India',
            'status' => 'active',
            'contractor_id' => $b['contractor']->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('clients', ['name' => 'Sneaky Client', 'contractor_id' => $a['contractor']->id]);
        $this->assertDatabaseMissing('clients', ['name' => 'Sneaky Client', 'contractor_id' => $b['contractor']->id]);
    }

    public function test_contractor_id_in_the_request_cannot_move_a_record_to_another_contractor(): void
    {
        $a = $this->tenant('A');
        $b = $this->tenant('B');

        $this->actingAs($a['user'])->put(route('clients.update', $a['client']), [
            'name' => 'Renamed',
            'country' => 'India',
            'status' => 'active',
            'contractor_id' => $b['contractor']->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('clients', ['id' => $a['client']->id, 'name' => 'Renamed', 'contractor_id' => $a['contractor']->id]);
    }

    public function test_project_cannot_be_created_for_another_contractors_client_or_dealer(): void
    {
        $a = $this->tenant('A');
        $b = $this->tenant('B');

        $this->actingAs($a['user'])->post(route('projects.store'), [
            'name' => 'Cross Project',
            'client_id' => $b['client']->id,
            'dealer_id' => $b['dealer']->id,
            'status' => 'planning',
        ])->assertSessionHasErrors([
            'client_id' => 'Select a client from the list.',
            'dealer_id' => 'Select a dealer from the list.',
        ]);

        $this->assertDatabaseMissing('projects', ['name' => 'Cross Project']);
    }

    public function test_work_cannot_use_another_contractors_project_or_user(): void
    {
        $a = $this->tenant('A');
        $b = $this->tenant('B');

        $this->actingAs($a['user'])->post(route('works.store'), [
            'title' => 'Cross Work',
            'project_id' => $b['project']->id,
            'assigned_to' => $b['user']->id,
            'priority' => 'medium',
            'status' => 'pending',
        ])->assertSessionHasErrors(['project_id', 'assigned_to']);

        $this->assertDatabaseMissing('works', ['title' => 'Cross Work']);
    }

    public function test_work_client_comes_from_the_project_not_the_request(): void
    {
        $a = $this->tenant('A');
        $b = $this->tenant('B');

        $this->actingAs($a['user'])->post(route('works.store'), [
            'title' => 'Own Work',
            'project_id' => $a['project']->id,
            'client_id' => $b['client']->id,
            'priority' => 'medium',
            'status' => 'pending',
        ])->assertRedirect();

        $this->assertDatabaseHas('works', ['title' => 'Own Work', 'client_id' => $a['client']->id, 'contractor_id' => $a['contractor']->id]);
    }

    public function test_location_cannot_be_added_to_another_contractors_project_via_the_picker(): void
    {
        $a = $this->tenant('A');
        $b = $this->tenant('B');

        $this->actingAs($a['user'])
            ->get(route('locations.create', ['project_id' => $b['project']->id]))
            ->assertNotFound();
    }

    public function test_dashboard_trend_and_totals_only_count_own_records(): void
    {
        $a = $this->tenant('A');
        $b = $this->tenant('B');
        Project::factory()->count(2)->forClient($b['client'])->create();

        $response = $this->actingAs($a['user'])->get(route('dashboard'));

        $this->assertSame(1, $response->viewData('summary')['projects']);
        $this->assertSame(1, $response->viewData('summary')['clients']);
        $this->assertSame(1, array_sum($this->actingAs($a['user'])->getJson(route('dashboard.trend', ['range' => '30d']))->json('values')));
    }

    public function test_admin_sees_both_contractors_data(): void
    {
        $a = $this->tenant('A');
        $b = $this->tenant('B');
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin);
        $this->get(route('admin.clients.index'))->assertSee('Client A1')->assertSee('Client B1');
        $this->get(route('admin.projects.index'))->assertSee('Project A1')->assertSee('Project B1');
        $this->get(route('admin.locations.index'))->assertSee('Location A1')->assertSee('Location B1');
        $this->get(route('admin.works.index'))->assertSee('Work A1')->assertSee('Work B1');
        $this->get(route('admin.projects.show', $b['project']))->assertOk()->assertSee('Project B1');
        $this->get(route('admin.contractors.show', $a['contractor']))->assertOk()->assertSee('Client A1')->assertDontSee('Client B1');
    }

    public function test_admin_filter_by_contractor_limits_results(): void
    {
        $a = $this->tenant('A');
        $b = $this->tenant('B');

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.projects.index', ['contractor_id' => $b['contractor']->id]))
            ->assertSee('Project B1')
            ->assertDontSee('Project A1');
    }

    public function test_contractor_users_cannot_open_the_admin_portal(): void
    {
        $a = $this->tenant('A');

        foreach (['admin.dashboard', 'admin.contractors.index', 'admin.clients.index', 'admin.users.index', 'admin.roles.index', 'admin.activity.index', 'admin.settings.edit'] as $route) {
            $this->actingAs($a['user'])->get(route($route))->assertForbidden();
        }
    }

    public function test_admin_users_cannot_open_the_contractor_portal(): void
    {
        $admin = User::factory()->superAdmin()->create();

        foreach (['dashboard', 'clients.index', 'projects.create', 'works.index', 'profile.show'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertForbidden();
        }
    }

    public function test_users_of_the_same_contractor_share_its_data(): void
    {
        $a = $this->tenant('A');
        $colleague = User::factory()->staffOf($a['contractor'])->create();

        $this->actingAs($colleague)->get(route('projects.show', $a['project']))->assertOk()->assertSee('Project A1');
    }
}
