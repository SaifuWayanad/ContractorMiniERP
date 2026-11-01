<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
