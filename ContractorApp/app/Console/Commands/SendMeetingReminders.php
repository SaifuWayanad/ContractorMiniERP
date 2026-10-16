<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Services\PushService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendMeetingReminders extends Command
{
    protected $signature = 'notifications:send-meeting-reminders {--test : Send a test notification to all users with a push token}';

    protected $description = 'Send push notification reminders for upcoming meetings (morning of, 24h and 1h before)';

    public function handle(): int
    {
        if ($this->option('test')) {
            return $this->sendTestNotification();
        }

        $now = now(); // IST (Asia/Kolkata)

        // Send one morning notification at 6 AM for every meeting scheduled today.
        // The scheduler runs every 15 minutes; the deduplication log prevents duplicates.
        if ($now->hour === 6) {
            $projects = Project::with('contractor')
                ->whereNotNull('next_meeting_at')
                ->whereDate('next_meeting_at', $now->toDateString())
                ->get();

            foreach ($projects as $project) {
                $this->notifyContractorUsers($project, 'morning_today');
            }
        }

        return self::SUCCESS;
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

    private function notifyContractorUsers(Project $project, string $type): void
    {
        $users = User::where('contractor_id', $project->contractor_id)
            ->whereNotNull('push_token')
            ->get();

        foreach ($users as $user) {
            $alreadySent = DB::table('push_notification_logs')
                ->where('user_id', $user->id)
                ->where('project_id', $project->id)
                ->where('type', $type)
                ->where('meeting_at', $project->next_meeting_at)
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $sent = $this->sendPush($user->push_token, $project, $type);

            if ($sent) {
                DB::table('push_notification_logs')->insert([
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                    'type' => $type,
                    'meeting_at' => $project->next_meeting_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function sendPush(string $token, Project $project, string $type): bool
    {
        $meeting = $project->next_meeting_at->setTimezone(config('app.timezone'));
        $time = $meeting->format('h:i A');
        $date = $meeting->format('d M');

        $body = "Meeting today at {$time} — {$project->name}";
        $title = 'Meeting Today';

        return app(PushService::class)->send($token, $title, $body, ['projectId' => $project->id])['ok'];
    }
}
