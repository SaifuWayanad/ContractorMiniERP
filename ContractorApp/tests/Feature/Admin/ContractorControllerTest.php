<?php

namespace Tests\Feature\Admin;

use App\Models\Contractor;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ContractorControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_a_contractor_with_its_first_owner_login(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())->post(route('admin.contractors.store'), [
            'company_name' => 'Skyline Builders', 'contact_person' => 'Asha', 'email' => 'office@skyline.test',
            'phone' => '9876543210', 'country' => 'India', 'status' => 'active',
            'create_user' => '1', 'user_name' => 'Asha Nair',
            'user_password' => 'Welcome123', 'user_password_confirmation' => 'Welcome123',
        ])->assertRedirect();

        $contractor = Contractor::firstWhere('company_name', 'Skyline Builders');
        $user = User::firstWhere('username', 'asha.nair');
        $this->assertSame($contractor->id, $user->contractor_id);
        $this->assertSame(Role::CONTRACTOR_OWNER, $user->role->slug);
        $this->assertNotSame('Welcome123', $user->password);
        $this->assertTrue(Hash::check('Welcome123', $user->password));

        auth()->logout();
        $this->post(route('login'), ['username' => 'asha.nair', 'password' => 'Welcome123'])->assertRedirect(route('dashboard'));
    }

    public function test_initial_login_fields_are_required_only_when_requested(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $base = ['company_name' => 'A', 'contact_person' => 'B', 'email' => 'a@b.test', 'phone' => '9876543210', 'country' => 'India', 'status' => 'active'];

        $this->actingAs($admin)->post(route('admin.contractors.store'), [...$base, 'create_user' => '1'])
            ->assertSessionHasErrors(['user_name', 'user_password']);
        $this->actingAs($admin)->post(route('admin.contractors.store'), [...$base, 'create_user' => '0'])
            ->assertSessionHasNoErrors();
    }

    public function test_deactivating_keeps_data_and_blocks_sign_in_until_reactivated(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $project = Project::factory()->for($contractor)->create();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->patch(route('admin.contractors.status', $contractor), ['status' => 'inactive'])->assertSessionHas('toast');
        $this->assertFalse($contractor->fresh()->isActive());
        $this->assertModelExists($project);

        auth()->logout();
        $this->post(route('login'), ['username' => $owner->username, 'password' => 'password'])->assertSessionHasErrors('username');
        $this->assertGuest();

        $this->actingAs($admin)->patch(route('admin.contractors.status', $contractor), ['status' => 'active']);
        auth()->logout();
        $this->post(route('login'), ['username' => $owner->username, 'password' => 'password'])->assertRedirect(route('dashboard'));
    }

    public function test_index_shows_counts_and_filters_by_status(): void
    {
        [$active] = $this->contractorWithOwner(['company_name' => 'Busy Co']);
        Project::factory()->for($active)->count(2)->create(['status' => 'active']);
        Contractor::factory()->inactive()->create(['company_name' => 'Sleepy Co']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.contractors.index', ['status' => 'active']))
            ->assertSee('Busy Co')
            ->assertDontSee('Sleepy Co');
    }

    public function test_archiving_soft_deletes_and_blocks_its_users(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();

        $this->actingAs(User::factory()->superAdmin()->create())->delete(route('admin.contractors.destroy', $contractor));

        $this->assertSoftDeleted($contractor);
        auth()->logout();
        $this->post(route('login'), ['username' => $owner->username, 'password' => 'password'])->assertSessionHasErrors('username');
    }
}
