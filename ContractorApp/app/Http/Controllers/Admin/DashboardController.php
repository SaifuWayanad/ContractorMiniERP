<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContractorStatus;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Contractor;
use App\Models\Project;
use App\Models\Work;
use App\Services\DashboardService;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Global management dashboard aggregated across all contractors.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $contractorCounts = Contractor::toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as active', [ContractorStatus::Active->value])
            ->first();

        $projectCounts = Project::toBase()
            ->whereNull('deleted_at')
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $statusDistribution = collect(ProjectStatus::cases())
            ->mapWithKeys(fn (ProjectStatus $status): array => [$status->label() => (int) ($projectCounts[$status->value] ?? 0)])
            ->all();

        return view('admin.dashboard', [
            'user' => $user,
            'cards' => [
                'contractors' => (int) $contractorCounts->total,
                'active_contractors' => (int) $contractorCounts->active,
                'clients' => Client::count(),
                'projects' => array_sum($statusDistribution),
                'active_projects' => (int) ($projectCounts[ProjectStatus::Active->value] ?? 0),
                'completed_projects' => (int) ($projectCounts[ProjectStatus::Completed->value] ?? 0),
                'pending_works' => Work::open()->count(),
            ],
            'statusDistribution' => $statusDistribution,
            'projectsByContractor' => Contractor::query()
                ->withCount('projects')
                ->orderByDesc('projects_count')
                ->orderBy('company_name')
                ->limit(10)
                ->get()
                ->filter(fn (Contractor $contractor): bool => $contractor->projects_count > 0)
                ->mapWithKeys(fn (Contractor $contractor): array => [$contractor->company_name => $contractor->projects_count])
                ->all(),
            'growth' => $this->contractorGrowth(),
            'activity' => $user->can('activity.view')
                ? ActivityLog::with(['user:id,name', 'contractor:id,company_name'])->latest()->orderByDesc('id')->limit(10)->get()
                : collect(),
            'recentContractors' => Contractor::withCount('projects')->latest()->limit(5)->get(),
        ]);
    }

    /**
     * Cumulative number of contractors at the end of each of the last 12 months.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    private function contractorGrowth(): array
    {
        $start = CarbonImmutable::today()->startOfMonth()->subMonthsNoOverflow(11);
        $bucket = DashboardService::dateBucketExpression('created_at', 'month');

        $newPerMonth = Contractor::withTrashed()
            ->toBase()
            ->selectRaw("{$bucket} as bucket, count(*) as aggregate")
            ->where('created_at', '>=', $start)
            ->groupBy('bucket')
            ->pluck('aggregate', 'bucket');

        $running = Contractor::withTrashed()->where('created_at', '<', $start)->count();
        $labels = [];
        $values = [];

        foreach (CarbonPeriod::create($start, '1 month', CarbonImmutable::today()) as $month) {
            $running += (int) ($newPerMonth[$month->format('Y-m')] ?? 0);
            $labels[] = $month->format('M Y');
            $values[] = $running;
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
