<?php

namespace Tests\Feature\Auth;

use App\Enums\ContractorStatus;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_with_remember_me_and_forgot_password(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('name="remember"', false)
            ->assertSee(route('password.request'));
    }

    public function test_contractor_user_lands_on_the_contractor_dashboard(): void
    {
        [, $owner] = $this->contractorWithOwner();

        $this->post(route('login'), ['username' => $owner->username, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($owner);
        $this->assertNotNull($owner->fresh()->last_login_at);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $owner->id, 'contractor_id' => $owner->contractor_id, 'action' => 'login']);
    }

    public function test_admin_user_lands_on_the_admin_dashboard(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->post(route('login'), ['username' => $admin->username, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_wrong_password_shows_invalid_login_message(): void
    {
        [, $owner] = $this->contractorWithOwner();

        $this->from(route('login'))
            ->post(route('login'), ['username' => $owner->username, 'password' => 'wrong-password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['username' => 'These credentials do not match our records.']);

        $this->assertGuest();
    }

    public function test_login_requires_username_and_password(): void
    {
        $this->post(route('login'), [])->assertSessionHasErrors(['username', 'password']);
    }

    public function test_repeated_failed_logins_are_rate_limited(): void
    {
        [, $owner] = $this->contractorWithOwner();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login'), ['username' => $owner->username, 'password' => 'wrong']);
        }

        $this->post(route('login'), ['username' => $owner->username, 'password' => 'password'])
            ->assertSessionHasErrors('username');

        $this->assertStringContainsString('Too many login attempts', session('errors')->first('username'));
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_sign_in(): void
    {
        [$contractor] = $this->contractorWithOwner();
        $inactive = User::factory()->ownerOf($contractor)->inactive()->create();

        $this->post(route('login'), ['username' => $inactive->username, 'password' => 'password'])
            ->assertSessionHasErrors(['username' => 'Your account is inactive. Please contact your administrator.']);

        $this->assertGuest();
    }

    public function test_users_of_an_inactive_contractor_cannot_sign_in(): void
    {
        [$contractor, $owner] = $this->contractorWithOwner(['status' => ContractorStatus::Inactive]);

        $this->post(route('login'), ['username' => $owner->username, 'password' => 'password'])
            ->assertSessionHasErrors(['username' => 'Your account is inactive. Please contact your administrator.']);

        $this->assertGuest();
    }

    public function test_user_deactivated_during_a_session_is_signed_out_on_next_request(): void
    {
        [, $owner] = $this->contractorWithOwner();
        $this->actingAs($owner)->get(route('dashboard'))->assertOk();

        $owner->status = UserStatus::Inactive;
        $owner->save();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_logout_ends_the_session_and_logs_activity(): void
    {
        [, $owner] = $this->contractorWithOwner();

        $this->actingAs($owner)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['user_id' => $owner->id, 'action' => 'logout']);
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_reset_link_is_sent_without_revealing_whether_the_email_exists(): void
    {
        Notification::fake();
        [, $owner] = $this->contractorWithOwner();

        $this->post(route('password.email'), ['email' => $owner->email])->assertSessionHas('status');
        $this->post(route('password.email'), ['email' => 'nobody@example.com'])->assertSessionHas('status');

        Notification::assertSentTo($owner, ResetPassword::class);
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        Notification::fake();
        [, $owner] = $this->contractorWithOwner();
        $this->post(route('password.email'), ['email' => $owner->email]);

        Notification::assertSentTo($owner, ResetPassword::class, function (ResetPassword $notification) use ($owner): bool {
            $this->get(route('password.reset', ['token' => $notification->token, 'email' => $owner->email]))->assertOk();

            $this->post(route('password.store'), [
                'token' => $notification->token,
                'email' => $owner->email,
                'password' => 'NewPassword123',
                'password_confirmation' => 'NewPassword123',
            ])->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(Hash::check('NewPassword123', $owner->fresh()->password));
    }

    public function test_user_can_change_their_password_with_the_current_one(): void
    {
        [, $owner] = $this->contractorWithOwner();

        $this->actingAs($owner)->put(route('account.password.update'), [
            'current_password' => 'wrong',
            'password' => 'Another1234',
            'password_confirmation' => 'Another1234',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($owner)->put(route('account.password.update'), [
            'current_password' => 'password',
            'password' => 'Another1234',
            'password_confirmation' => 'Another1234',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('Another1234', $owner->fresh()->password));
    }
}
