<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Project;
use App\Models\ProjectPayment;
use App\Models\Purchase;
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

        $allPurchases = Purchase::whereIn('project_id', $allIds)->sum('line_total');
        $allExpenses  = Expense::whereIn('project_id', $allIds)->sum('amount');
        $allPayments  = ProjectPayment::whereIn('project_id', $allIds)->sum('amount_received');
        $allBudget    = Project::whereIn('id', $allIds)->sum('budget');

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

        $expenseTotals = Expense::whereIn('project_id', $pageIds)
            ->select('project_id', DB::raw('SUM(amount) as total'))
            ->groupBy('project_id')->pluck('total', 'project_id');

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

        $expenseTotals = Expense::whereIn('project_id', $allIds)
            ->join('projects', 'projects.id', '=', 'expenses.project_id')
            ->select('projects.client_id', DB::raw('SUM(expenses.amount) as total'))
            ->groupBy('projects.client_id')->pluck('total', 'client_id');

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
