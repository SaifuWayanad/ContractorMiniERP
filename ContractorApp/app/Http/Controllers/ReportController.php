<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Project;
use App\Models\ProjectPayment;
use App\Models\Purchase;
use App\Models\Work;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function outstanding(Request $request): View
    {
        $this->authorize('viewAny', \App\Models\Project::class);

        $statusFilter   = $request->string('status')->value() ?: null;
        $search         = $request->string('search')->trim()->value();
        $clientIdFilter = $request->integer('client_id') ?: null;
        $locationFilter = $request->string('location')->trim()->value() ?: null;

        $baseQuery = Project::query()
            ->when($statusFilter, fn ($q, $s) => $q->where('status', $s))
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($clientIdFilter, fn ($q, $id) => $q->where('client_id', $id))
            ->when($locationFilter, fn ($q, $loc) => $q->where('location', $loc));

        // All matching IDs for KPI totals
        $allIds = (clone $baseQuery)->pluck('id');

        $allPurchases   = Purchase::whereIn('project_id', $allIds)->sum('line_total');
        $allExpenses    = Expense::whereIn('project_id', $allIds)->sum('amount')
                        + Work::whereIn('project_id', $allIds)->whereNotNull('amount')->sum('amount');
        $allPayments    = ProjectPayment::whereIn('project_id', $allIds)->sum('amount_received');
        $allBudget      = Project::whereIn('id', $allIds)->sum('budget');

        $totals = [
            'budget'      => (float) $allBudget,
            'purchases'   => (float) $allPurchases,
            'expenses'    => (float) $allExpenses,
            'total_cost'  => (float) $allPurchases + (float) $allExpenses,
            'payments'    => (float) $allPayments,
            'outstanding' => (float) $allBudget - (float) $allPayments,
            'balance'     => (float) $allBudget - ((float) $allPurchases + (float) $allExpenses),
        ];

        // Paginated projects for table
        $paginated = (clone $baseQuery)
            ->with(['client:id,name'])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $pageIds = $paginated->pluck('id');

        $purchaseTotals = Purchase::whereIn('project_id', $pageIds)
            ->select('project_id', DB::raw('SUM(line_total) as total'))
            ->groupBy('project_id')->pluck('total', 'project_id');

        $expenseOnlyTotals = Expense::whereIn('project_id', $pageIds)
            ->select('project_id', DB::raw('SUM(amount) as total'))
            ->groupBy('project_id')->pluck('total', 'project_id');

        $workExpenseTotals = Work::whereIn('project_id', $pageIds)
            ->whereNotNull('amount')
            ->select('project_id', DB::raw('SUM(amount) as total'))
            ->groupBy('project_id')->pluck('total', 'project_id');

        $expenseTotals = $pageIds->mapWithKeys(fn ($id) => [
            $id => (float) ($expenseOnlyTotals[$id] ?? 0) + (float) ($workExpenseTotals[$id] ?? 0),
        ]);

        $paymentTotals = ProjectPayment::whereIn('project_id', $pageIds)
            ->select('project_id', DB::raw('SUM(amount_received) as total'))
            ->groupBy('project_id')->pluck('total', 'project_id');

        $rows = $paginated->through(function (Project $project) use ($purchaseTotals, $expenseTotals, $paymentTotals) {
            $purchases   = (float) ($purchaseTotals[$project->id] ?? 0);
            $expenses    = (float) ($expenseTotals[$project->id] ?? 0);
            $payments    = (float) ($paymentTotals[$project->id] ?? 0);
            $budget      = (float) ($project->budget ?? 0);
            $totalCost   = $purchases + $expenses;
            $outstanding = $budget - $payments;

            return [
                'project'     => $project,
                'budget'      => $budget,
                'purchases'   => $purchases,
                'expenses'    => $expenses,
                'total_cost'  => $totalCost,
                'payments'    => $payments,
                'outstanding' => $outstanding,
                'balance'     => $budget - $totalCost,
            ];
        });

        return view('reports.outstanding', [
            'rows'           => $rows,
            'totals'         => $totals,
            'statusFilter'   => $statusFilter,
            'search'         => $search,
            'clientIdFilter' => $clientIdFilter,
            'locationFilter' => $locationFilter,
            'clients'        => Client::orderBy('name')->pluck('name', 'id'),
            'locations'      => Project::whereNotNull('location')->where('location', '!=', '')->distinct()->orderBy('location')->pluck('location'),
        ]);
    }

    public function collections(Request $request): View
    {
        $this->authorize('viewAny', ProjectPayment::class);

        $period       = $request->string('period')->trim()->value() ?: 'all_time';
        $projectId    = $request->integer('project_id') ?: null;
        $clientId     = $request->integer('client_id') ?: null;
        $methodFilter = $request->string('payment_method')->trim()->value() ?: null;

        [$dateFrom, $dateTo] = match ($period) {
            'today'      => [today()->toDateString(), today()->toDateString()],
            'this_week'  => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
            'this_month' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'this_year'  => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
            'all_time'   => [null, null],
            'custom'     => [
                $request->string('date_from')->trim()->value() ?: null,
                $request->string('date_to')->trim()->value() ?: null,
            ],
            default      => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
        };

        $payments = ProjectPayment::query()
            ->with(['project:id,name,project_number,client_id', 'project.client:id,name'])
            ->when($dateFrom, fn ($q) => $q->whereDate('payment_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('payment_date', '<=', $dateTo))
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($clientId, fn ($q) => $q->whereHas('project', fn ($p) => $p->where('client_id', $clientId)))
            ->when($methodFilter, fn ($q) => $q->where('payment_method', $methodFilter))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        // Distinct methods across filtered results
        $methods = $payments->pluck('payment_method')
            ->map(fn ($m) => $m ?? 'Other')
            ->unique()->sort()->values();

        // Grand totals per method
        $methodGrandTotals = $methods->mapWithKeys(fn ($m) => [
            $m => (float) $payments->filter(fn ($p) => ($p->payment_method ?? 'Other') === $m)->sum('amount_received'),
        ]);

        // Build Customer → Project → Transactions hierarchy
        $byClient = $payments->groupBy(fn ($p) => $p->project?->client_id ?? 0);

        $customerRows = $byClient->map(function ($clientPayments, $clientId) use ($methods) {
            $client = $clientPayments->first()->project?->client;

            $customerMethods = $methods->mapWithKeys(fn ($m) => [
                $m => (float) $clientPayments->filter(fn ($p) => ($p->payment_method ?? 'Other') === $m)->sum('amount_received'),
            ]);

            $byProject = $clientPayments->groupBy('project_id');
            $projectRows = $byProject->map(function ($projectPayments, $pid) use ($methods) {
                $project = $projectPayments->first()->project;

                $projectMethods = $methods->mapWithKeys(fn ($m) => [
                    $m => (float) $projectPayments->filter(fn ($p) => ($p->payment_method ?? 'Other') === $m)->sum('amount_received'),
                ]);

                return [
                    'project_id'     => $pid,
                    'project_name'   => $project?->name ?? 'Unknown',
                    'project_number' => $project?->project_number,
                    'methods'        => $projectMethods,
                    'total'          => (float) $projectPayments->sum('amount_received'),
                    'transactions'   => $projectPayments->values(),
                ];
            })->values()->sortBy('project_name')->values();

            return [
                'client_id'   => $clientId,
                'client_name' => $client?->name ?? 'Unknown',
                'methods'     => $customerMethods,
                'total'       => (float) $clientPayments->sum('amount_received'),
                'projects'    => $projectRows,
            ];
        })->values()->sortBy('client_name')->values();

        return view('reports.collections', [
            'payments'          => $payments,
            'customerRows'      => $customerRows,
            'methods'           => $methods,
            'methodGrandTotals' => $methodGrandTotals,
            'grandTotal'        => (float) $payments->sum('amount_received'),
            'period'            => $period,
            'dateFrom'          => $dateFrom,
            'dateTo'            => $dateTo,
            'projectId'         => $projectId,
            'clientId'          => $clientId,
            'methodFilter'      => $methodFilter,
            'clients'           => Client::orderBy('name')->pluck('name', 'id'),
            'projects'          => Project::orderBy('name')->pluck('name', 'id'),
            'allMethods'        => ProjectPayment::whereNotNull('payment_method')->distinct()->orderBy('payment_method')->pluck('payment_method'),
        ]);
    }

    public function customerOutstanding(Request $request): View
    {
        $this->authorize('viewAny', \App\Models\Project::class);

        $search         = $request->string('search')->trim()->value();
        $locationFilter = $request->string('location')->trim()->value() ?: null;

        $projectQuery = Project::query()
            ->when($locationFilter, fn ($q, $loc) => $q->where('location', $loc))
            ->when($search !== '', fn ($q) => $q->whereHas('client', fn ($cq) => $cq->where('name', 'like', '%'.$search.'%')));

        $allIds = $projectQuery->pluck('id');

        $clientIds = Project::whereIn('id', $allIds)->distinct()->pluck('client_id')->filter();

        $purchaseTotals = Purchase::whereIn('project_id', $allIds)
            ->join('projects', 'projects.id', '=', 'purchases.project_id')
            ->select('projects.client_id', DB::raw('SUM(purchases.line_total) as total'))
            ->groupBy('projects.client_id')->pluck('total', 'client_id');

        $expenseOnlyByClient = Expense::whereIn('project_id', $allIds)
            ->join('projects', 'projects.id', '=', 'expenses.project_id')
            ->select('projects.client_id', DB::raw('SUM(expenses.amount) as total'))
            ->groupBy('projects.client_id')->pluck('total', 'client_id');

        $workExpenseByClient = Work::whereIn('project_id', $allIds)
            ->whereNotNull('amount')
            ->join('projects', 'projects.id', '=', 'works.project_id')
            ->select('projects.client_id', DB::raw('SUM(works.amount) as total'))
            ->groupBy('projects.client_id')->pluck('total', 'client_id');

        $expenseTotals = $clientIds->mapWithKeys(fn ($id) => [
            $id => (float) ($expenseOnlyByClient[$id] ?? 0) + (float) ($workExpenseByClient[$id] ?? 0),
        ]);

        $paymentTotals = ProjectPayment::whereIn('project_id', $allIds)
            ->join('projects', 'projects.id', '=', 'project_payments.project_id')
            ->select('projects.client_id', DB::raw('SUM(project_payments.amount_received) as total'))
            ->groupBy('projects.client_id')->pluck('total', 'client_id');

        $budgetTotals = Project::whereIn('id', $allIds)
            ->select('client_id', DB::raw('SUM(budget) as total'))
            ->groupBy('client_id')->pluck('total', 'client_id');

        $projectCounts = Project::whereIn('id', $allIds)
            ->select('client_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('client_id')->pluck('cnt', 'client_id');

        $clients = Client::whereIn('id', $clientIds)->orderBy('name')->get(['id', 'name']);

        $rows = $clients->map(function (Client $client) use ($purchaseTotals, $expenseTotals, $paymentTotals, $budgetTotals, $projectCounts) {
            $purchases   = (float) ($purchaseTotals[$client->id] ?? 0);
            $expenses    = (float) ($expenseTotals[$client->id] ?? 0);
            $payments    = (float) ($paymentTotals[$client->id] ?? 0);
            $budget      = (float) ($budgetTotals[$client->id] ?? 0);
            $totalCost   = $purchases + $expenses;

            return [
                'client'       => $client,
                'projects'     => (int) ($projectCounts[$client->id] ?? 0),
                'budget'       => $budget,
                'total_cost'   => $totalCost,
                'payments'     => $payments,
                'outstanding'  => $budget - $payments,
                'balance'      => $budget - $totalCost,
            ];
        });

        $totals = [
            'budget'      => $rows->sum('budget'),
            'total_cost'  => $rows->sum('total_cost'),
            'payments'    => $rows->sum('payments'),
            'outstanding' => $rows->sum('outstanding'),
            'balance'     => $rows->sum('balance'),
        ];

        return view('reports.customer-outstanding', [
            'rows'           => $rows,
            'totals'         => $totals,
            'search'         => $search,
            'locationFilter' => $locationFilter,
            'locations'      => Project::whereNotNull('location')->where('location', '!=', '')->distinct()->orderBy('location')->pluck('location'),
        ]);
    }
}
