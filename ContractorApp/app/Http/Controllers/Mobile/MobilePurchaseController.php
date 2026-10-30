<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobilePurchaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $projectId = $request->integer('project_id') ?: null;

        $purchases = Purchase::query()
            ->with(['product:id,name,unit'])
            ->when($projectId, fn ($q, $id) => $q->where('project_id', $id))
            ->orderByDesc('purchase_date')
            ->latest()
            ->get()
            ->map(fn (Purchase $p) => [
                'id'                => $p->id,
                'project_id'        => $p->project_id,
                'product_id'        => $p->product_id,
                'product_name'      => $p->product?->name,
                'product_unit'      => $p->product?->unit,
                'purchase_date'     => $p->purchase_date?->toDateString(),
                'quantity'          => (float) $p->quantity,
                'unit_amount'       => (float) $p->unit_amount,
                'line_total'        => (float) $p->line_total,
                'supplier'          => $p->supplier,
                'invoice_reference' => $p->invoice_reference,
                'notes'             => $p->notes,
            ]);

        return response()->json($purchases);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id'        => ['required', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'product_id'        => ['nullable', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'purchase_date'     => ['required', 'date'],
            'quantity'          => ['required', 'numeric', 'min:0.0001'],
            'unit_amount'       => ['required', 'numeric', 'min:0'],
            'supplier'          => ['nullable', 'string', 'max:200'],
            'invoice_reference' => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string', 'max:5000'],
        ]);

        $purchase = Purchase::create($data);

        return response()->json(['id' => $purchase->id, 'line_total' => (float) $purchase->line_total, 'message' => 'Purchase recorded.'], 201);
    }

    public function update(Request $request, Purchase $purchase): JsonResponse
    {
        $data = $request->validate([
            'product_id'        => ['nullable', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'purchase_date'     => ['sometimes', 'date'],
            'quantity'          => ['sometimes', 'numeric', 'min:0.0001'],
            'unit_amount'       => ['sometimes', 'numeric', 'min:0'],
            'supplier'          => ['nullable', 'string', 'max:200'],
            'invoice_reference' => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string', 'max:5000'],
        ]);

        $purchase->update($data);

        return response()->json(['line_total' => (float) $purchase->fresh()->line_total, 'message' => 'Purchase updated.']);
    }

    public function destroy(Purchase $purchase): JsonResponse
    {
        $purchase->delete();

        return response()->json(['message' => 'Purchase deleted.']);
    }
}
