<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'New Person', 'username' => 'new.person', 'email' => 'new@example.com', 'status' => 'active',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
            ...$overrides,
        ];
    }

    public function test_user_can_be_created_with_any_role(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('admin.users.store'), $this->payload(['role_id' => Role::where('slug', Role::CONTRACTOR_STAFF)->value('id')]))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'contractor_id' => null]);
    }

    public function test_admins_cannot_lock_themselves_out(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->put(route('admin.users.update', $admin), $this->payload([
            'email' => $admin->email, 'role_id' => Role::where('slug', Role::ADMIN)->value('id'), 'status' => 'inactive', 'password' => '', 'password_confirmation' => '',
        ]))->assertSessionHasErrors(['role_id', 'status']);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertForbidden();
    }

    public function test_deactivated_user_cannot_sign_in(): void
    {
        [, $owner] = $this->contractorWithOwner();

        $this->actingAs(User::factory()->superAdmin()->create())->patch(route('admin.users.status', $owner), ['status' => 'inactive']);

        auth()->logout();
        $this->post(route('login'), ['username' => $owner->username, 'password' => 'password'])->assertSessionHasErrors('username');
    }

    public function test_blank_password_on_update_keeps_the_existing_one(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner();
        $hash = $owner->password;

        $this->actingAs(User::factory()->superAdmin()->create())->put(route('admin.users.update', $owner), $this->payload([
            'name' => 'Renamed', 'email' => $owner->email, 'role_id' => $owner->role_id,
            'password' => '', 'password_confirmation' => '',
        ]))->assertSessionHasNoErrors();

        $this->assertSame($hash, $owner->fresh()->password);
        $this->assertSame('Renamed', $owner->fresh()->name);
    }
}
