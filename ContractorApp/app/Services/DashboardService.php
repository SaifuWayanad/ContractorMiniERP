<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Enums\WorkStatus;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Project;
use App\Models\ProjectLocation;
use App\Models\ProjectPayment;
use App\Models\Purchase;
use App\Models\Visit;
use App\Models\Work;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Figures for one contractor's dashboard. Every query is explicitly limited to the given contractor,
 * in addition to the global contractor scope, so the same service is safe to use from admin screens.
 * All totals are computed with SQL aggregates.
 */
class DashboardService
{
    public const TREND_RANGES = [
        '7d' => 'Last 7 Days',
        '30d' => 'Last 30 Days',
        '6m' => 'Last 6 Months',
        'year' => 'This Year',
    ];

    public const DEADLINE_WINDOW_DAYS = 14;

    /**
     * Lifetime totals plus what changed during the period.
     *
     * @return array{clients: int, projects: int, active_projects: int, completed_projects: int, pending_works: int, locations: int, new_clients: int, new_projects: int, works_completed: int, projects_completed: int}
     */
    public function summary(int $contractorId, ReportingPeriod $period): array
    {
        $projectCounts = Project::forContractor($contractorId)
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as active', [ProjectStatus::Active->value])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as completed', [ProjectStatus::Completed->value])
            ->selectRaw('sum(case when created_at between ? and ? then 1 else 0 end) as created_in_period', $period->range())
            ->first();

        $purchaseValue  = (float) Purchase::forContractor($contractorId)->sum('line_total');
        $totalExpenses  = (float) Expense::forContractor($contractorId)->sum('amount');
        $totalReceipts  = (float) ProjectPayment::forContractor($contractorId)->sum('amount_received');
        $totalBudget    = (float) Project::forContractor($contractorId)->sum('budget');
        $outstanding    = $totalBudget - $totalReceipts;

        return [
            'clients' => Client::forContractor($contractorId)->count(),
            'projects' => (int) $projectCounts->total,
            'active_projects' => (int) $projectCounts->active,
            'completed_projects' => (int) $projectCounts->completed,
            'pending_works' => Work::forContractor($contractorId)->open()->count(),
            'locations' => ProjectLocation::forContractor($contractorId)->count(),
            'new_clients' => Client::forContractor($contractorId)->whereBetween('created_at', $period->range())->count(),
            'new_projects' => (int) $projectCounts->created_in_period,
            'works_completed' => Work::forContractor($contractorId)->whereBetween('completed_at', $period->range())->count(),
            'projects_completed' => Project::forContractor($contractorId)->where('status', ProjectStatus::Completed)->whereBetween('actual_end_date', $period->range())->count(),
            'purchase_value' => $purchaseValue,
            'total_expenses' => $totalExpenses,
            'total_receipts' => $totalReceipts,
            'outstanding'    => $outstanding,
        ];
    }

    /**
     * Number of projects in each status (every status is present, including zeroes).
     *
     * @return array<string, int> status label => count
     */
    public function projectStatusDistribution(int $contractorId): array
    {
        $counts = Project::forContractor($contractorId)
            ->toBase()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect(ProjectStatus::cases())
            ->mapWithKeys(fn (ProjectStatus $status): array => [$status->label() => (int) ($counts[$status->value] ?? 0)])
            ->all();
    }

    /**
     * Projects created per day (short ranges) or per month (long ranges), with empty buckets filled in.
     *
     * @return array{range: string, labels: list<string>, values: list<int>}
     */
    public function projectTrend(int $contractorId, string $range): array
    {
        $range = array_key_exists($range, self::TREND_RANGES) ? $range : '30d';
        $today = CarbonImmutable::today();

        [$start, $unit] = match ($range) {
            '7d' => [$today->subDays(6), 'day'],
            '30d' => [$today->subDays(29), 'day'],
            '6m' => [$today->startOfMonth()->subMonthsNoOverflow(5), 'month'],
            'year' => [$today->startOfYear(), 'month'],
        };

        $bucket = $this->dateBucketExpression('created_at', $unit);

        $counts = Project::forContractor($contractorId)
            ->toBase()
            ->selectRaw("{$bucket} as bucket, count(*) as aggregate")
            ->where('created_at', '>=', $start)
            ->groupBy('bucket')
            ->pluck('aggregate', 'bucket');

        $labels = [];
        $values = [];
        $interval = $unit === 'day' ? '1 day' : '1 month';

        foreach (CarbonPeriod::create($start, $interval, $today) as $date) {
            $key = $unit === 'day' ? $date->format('Y-m-d') : $date->format('Y-m');
            $labels[] = $unit === 'day' ? $date->format('d M') : $date->format('M Y');
            $values[] = (int) ($counts[$key] ?? 0);
        }

        return ['range' => $range, 'labels' => $labels, 'values' => $values];
    }

    /**
     * @return Collection<int, Project>
     */
    public function recentProjects(int $contractorId, int $limit = 5): Collection
    {
        return Project::forContractor($contractorId)
            ->with(['client:id,name', 'primaryLocation'])
            ->latest()
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Client>
     */
    public function recentClients(int $contractorId, int $limit = 5): Collection
    {
        return Client::forContractor($contractorId)
            ->withCount('projects')
            ->latest()
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Open projects and works due within the deadline window (including overdue ones), soonest first.
     *
     * @return list<array{type: string, title: string, subtitle: ?string, date: CarbonImmutable, url: string, days: int}>
     */
    public function upcomingDeadlines(int $contractorId, int $limit = 8): array
    {
        $today = CarbonImmutable::today();
        $until = $today->addDays(self::DEADLINE_WINDOW_DAYS);

        $projects = Project::forContractor($contractorId)
            ->open()
            ->whereNotNull('expected_end_date')
            ->where('expected_end_date', '<=', $until)
            ->with('client:id,name')
            ->orderBy('expected_end_date')
            ->limit($limit)
            ->get()
            ->map(fn (Project $project): array => [
                'type' => 'Project',
                'title' => $project->name,
                'subtitle' => $project->client?->name,
                'date' => $project->expected_end_date->toImmutable(),
                'url' => route('projects.show', $project),
                'days' => (int) $today->diffInDays($project->expected_end_date, false),
            ]);

        $works = Work::forContractor($contractorId)
            ->open()
            ->whereNotNull('due_date')
            ->where('due_date', '<=', $until)
            ->with('project:id,name')
            ->orderBy('due_date')
            ->limit($limit)
            ->get()
            ->map(fn (Work $work): array => [
                'type' => 'Work',
                'title' => $work->title,
                'subtitle' => $work->project?->name,
                'date' => $work->due_date->toImmutable(),
                'url' => route('works.show', $work),
                'days' => (int) $today->diffInDays($work->due_date, false),
            ]);

        return $projects->concat($works)->sortBy('date')->take($limit)->values()->all();
    }

    /**
     * Visits with a next meeting scheduled for today.
     *
     * @return Collection<int, Visit>
     */
    public function todaysMeetings(int $contractorId): Collection
    {
        return Visit::forContractor($contractorId)
            ->with('client:id,name')
            ->whereDate('next_meeting_date', today())
            ->orderBy('next_meeting_date')
            ->get(['id', 'client_id', 'purpose', 'location', 'next_meeting_date']);
    }

    /**
     * Visits with a next meeting scheduled from tomorrow onwards (next 30 days), soonest first.
     *
     * @return Collection<int, Visit>
     */
    public function upcomingMeetings(int $contractorId, int $limit = 6): Collection
    {
        return Visit::forContractor($contractorId)
            ->with('client:id,name')
            ->where('next_meeting_date', '>', today())
            ->where('next_meeting_date', '<=', today()->addDays(30))
            ->orderBy('next_meeting_date')
            ->limit($limit)
            ->get(['id', 'client_id', 'purpose', 'location', 'next_meeting_date']);
    }

    /**
     * @return Collection<int, ActivityLog>
     */
    public function recentActivity(int $contractorId, int $limit = 8): Collection
    {
        return ActivityLog::query()
            ->where('contractor_id', $contractorId)
            ->with('user:id,name')
            ->latest()
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * SQL that formats a timestamp column as Y-m-d or Y-m, for the current database driver.
     */
    public static function dateBucketExpression(string $column, string $unit): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'mysql', 'mariadb' => $unit === 'day' ? "DATE_FORMAT({$column}, '%Y-%m-%d')" : "DATE_FORMAT({$column}, '%Y-%m')",
            'pgsql' => $unit === 'day' ? "to_char({$column}, 'YYYY-MM-DD')" : "to_char({$column}, 'YYYY-MM')",
            default => $unit === 'day' ? "strftime('%Y-%m-%d', {$column})" : "strftime('%Y-%m', {$column})",
        };
    }

    /**
     * Label for a deadline that is $days away (negative means overdue).
     */
    public static function dueLabel(int $days): string
    {
        return match (true) {
            $days < -1 => 'Overdue by '.abs($days).' days',
            $days === -1 => 'Overdue by 1 day',
            $days === 0 => 'Due today',
            $days === 1 => 'Due tomorrow',
            default => "Due in {$days} days",
        };
    }

    /**
     * Status filter values for works that count as "pending".
     *
     * @return list<string>
     */
    public static function openWorkStatuses(): array
    {
        return array_map(fn (WorkStatus $status): string => $status->value, WorkStatus::open());
    }
}
