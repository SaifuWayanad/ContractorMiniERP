<?php

namespace Tests\Feature\Security;

use App\Enums\RoleType;
use App\Models\Client;
use App\Models\Contractor;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_edit_but_not_delete(): void
    {
        $contractor = Contractor::factory()->create();
        $staff = User::factory()->staffOf($contractor)->create();
        $client = Client::factory()->for($contractor)->create();

        $this->actingAs($staff);
        $this->get(route('clients.edit', $client))->assertOk();
        $this->get(route('clients.show', $client))->assertDontSee('Delete this client?');
        $this->delete(route('clients.destroy', $client))->assertForbidden();
        $this->assertNotSoftDeleted($client);
    }

    public function test_role_without_a_permission_is_forbidden_and_the_menu_item_is_hidden(): void
    {
        $contractor = Contractor::factory()->create();
        $viewer = Role::factory()->withPermissions(['dashboard.view', 'projects.view'])->create();
        $user = User::factory()->ownerOf($contractor)->withRole($viewer)->create();

        $this->actingAs($user);
        $this->get(route('clients.index'))->assertForbidden();
        $this->get(route('projects.create'))->assertForbidden();
        $this->get(route('projects.index'))->assertOk()->assertDontSee('Create Project');
        $this->get(route('dashboard'))->assertOk()
            ->assertDontSee(route('clients.index').'"', false)
            ->assertDontSee('Quick actions');
    }

    public function test_dashboard_requires_the_dashboard_permission(): void
    {
        $contractor = Contractor::factory()->create();
        $role = Role::factory()->withPermissions(['projects.view'])->create();

        $this->actingAs(User::factory()->ownerOf($contractor)->withRole($role)->create())
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_admin_has_full_access_to_admin_portal(): void
    {
        $contractor = Contractor::factory()->create();
        $manager = User::factory()->admin()->create();

        $this->actingAs($manager);
        $this->get(route('admin.contractors.index'))->assertOk();
        $this->get(route('admin.contractors.create'))->assertOk();
        $this->get(route('admin.roles.index'))->assertOk();
        $this->get(route('admin.settings.edit'))->assertOk();
    }

    public function test_admins_cannot_change_contractor_business_data(): void
    {
        [$contractor] = $this->contractorWithOwner();
        $project = Project::factory()->for($contractor)->create();

        $this->actingAs(User::factory()->superAdmin()->create());
        $this->assertFalse(auth()->user()->can('update', $project));
        $this->assertTrue(auth()->user()->can('view', $project));
    }

    public function test_contractor_role_cannot_be_given_admin_permissions(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.roles.store'), [
                'name' => 'Sneaky',
                'type' => RoleType::Contractor->value,
                'permissions' => ['clients.view', 'users.create'],
            ])
            ->assertSessionHasErrors('permissions.1');

        $this->assertDatabaseMissing('roles', ['name' => 'Sneaky']);
    }

    public function test_only_super_admin_can_assign_the_super_admin_role(): void
    {
        $role = Role::factory()->admin()->withPermissions(['dashboard.view', 'users.view', 'users.create'])->create();
        $userManager = User::factory()->admin()->withRole($role)->create();

        $this->actingAs($userManager)->post(route('admin.users.store'), [
            'name' => 'Escalated',
            'email' => 'escalated@example.com',
            'role_id' => Role::where('slug', Role::SUPER_ADMIN)->value('id'),
            'status' => 'active',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertSessionHasErrors(['role_id' => 'Only a Super Admin can assign the Super Admin role.']);

        $this->assertDatabaseMissing('users', ['email' => 'escalated@example.com']);
    }

    public function test_super_admin_role_cannot_be_edited(): void
    {
        $superRole = Role::where('slug', Role::SUPER_ADMIN)->first();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.roles.edit', $superRole))
            ->assertForbidden();
    }

    public function test_roles_in_use_cannot_be_deleted(): void
    {
        $role = Role::factory()->create();
        User::factory()->withRole($role)->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->delete(route('admin.roles.destroy', $role))
            ->assertForbidden();

        $this->assertModelExists($role);
    }

    public function test_mass_assignment_cannot_change_role_or_contractor_through_the_profile(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $other = Contractor::factory()->create();

        $this->actingAs($owner)->put(route('profile.update'), [
            'company_name' => 'Renamed Co',
            'contact_person' => 'X',
            'email' => 'x@example.com',
            'phone' => '9847000000',
            'country' => 'India',
            'status' => 'inactive',
            'id' => $other->id,
        ])->assertRedirect(route('profile.show'));

        $contractor->refresh();
        $this->assertSame('Renamed Co', $contractor->company_name);
        $this->assertTrue($contractor->isActive());
        $this->assertNotSame('Renamed Co', $other->fresh()->company_name);
    }
}
