<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileReportController extends Controller
{
    public function outstanding(Request $request): JsonResponse
    {
        $search   = $request->string('search')->trim()->value();
        $clientId = $request->integer('client_id') ?: null;

        $projects = Project::query()
            ->with('client:id,name')
            ->withSum('purchases as purchases_total', 'line_total')
            ->withSum('expenses as expenses_total', 'amount')
            ->withSum('payments as payments_total', 'amount_received')
            ->when($search !== '', fn (Builder $q) => $q->where('name', 'like', '%'.$search.'%')
                ->orWhere('project_number', 'like', '%'.$search.'%'))
            ->when($clientId, fn (Builder $q, int $id) => $q->where('client_id', $id))
            ->orderBy('name')
            ->get();

        $rows = $projects->map(function (Project $p) {
            $budget      = (float) ($p->budget ?? 0);
            $purchases   = (float) ($p->purchases_total ?? 0);
            $expenses    = (float) ($p->expenses_total ?? 0);
            $payments    = (float) ($p->payments_total ?? 0);
            $totalCost   = $purchases + $expenses;
            $outstanding = $budget - $payments;
            $netBalance  = $payments - $totalCost;

            return [
                'project_id'     => $p->id,
                'project_name'   => $p->name,
                'project_number' => $p->project_number,
                'client_name'    => $p->client?->name,
                'status'         => $p->status->value,
                'budget'         => $budget,
                'purchases'      => $purchases,
                'expenses'       => $expenses,
                'total_cost'     => $totalCost,
                'payments'       => $payments,
                'outstanding'    => $outstanding,
                'net_balance'    => $netBalance,
            ];
        });

        $totals = [
            'budget'      => $rows->sum('budget'),
            'total_cost'  => $rows->sum('total_cost'),
            'payments'    => $rows->sum('payments'),
            'outstanding' => $rows->sum('outstanding'),
            'net_balance' => $rows->sum('net_balance'),
        ];

        return response()->json([
            'rows'   => $rows->values(),
            'totals' => $totals,
        ]);
    }

    public function collections(Request $request): JsonResponse
    {
        $dateFrom      = $request->string('date_from')->trim()->value() ?: null;
        $dateTo        = $request->string('date_to')->trim()->value()   ?: null;
        $projectId     = $request->integer('project_id')  ?: null;
        $clientId      = $request->integer('client_id')   ?: null;
        $paymentMethod = $request->string('payment_method')->trim()->value() ?: null;

        $knownMethods = ['cash', 'bank_transfer', 'upi', 'cheque', 'credit_card', 'other'];

        // Aggregate payments by project + payment_method
        $aggregates = ProjectPayment::query()
            ->join('projects', 'project_payments.project_id', '=', 'projects.id')
            ->whereNull('project_payments.deleted_at')
            ->whereNull('projects.deleted_at')
            ->when($dateFrom, fn (Builder $q, string $d) => $q->whereDate('project_payments.payment_date', '>=', $d))
            ->when($dateTo,   fn (Builder $q, string $d) => $q->whereDate('project_payments.payment_date', '<=', $d))
            ->when($projectId, fn (Builder $q, int $id)  => $q->where('project_payments.project_id', $id))
            ->when($clientId,  fn (Builder $q, int $id)  => $q->where('projects.client_id', $id))
            ->when($paymentMethod, fn (Builder $q, string $m) => $q->where('project_payments.payment_method', $m))
            ->select([
                'project_payments.project_id',
                DB::raw('COALESCE(project_payments.payment_method, \'other\') as method'),
                DB::raw('SUM(project_payments.amount_received) as subtotal'),
            ])
            ->groupBy('project_payments.project_id', DB::raw('COALESCE(project_payments.payment_method, \'other\')'))
            ->get();

        // Load projects that have payments in the result
        $projectIds = $aggregates->pluck('project_id')->unique();
        $projects = Project::query()
            ->with('client:id,name')
            ->whereIn('id', $projectIds)
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        // Discover methods present in data (preserve known ordering)
        $methodsPresent = $aggregates->pluck('method')->unique()->sort()->values();
        $orderedMethods = collect($knownMethods)
            ->filter(fn ($m) => $methodsPresent->contains($m))
            ->values();
        // Append any unknown methods at end
        $extraMethods = $methodsPresent->diff($knownMethods)->values();
        $allMethods = $orderedMethods->merge($extraMethods)->values();

        // If filtering by a single method, show only that column
        if ($paymentMethod) {
            $allMethods = collect([$paymentMethod]);
        }

        // Build pivot indexed by project_id => method => subtotal
        $pivot = [];
        foreach ($aggregates as $agg) {
            $pivot[$agg->project_id][$agg->method] = (float) $agg->subtotal;
        }

        // Build rows
        $rows = $projects->map(function (Project $p) use ($pivot, $allMethods) {
            $methodAmounts = $pivot[$p->id] ?? [];
            $row = [
                'project_id'     => $p->id,
                'project_name'   => $p->name,
                'project_number' => $p->project_number,
                'client_name'    => $p->client?->name,
            ];
            $total = 0.0;
            foreach ($allMethods as $m) {
                $amt = $methodAmounts[$m] ?? 0.0;
                $row[$m] = $amt;
                $total  += $amt;
            }
            $row['total'] = $total;
            return $row;
        })->values();

        // Grand totals
        $grandTotals = ['total' => 0.0];
        foreach ($allMethods as $m) {
            $grandTotals[$m] = $rows->sum($m);
            $grandTotals['total'] += $grandTotals[$m];
        }

        return response()->json([
            'rows'            => $rows,
            'totals'          => $grandTotals,
            'payment_methods' => $allMethods->values(),
        ]);
    }
}
