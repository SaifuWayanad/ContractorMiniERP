<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_creates_a_client_and_the_creation_is_logged(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();

        $response = $this->actingAs($owner)->post(route('clients.store'), [
            'name' => 'Harbour Homes', 'contact_person' => 'Abdul', 'phone' => '9895123456',
            'country' => 'India', 'status' => 'active',
        ]);

        $client = Client::firstWhere('name', 'Harbour Homes');
        $response->assertRedirect(route('clients.index'))->assertSessionHas('toast');
        $this->assertSame($contractor->id, $client->contractor_id);
        $this->assertDatabaseHas('activity_logs', [
            'contractor_id' => $contractor->id, 'action' => 'created', 'entity_type' => 'client', 'entity_id' => $client->id,
            'description' => "{$owner->name} created client \"Harbour Homes\"",
        ]);
    }

    public function test_store_validates_required_fields_and_phone(): void
    {
        [, $owner] = $this->contractorWithOwner();

        $this->actingAs($owner)->post(route('clients.store'), ['phone' => 'abc', 'status' => 'nope'])
            ->assertSessionHasErrors([
                'name' => 'The client name field is required.',
                'phone' => 'Enter a valid phone number.',
                'status',
            ])
            ->assertSessionDoesntHaveErrors(['country']);
    }

    public function test_index_filters_by_status_and_search_and_keeps_filters_in_pagination(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        Client::factory()->for($contractor)->create(['name' => 'Active Alpha']);
        Client::factory()->for($contractor)->inactive()->create(['name' => 'Dormant Beta']);

        $this->actingAs($owner)->get(route('clients.index', ['status' => 'inactive']))
            ->assertSee('Dormant Beta')->assertDontSee('Active Alpha');
        $this->actingAs($owner)->get(route('clients.index', ['search' => 'alpha']))
            ->assertSee('Active Alpha')->assertDontSee('Dormant Beta');
    }

    public function test_index_paginates_and_shows_record_range(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        Client::factory()->for($contractor)->count(30)->create();

        $this->actingAs($owner)->get(route('clients.index', ['status' => 'active']))
            ->assertSee('Showing 1–25 of 30 records')
            ->assertSee('status=active&amp;page=2', false);
    }

    public function test_empty_list_explains_itself(): void
    {
        [, $owner] = $this->contractorWithOwner();

        $this->actingAs($owner)->get(route('clients.index'))->assertSee("You haven't added any customers yet.");
    }

    public function test_client_with_projects_cannot_be_deleted(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $client = Client::factory()->for($contractor)->create();
        Project::factory()->forClient($client)->create();

        $this->actingAs($owner)->delete(route('clients.destroy', $client))->assertSessionHas('error');
        $this->assertNotSoftDeleted($client);
    }

    public function test_client_without_projects_is_soft_deleted(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $client = Client::factory()->for($contractor)->create();

        $this->actingAs($owner)->delete(route('clients.destroy', $client))->assertRedirect(route('clients.index'));
        $this->assertSoftDeleted($client);
    }

    public function test_client_details_show_only_that_clients_projects(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $client = Client::factory()->for($contractor)->create();
        $otherClient = Client::factory()->for($contractor)->create();
        Project::factory()->forClient($client)->create(['name' => 'Mine']);
        Project::factory()->forClient($otherClient)->create(['name' => 'Not Mine']);

        $this->actingAs($owner)->get(route('clients.show', $client))->assertSee('Mine')->assertDontSee('Not Mine');
    }

    public function test_output_is_escaped(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        Client::factory()->for($contractor)->create(['name' => '<script>alert(1)</script>']);

        $this->actingAs($owner)->get(route('clients.index'))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
