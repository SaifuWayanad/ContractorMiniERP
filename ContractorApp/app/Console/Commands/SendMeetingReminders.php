<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MeetingDigestService;
use App\Services\PushService;
use Illuminate\Console\Command;

class SendMeetingReminders extends Command
{
    protected $signature = 'notifications:send-meeting-reminders {--test : Send a test notification to all users with a push token}';

    protected $description = "Send each contractor user one push listing today's meetings (at the configured morning hour)";

    public function handle(MeetingDigestService $digest): int
    {
        if ($this->option('test')) {
            return $this->sendTestNotification();
        }

        $now = now(); // app timezone (config/app.php)

        // The scheduler runs every 15 minutes. Only the configured hour sends the summary;
        // the per-user log makes repeat runs within that hour harmless.
        if ($now->hour === config('meetings.morning_summary.hour')) {
            $totals = $digest->sendMorningSummaries($now);

            $this->info("Morning summary: {$totals['sent']} sent, {$totals['skipped']} already sent, {$totals['failed']} failed.");

            return $totals['failed'] > 0 ? self::FAILURE : self::SUCCESS;
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
}
