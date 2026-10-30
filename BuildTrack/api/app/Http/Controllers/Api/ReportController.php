<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\Paginates;
use App\Http\Controllers\Api\Concerns\Scopes;
use App\Http\Controllers\Controller;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\Project;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use Paginates, Scopes;

    public function outstanding(Request $request): JsonResponse
    {
        $query = Project::with(['customer', 'location']);

        if (! $request->user()->isAdmin()) {
            $query->where('assigned_user_id', $request->user()->id);
        }

        if (! $this->boolQuery($request, 'includeArchived')) {
            $query->where('is_archived', false);
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(fn ($q) => $q->where('project_name', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($c) => $c->where('customer_name', 'like', "%{$search}%")));
        }

        if ($request->filled('customerId')) {
            $query->where('customer_id', (int) $request->query('customerId'));
        }

        if ($request->filled('projectId')) {
            $query->where('id', (int) $request->query('projectId'));
        }

        if ($request->filled('locationId')) {
            $query->where('location_id', (int) $request->query('locationId'));
        }

        $dateFrom = $request->query('dateFrom');
        $dateTo = $request->query('dateTo');

        $total = (clone $query)->count();
        $allIds = (clone $query)->pluck('id')->all();

        $page = max(1, (int) $request->query('page', 1));
        $pageSize = min(200, max(1, (int) $request->query('pageSize', 20)));

        $pageProjects = (clone $query)->orderBy('project_name')
            ->skip(($page - 1) * $pageSize)->take($pageSize)->get();
        $pagedIds = $pageProjects->pluck('id')->all();

        $purchSums = $this->purchaseSums($pagedIds, $dateFrom, $dateTo);
        $expSums = $this->expenseSums($pagedIds, $dateFrom, $dateTo);
        $payments = CustomerPayment::where('is_voided', false)->whereIn('project_id', $pagedIds)
            ->selectRaw('project_id, SUM(amount_received) as total, MAX(payment_date) as last_date')
            ->groupBy('project_id')->get()->keyBy('project_id');

        $items = $pageProjects->map(function (Project $p) use ($purchSums, $expSums, $payments) {
            $purch = (float) ($purchSums[$p->id] ?? 0);
            $exp = (float) ($expSums[$p->id] ?? 0);
            $pay = $payments->get($p->id);
            $totalCost = $purch + $exp;
            $totalReceived = (float) ($pay->total ?? 0);
            $budget = $p->budget !== null ? (float) $p->budget : null;

            return [
                'projectId' => $p->id,
                'projectName' => $p->project_name,
                'customerId' => $p->customer_id,
                'customerName' => $p->customer?->customer_name ?? '',
                'locationId' => $p->location_id,
                'locationName' => $p->location?->location_name,
                'budget' => $budget,
                'totalPurchases' => $purch,
                'totalExpenses' => $exp,
                'totalProjectCost' => $totalCost,
                'totalReceived' => $totalReceived,
                'remainingBudget' => $budget !== null ? $budget - $totalCost : null,
                'balanceDue' => $budget !== null ? $budget - $totalReceived : null,
                'budgetUtilizationPct' => ($budget !== null && $budget > 0) ? round($totalCost / $budget * 100, 1) : null,
                'isOverBudget' => $budget !== null && $totalCost > $budget,
                'isArchived' => $p->is_archived,
                'lastPaymentDate' => $pay?->last_date ? \Illuminate\Support\Carbon::parse($pay->last_date)->toIso8601String() : null,
            ];
        })->values();

        $globalPurch = (float) $this->purchaseQuery($allIds, $dateFrom, $dateTo)->sum('line_total');
        $globalExp = (float) $this->expenseQuery($allIds, $dateFrom, $dateTo)->sum('amount');
        $globalReceived = (float) CustomerPayment::where('is_voided', false)->whereIn('project_id', $allIds)->sum('amount_received');
        $globalBudget = (float) (clone $query)->sum('budget');

        return response()->json([
            'items' => $items,
            'totals' => [
                'totalBudget' => $globalBudget,
                'totalPurchases' => $globalPurch,
                'totalExpenses' => $globalExp,
                'totalProjectCost' => $globalPurch + $globalExp,
                'totalReceived' => $globalReceived,
                'projectCount' => $total,
                'overBudgetCount' => $items->where('isOverBudget', true)->count(),
            ],
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ]);
    }

    public function projectTransactions(Request $request, Project $project): JsonResponse
    {
        if (! $request->user()->isAdmin() && $project->assigned_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $items = collect();

        $items = $items->merge(Purchase::with('product')->where('project_id', $project->id)->where('is_archived', false)
            ->get()->map(fn (Purchase $pu) => [
                'transactionType' => 'Purchase',
                'id' => $pu->id,
                'date' => $pu->purchase_date?->toIso8601String(),
                'reference' => $pu->invoice_reference,
                'description' => $pu->product?->product_name ?? 'Purchase',
                'amount' => (float) $pu->line_total,
                'notes' => $pu->notes,
            ]));

        $items = $items->merge(Expense::where('project_id', $project->id)->where('is_archived', false)
            ->get()->map(fn (Expense $e) => [
                'transactionType' => 'Expense',
                'id' => $e->id,
                'date' => $e->expense_date?->toIso8601String(),
                'reference' => $e->reference_number,
                'description' => $e->expense_heading,
                'amount' => (float) $e->amount,
                'notes' => $e->notes,
            ]));

        $items = $items->merge(CustomerPayment::where('project_id', $project->id)->where('is_voided', false)
            ->get()->map(fn (CustomerPayment $cp) => [
                'transactionType' => 'Payment',
                'id' => $cp->id,
                'date' => $cp->payment_date?->toIso8601String(),
                'reference' => $cp->receipt_reference,
                'description' => 'Customer Payment',
                'amount' => (float) $cp->amount_received,
                'notes' => $cp->notes,
            ]));

        return response()->json($items->sortByDesc('date')->values());
    }

    /** @param  array<int, int>  $ids  @return array<int, float> */
    private function purchaseSums(array $ids, ?string $from, ?string $to): array
    {
        return $this->purchaseQuery($ids, $from, $to)
            ->selectRaw('project_id, SUM(line_total) as total')->groupBy('project_id')
            ->pluck('total', 'project_id')->map(fn ($v) => (float) $v)->all();
    }

    /** @param  array<int, int>  $ids  @return array<int, float> */
    private function expenseSums(array $ids, ?string $from, ?string $to): array
    {
        return $this->expenseQuery($ids, $from, $to)
            ->selectRaw('project_id, SUM(amount) as total')->groupBy('project_id')
            ->pluck('total', 'project_id')->map(fn ($v) => (float) $v)->all();
    }

    /** @param  array<int, int>  $ids */
    private function purchaseQuery(array $ids, ?string $from, ?string $to): Builder
    {
        return Purchase::where('is_archived', false)->whereIn('project_id', $ids)
            ->when($from, fn ($q) => $q->where('purchase_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('purchase_date', '<=', $to));
    }

    /** @param  array<int, int>  $ids */
    private function expenseQuery(array $ids, ?string $from, ?string $to): Builder
    {
        return Expense::where('is_archived', false)->whereIn('project_id', $ids)
            ->when($from, fn ($q) => $q->where('expense_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('expense_date', '<=', $to));
    }
}
