<?php

namespace Tests\Feature\Notifications;

use App\Enums\ProjectStatus;
use App\Enums\UserStatus;
use App\Models\Contractor;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\MeetingDigestService;
use App\Services\PushService;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

class MorningMeetingSummaryTest extends TestCase
{
    use RefreshDatabase;

    private Contractor $contractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->contractor = Contractor::factory()->create();
        config(['app.timezone' => 'Asia/Kolkata']);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    /** Freeze the clock at a local (Asia/Kolkata) time on 19 Oct 2026. */
    private function at(string $time): void
    {
        $this->travelTo($this->local($time));
    }

    /** A local (Asia/Kolkata) moment on 19 Oct 2026, shifted by whole days. */
    private function local(string $time, int $days = 0): Carbon
    {
        return Carbon::parse('2026-10-19 '.$time, 'Asia/Kolkata')->addDays($days);
    }

    private function contractorUser(array $overrides = [], array $permissions = ['projects.view']): User
    {
        $role = Role::factory()->withPermissions($permissions)->create();

        return User::factory()->create(array_merge([
            'contractor_id' => $this->contractor->id,
            'role_id' => $role->id,
            'push_token' => 'fcm-'.fake()->unique()->uuid(),
        ], $overrides));
    }

    private function meeting(string $when, array $overrides = []): Project
    {
        return Project::factory()->create(array_merge([
            'contractor_id' => $this->contractor->id,
            'status' => ProjectStatus::Active,
            'next_meeting_at' => $this->local($when),
            'next_meeting_notes' => null,
        ], $overrides));
    }

    /** @return MockInterface&PushService */
    private function fakePush(bool $ok = true)
    {
        return $this->mock(PushService::class, function (MockInterface $mock) use ($ok): void {
            $mock->shouldReceive('send')->andReturn(['ok' => $ok, 'error' => $ok ? null : 'boom']);
        });
    }

    public function test_one_meeting_today_is_sent_in_a_single_summary(): void
    {
        $this->at('06:00:00');
        $user = $this->contractorUser();
        $this->meeting('09:30:00', ['name' => 'Project Review']);

        $push = $this->mock(PushService::class);
        $push->shouldReceive('send')->once()->withArgs(fn ($token, $title, $body) => $token === $user->push_token
            && $title === "Today's Scheduled Meetings"
            && $body === 'Project Review at 09:30 AM')
            ->andReturn(['ok' => true, 'error' => null]);

        $this->artisan('notifications:send-meeting-reminders')->assertSuccessful();
    }

    public function test_multiple_meetings_are_listed_and_the_rest_summarised(): void
    {
        $this->at('06:00:00');
        $this->contractorUser();
        $this->meeting('12:00:00', ['name' => 'Site visit']);
        $this->meeting('09:30:00', ['name' => 'Project Review']);
        $this->meeting('08:00:00', ['name' => 'Foundation check']);
        $this->meeting('17:00:00', ['name' => 'Handover']);

        $push = $this->mock(PushService::class);
        $push->shouldReceive('send')->once()->withArgs(fn ($token, $title, $body) => $body === implode("\n", [
            'Foundation check at 08:00 AM',
            'Project Review at 09:30 AM',
            'Site visit at 12:00 PM',
            'and 1 more. Open the app to see the full schedule.',
        ]))->andReturn(['ok' => true, 'error' => null]);

        $this->artisan('notifications:send-meeting-reminders');
    }

    public function test_no_meetings_sends_nothing_by_default(): void
    {
        $this->at('06:00:00');
        $this->contractorUser();
        $this->meeting('09:30:00', ['next_meeting_at' => $this->local('09:30:00', 1)]);

        $this->fakePush()->shouldNotReceive('send');

        $this->artisan('notifications:send-meeting-reminders')->assertSuccessful();
    }

    public function test_no_meetings_sends_a_message_when_configured(): void
    {
        config(['meetings.morning_summary.notify_when_empty' => true]);
        $this->at('06:00:00');
        $this->contractorUser();

        $push = $this->mock(PushService::class);
        $push->shouldReceive('send')->once()->withArgs(fn ($token, $title, $body) => $body === 'No meetings scheduled for today.')
            ->andReturn(['ok' => true, 'error' => null]);

        $this->artisan('notifications:send-meeting-reminders');
    }

    public function test_cancelled_and_completed_projects_are_excluded(): void
    {
        $this->at('06:00:00');
        $this->contractorUser();
        $this->meeting('09:30:00', ['status' => ProjectStatus::Cancelled]);
        $this->meeting('10:30:00', ['status' => ProjectStatus::Completed]);

        $this->fakePush()->shouldNotReceive('send');

        $this->artisan('notifications:send-meeting-reminders');
    }

    public function test_meetings_on_other_days_are_excluded(): void
    {
        $this->at('06:00:00');
        $this->contractorUser();
        $this->meeting('23:59:00', ['next_meeting_at' => $this->local('23:59:00', -1)]);
        $this->meeting('00:00:00', ['next_meeting_at' => $this->local('00:00:00', 1)]);

        $this->fakePush()->shouldNotReceive('send');

        $this->artisan('notifications:send-meeting-reminders');
    }

    public function test_rescheduled_meeting_uses_its_new_time(): void
    {
        $this->at('06:00:00');
        $this->contractorUser();
        $project = $this->meeting('09:30:00', ['name' => 'Pour']);
        $project->update(['next_meeting_at' => $this->local('15:15:00')]);

        $push = $this->mock(PushService::class);
        $push->shouldReceive('send')->once()->withArgs(fn ($token, $title, $body) => $body === 'Pour at 03:15 PM')
            ->andReturn(['ok' => true, 'error' => null]);

        $this->artisan('notifications:send-meeting-reminders');
    }

    public function test_only_eligible_recipients_receive_the_summary(): void
    {
        $this->at('06:00:00');
        $this->meeting('09:30:00', ['name' => 'Review']);

        $allowed = $this->contractorUser();
        $this->contractorUser(['push_token' => null]);
        $this->contractorUser(['status' => UserStatus::Inactive]);
        $this->contractorUser([], []);
        $this->contractorUser(['contractor_id' => Contractor::factory()->create()->id]);

        $push = $this->mock(PushService::class);
        $push->shouldReceive('send')->once()->withArgs(fn ($token) => $token === $allowed->push_token)
            ->andReturn(['ok' => true, 'error' => null]);

        $this->artisan('notifications:send-meeting-reminders');
    }

    public function test_suspended_contractor_receives_nothing(): void
    {
        $this->at('06:00:00');
        $this->meeting('09:30:00');
        $this->contractorUser();
        $this->contractor->update(['status' => 'inactive']);

        $this->fakePush()->shouldNotReceive('send');

        $this->artisan('notifications:send-meeting-reminders');
    }

    public function test_a_second_run_the_same_day_does_not_send_again(): void
    {
        $this->at('06:00:00');
        $this->contractorUser();
        $this->meeting('09:30:00');

        $push = $this->mock(PushService::class);
        $push->shouldReceive('send')->once()->andReturn(['ok' => true, 'error' => null]);

        $this->artisan('notifications:send-meeting-reminders');
        $this->travelTo(now()->addMinutes(15));
        $this->artisan('notifications:send-meeting-reminders');
    }

    public function test_a_failed_push_is_retried_on_the_next_run(): void
    {
        $this->at('06:00:00');
        $this->contractorUser();
        $this->meeting('09:30:00');

        $push = $this->mock(PushService::class);
        $push->shouldReceive('send')->twice()
            ->andReturn(['ok' => false, 'error' => 'boom'], ['ok' => true, 'error' => null]);

        $this->artisan('notifications:send-meeting-reminders')->assertFailed();
        $this->travelTo(now()->addMinutes(15));
        $this->artisan('notifications:send-meeting-reminders')->assertSuccessful();

        $this->assertSame(1, DB::table('push_notification_logs')->where('type', 'morning_summary')->count());
    }

    public function test_nothing_is_sent_outside_the_morning_hour(): void
    {
        $this->at('09:00:00');
        $this->contractorUser();
        $this->meeting('10:00:00');

        $this->fakePush()->shouldNotReceive('send');

        $this->artisan('notifications:send-meeting-reminders');
    }

    public function test_meeting_created_before_the_summary_hour_is_not_pushed_immediately(): void
    {
        $this->at('05:50:00');
        $this->contractorUser();

        $this->fakePush()->shouldNotReceive('send');

        $project = $this->meeting('09:30:00');
        app(MeetingDigestService::class)->notifyMeetingScheduledToday($project, now());
    }

    public function test_meeting_created_after_the_summary_is_pushed_immediately(): void
    {
        $this->at('11:00:00');
        $this->contractorUser();
        $project = $this->meeting('14:00:00', ['name' => 'Inspection']);

        $push = $this->mock(PushService::class);
        $push->shouldReceive('send')->once()->withArgs(fn ($token, $title, $body) => $title === 'Meeting Scheduled for Today'
            && $body === 'Inspection at 02:00 PM')
            ->andReturn(['ok' => true, 'error' => null]);

        app(MeetingDigestService::class)->notifyMeetingScheduledToday($project, now());
    }

    public function test_meeting_set_for_another_day_is_not_pushed_immediately(): void
    {
        $this->at('11:00:00');
        $this->contractorUser();
        $project = $this->meeting('14:00:00', ['next_meeting_at' => $this->local('14:00:00', 2)]);

        $this->fakePush()->shouldNotReceive('send');

        app(MeetingDigestService::class)->notifyMeetingScheduledToday($project, now());
    }
}
