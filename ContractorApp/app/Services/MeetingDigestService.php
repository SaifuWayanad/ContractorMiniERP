<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\Contractor;
use App\Models\Project;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds and sends the meeting notifications for contractor users:
 * the 6 AM daily summary and the immediate push when a meeting is set for today.
 */
class MeetingDigestService
{
    /** Notification type stored in push_notification_logs for the daily summary. */
    public const SUMMARY_TYPE = 'morning_summary';

    /** Titles listed in the body before the rest are collapsed into "and N more". */
    private const MAX_LISTED = 3;

    public function __construct(private PushService $push) {}

    /**
     * Send today's summary to every eligible contractor user, once per local date.
     *
     * @return array{sent: int, skipped: int, failed: int}
     */
    public function sendMorningSummaries(CarbonInterface $now): array
    {
        $totals = ['sent' => 0, 'skipped' => 0, 'failed' => 0];

        $contractorIds = User::query()
            ->whereNotNull('push_token')
            ->whereNotNull('contractor_id')
            ->distinct()
            ->pluck('contractor_id');

        foreach (Contractor::whereIn('id', $contractorIds)->get() as $contractor) {
            $meetings = $this->todaysMeetings($contractor, $now);
            $message = $this->summaryMessage($meetings);

            if ($message === null && ! config('meetings.morning_summary.notify_when_empty')) {
                continue;
            }

            foreach ($this->recipients($contractor) as $user) {
                $outcome = $this->deliverOnce($user, $now, $message);
                $totals[$outcome]++;
            }
        }

        return $totals;
    }

    /**
     * Immediate push for a meeting set for today, after the morning summary has gone out.
     * Before the summary hour the summary itself will include the meeting, so nothing is sent.
     */
    public function notifyMeetingScheduledToday(Project $project, CarbonInterface $now): void
    {
        if ($now->hour < config('meetings.morning_summary.hour')) {
            return;
        }

        if (! in_array($project->status, ProjectStatus::open(), true) || ! $project->next_meeting_at?->isSameDay($now)) {
            return;
        }

        $time = $project->next_meeting_at->setTimezone(config('app.timezone'))->format('h:i A');

        foreach ($this->recipients($project->contractor) as $user) {
            $this->push->send($user->push_token, 'Meeting Scheduled for Today', "{$project->name} at {$time}", [
                'projectId' => $project->id,
                'screen' => 'project',
            ]);
        }
    }

    /**
     * Open projects of the contractor whose next meeting falls on the current local day.
     *
     * @return Collection<int, Project>
     */
    public function todaysMeetings(Contractor $contractor, CarbonInterface $now): Collection
    {
        return Project::query()
            ->where('contractor_id', $contractor->id)
            ->whereIn('status', array_map(fn (ProjectStatus $s) => $s->value, ProjectStatus::open()))
            ->whereBetween('next_meeting_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])
            ->orderBy('next_meeting_at')
            ->get();
    }

    /**
     * @param  Collection<int, Project>  $meetings
     */
    public function summaryMessage(Collection $meetings): ?string
    {
        if ($meetings->isEmpty()) {
            return null;
        }

        $lines = $meetings->take(self::MAX_LISTED)->map(
            fn (Project $p) => $p->name.' at '.$p->next_meeting_at->setTimezone(config('app.timezone'))->format('h:i A')
        );

        $remaining = $meetings->count() - self::MAX_LISTED;
        if ($remaining > 0) {
            $lines->push("and {$remaining} more. Open the app to see the full schedule.");
        }

        return $lines->implode("\n");
    }

    /**
     * Active contractor users with a device registered who may view projects.
     *
     * @return Collection<int, User>
     */
    public function recipients(Contractor $contractor): Collection
    {
        if (! $contractor->isActive()) {
            return new Collection;
        }

        return User::query()
            ->with('role.permissions', 'contractor')
            ->where('contractor_id', $contractor->id)
            ->whereNotNull('push_token')
            ->get()
            ->filter(fn (User $user) => $user->canSignIn() && $user->hasPermission('projects.view'))
            ->values();
    }

    /**
     * Send the summary to one user, recording it so it is never sent twice for the same day.
     * The log row is claimed before sending and released on failure, so a later run retries.
     *
     * @return 'sent'|'skipped'|'failed'
     */
    private function deliverOnce(User $user, CarbonInterface $now, ?string $message): string
    {
        $date = $now->toDateString();

        $claimed = DB::table('push_notification_logs')->insertOrIgnore([
            'user_id' => $user->id,
            'project_id' => null,
            'type' => self::SUMMARY_TYPE,
            'meeting_at' => $now->copy()->startOfDay(),
            'summary_date' => $date,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($claimed === 0) {
            return 'skipped';
        }

        $result = $this->push->send(
            $user->push_token,
            'Today\'s Scheduled Meetings',
            $message ?? 'No meetings scheduled for today.',
            ['screen' => 'meetings', 'date' => $date],
        );

        if ($result['ok']) {
            return 'sent';
        }

        DB::table('push_notification_logs')
            ->where('user_id', $user->id)
            ->where('type', self::SUMMARY_TYPE)
            ->where('summary_date', $date)
            ->delete();

        return 'failed';
    }
}
