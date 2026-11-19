<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InventoryBalance;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerReportController extends Controller
{
    public function show360(Customer $customer): JsonResponse
    {
        $activeProjects = $customer->projects()->where('status', 'ACTIVE')->count();

        $equipmentAtSites = InventoryBalance::where('location_type', 'PROJECT')
            ->whereIn('location_id', $customer->projects()->pluck('id'))
            ->sum('quantity_on_hand');

        $financialSummary = $this->getFinancialSummary($customer);

        return response()->json([
            'customer' => $customer,
            'kpis' => [
                'active_projects' => $activeProjects,
                'total_equipment_at_sites' => (float) $equipmentAtSites,
                'invoiced_total' => $financialSummary['invoiced_total'],
                'paid_total' => $financialSummary['paid_total'],
                'outstanding' => $financialSummary['outstanding'],
            ],
            'active_projects' => $customer->projects()->where('status', 'ACTIVE')
                ->with(['invoices' => fn ($q) => $q->whereIn('status', ['POSTED', 'PART_PAID', 'PAID'])])
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                    'status' => $p->status,
                    'equipment_on_hand' => (float) InventoryBalance::where('location_type', 'PROJECT')
                        ->where('location_id', $p->id)
                        ->sum('quantity_on_hand'),
                    'invoiced_amount' => $p->invoices->sum('grand_total'),
                ]),
        ]);
    }

    public function equipment(Customer $customer): JsonResponse
    {
        $balances = InventoryBalance::with(['product.category'])
            ->where('location_type', 'PROJECT')
            ->whereIn('location_id', $customer->projects()->pluck('id'))
            ->where('quantity_on_hand', '>', 0)
            ->get()
            ->map(function ($balance) {
                $project = \App\Models\Project::find($balance->location_id);

                return [
                    'product' => $balance->product,
                    'project' => ['id' => $project?->id, 'name' => $project?->name, 'code' => $project?->code],
                    'quantity_on_hand' => $balance->quantity_on_hand,
                ];
            });

        return response()->json($balances);
    }

    public function financialSummary(Customer $customer): JsonResponse
    {
        return response()->json($this->getFinancialSummary($customer));
    }

    private function getFinancialSummary(Customer $customer): array
    {
        $invoicedTotal = $customer->invoices()
            ->whereIn('status', ['POSTED', 'PART_PAID', 'PAID'])
            ->sum('grand_total');

        $creditNoteTotal = $customer->creditNotes()
            ->where('status', 'POSTED')
            ->sum('grand_total');

        $paidTotal = $customer->payments()
            ->where('status', 'POSTED')
            ->sum('allocated_amount');

        $outstanding = $invoicedTotal - $creditNoteTotal - $paidTotal;

        $ageing = [
            '0_30' => 0,
            '31_60' => 0,
            '61_90' => 0,
            '90_plus' => 0,
        ];

        $customer->invoices()
            ->whereIn('status', ['POSTED', 'PART_PAID'])
            ->get()
            ->each(function ($invoice) use (&$ageing) {
                $days = now()->diffInDays($invoice->invoice_date);
                $outstanding = $invoice->grand_total - $invoice->amount_paid;

                if ($days <= 30) {
                    $ageing['0_30'] += $outstanding;
                } elseif ($days <= 60) {
                    $ageing['31_60'] += $outstanding;
                } elseif ($days <= 90) {
                    $ageing['61_90'] += $outstanding;
                } else {
                    $ageing['90_plus'] += $outstanding;
                }
            });

        return [
            'invoiced_total' => (float) $invoicedTotal,
            'credit_note_total' => (float) $creditNoteTotal,
            'paid_total' => (float) $paidTotal,
            'outstanding' => (float) $outstanding,
            'ageing' => $ageing,
        ];
    }
}
