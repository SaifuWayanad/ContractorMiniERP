<?php

namespace App\Console\Commands;

use App\Enums\ContractorStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserStatus;
use App\Models\Contractor;
use App\Models\Project;
use App\Models\ProjectMeeting;
use App\Models\User;
use App\Services\PushService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendMeetingReminders extends Command
{
    protected $signature = 'notifications:send-meeting-reminders {--test : Send a test notification to all users with a push token}';

    protected $description = 'Send a daily 6 AM digest of today\'s meetings to each user';

    public function handle(): int
    {
        if ($this->option('test')) {
            return $this->sendTestNotification();
        }

        $now = now(); // Asia/Kolkata

        if ($now->hour === 6) {
            $this->sendDailyDigests($now);
        }

        return self::SUCCESS;
    }

    private function sendDailyDigests(Carbon $now): void
    {
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $todayString = $now->toDateString();

        $contractors = Contractor::where('status', ContractorStatus::Active)->get();

        foreach ($contractors as $contractor) {
            $meetings = $this->collectTodayMeetings($contractor->id, $todayStart, $todayEnd);

            // No meetings today — skip all users for this contractor.
            if ($meetings->isEmpty()) {
                continue;
            }

            $users = User::where('contractor_id', $contractor->id)
                ->where('status', UserStatus::Active)
                ->whereNotNull('push_token')
                ->get();

            foreach ($users as $user) {
                $this->sendDigestToUser($user, $meetings, $todayString, $todayStart);
            }
        }
    }

    /**
     * Collect all meetings for a contractor on a given day from both data sources:
     * - project_meetings rows (formally logged/upcoming meetings)
     * - projects.next_meeting_at (the quick "next meeting" field)
     * Both are merged and deduplicated by (project_id, meeting_at).
     *
     * @return Collection<int, array{project_name: string, purpose: ?string, meeting_at: Carbon}>
     */
    private function collectTodayMeetings(int $contractorId, Carbon $start, Carbon $end): Collection
    {
        // Source 1: formally logged ProjectMeeting records
        $loggedMeetings = ProjectMeeting::with('project:id,name')
            ->where('contractor_id', $contractorId)
            ->whereBetween('meeting_at', [$start, $end])
            ->get()
            ->map(fn (ProjectMeeting $m): array => [
                'dedup_key' => $m->project_id.'_'.$m->meeting_at->toDateTimeString(),
                'project_name' => $m->project?->name ?? '',
                'purpose' => $m->purpose,
                'meeting_at' => $m->meeting_at,
            ]);

        // Source 2: Project.next_meeting_at (non-cancelled projects only)
        $upcomingMeetings = Project::with('client:id,name')
            ->where('contractor_id', $contractorId)
            ->whereBetween('next_meeting_at', [$start, $end])
            ->whereNotIn('status', [ProjectStatus::Cancelled->value])
            ->get()
            ->map(fn (Project $p): array => [
                'dedup_key' => $p->id.'_'.$p->next_meeting_at->toDateTimeString(),
                'project_name' => $p->name,
                'purpose' => $p->next_meeting_notes,
                'meeting_at' => $p->next_meeting_at,
            ]);

        return $loggedMeetings
            ->merge($upcomingMeetings)
            ->unique('dedup_key')
            ->sortBy('meeting_at')
            ->values();
    }

    /**
     * @param Collection<int, array{project_name: string, purpose: ?string, meeting_at: Carbon}> $meetings
     */
    private function sendDigestToUser(User $user, Collection $meetings, string $todayString, Carbon $todayStart): void
    {
        $alreadySent = DB::table('push_notification_logs')
            ->where('user_id', $user->id)
            ->whereNull('project_id')
            ->where('type', 'daily_digest')
            ->whereDate('meeting_at', $todayString)
            ->exists();

        if ($alreadySent) {
            return;
        }

        $count = $meetings->count();
        $title = $count === 1 ? "Today's Meeting" : "Today's Meetings ({$count})";
        $body = $this->buildDigestBody($meetings);

        $result = app(PushService::class)->send(
            $user->push_token,
            $title,
            $body,
            ['screen' => 'Meetings'],
        );

        if ($result['ok']) {
            DB::table('push_notification_logs')->insert([
                'user_id' => $user->id,
                'project_id' => null,
                'type' => 'daily_digest',
                'meeting_at' => $todayStart,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            Log::warning('Daily digest push failed', [
                'user_id' => $user->id,
                'error' => $result['error'],
            ]);
        }
    }

    /**
     * Build the notification body from a sorted list of today's meetings.
     * Shows up to 5 lines; a summary line covers any overflow.
     *
     * @param Collection<int, array{project_name: string, purpose: ?string, meeting_at: Carbon}> $meetings
     */
    private function buildDigestBody(Collection $meetings): string
    {
        $tz = config('app.timezone');
        $maxLines = 5;
        $shown = $meetings->take($maxLines);
        $overflow = $meetings->count() - $shown->count();

        $lines = $shown->map(function (array $meeting) use ($tz): string {
            $time = $meeting['meeting_at']->setTimezone($tz)->format('h:i A');
            $label = $meeting['purpose'] ?? $meeting['project_name'];

            return "{$label} at {$time}";
        })->all();

        if ($overflow > 0) {
            $lines[] = "+ {$overflow} more";
        }

        return implode("\n", $lines);
    }

    private function sendTestNotification(): int
    {
        $users = User::whereNotNull('push_token')->get();

        if ($users->isEmpty()) {
            $this->warn('No users have a push token registered yet. Open the app on a real device to register.');

            return self::FAILURE;
        }

        $this->info("Sending test notification to {$users->count()} device(s)…");

        foreach ($users as $user) {
            $result = app(PushService::class)->send(
                $user->push_token,
                '🔔 Test Notification',
                'BuildMate push is working! Time: '.now()->setTimezone(config('app.timezone'))->format('h:i A'),
                ['test' => true],
            );

            $result['ok']
                ? $this->line("  ✓ Sent to {$user->name}")
                : $this->error("Failed for {$user->name}: {$result['error']}");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
