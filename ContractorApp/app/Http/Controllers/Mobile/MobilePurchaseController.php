<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Scopes\ContractorScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MobilePurchaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $projectId = $request->integer('project_id') ?: null;

        $purchases = Purchase::query()
            ->with(['product:id,name,unit', 'project:id,name,project_number', 'items.product:id,name,unit', 'items.project:id,name,project_number'])
            ->when($projectId, fn ($q, $id) => $q->where('project_id', $id))
            ->orderByDesc('purchase_date')
            ->latest()
            ->get()
            ->map(fn (Purchase $p) => self::mapPurchase($p));

        return response()->json($purchases);
    }

    public function store(Request $request): JsonResponse
    {
        $contractorId = ContractorScope::currentContractorId();
        $data = $request->validate([
            'project_id'        => ['required', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')->when($contractorId, fn ($r) => $r->where('contractor_id', $contractorId))],
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

    /**
     * Create a purchase header with multiple items.
     * Supports two modes:
     *  – Project-locked: header has project_id; items have no project_id.
     *  – Multi-project: header has no project_id; each item carries its own project_id.
     */
    public function storeBatch(Request $request): JsonResponse
    {
        $contractorId = ContractorScope::currentContractorId();
        $projectRule  = Rule::exists('projects', 'id')->whereNull('deleted_at')
            ->when($contractorId, fn ($r) => $r->where('contractor_id', $contractorId));

        $data = $request->validate([
            'project_id'           => ['nullable', 'integer', $projectRule],
            'purchase_date'        => ['required', 'date'],
            'invoice_reference'    => ['nullable', 'string', 'max:100'],
            'notes'                => ['nullable', 'string', 'max:5000'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.project_id'   => ['nullable', 'integer', $projectRule],
            'items.*.product_id'   => ['nullable', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.supplier'     => ['nullable', 'string', 'max:200'],
            'items.*.unit'         => ['nullable', 'string', 'max:50'],
            'items.*.quantity'     => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_amount'  => ['required', 'numeric', 'min:0'],
            'items.*.line_total'   => ['nullable', 'numeric', 'min:0'],
        ]);

        // At least one of header project_id or per-item project_id must be present
        $hasHeaderProject = ! empty($data['project_id']);
        $itemsMissingProject = collect($data['items'])->filter(fn ($i) => empty($i['project_id']) && ! $hasHeaderProject)->count();
        if ($itemsMissingProject > 0 && ! $hasHeaderProject) {
            return response()->json(['message' => 'Each item must have a project or a header project must be specified.'], 422);
        }

        // Determine header project_id for single-project batch (if all items share the same project)
        $headerProjectId = $data['project_id'] ?? null;
        if ($headerProjectId === null) {
            $itemProjectIds = array_unique(array_filter(array_column($data['items'], 'project_id')));
            if (count($itemProjectIds) === 1) {
                $headerProjectId = (int) $itemProjectIds[0];
            }
        }

        /** @var Purchase $purchase */
        $purchase = DB::transaction(function () use ($data, $headerProjectId) {
            $header = new Purchase([
                'project_id'        => $headerProjectId,
                'purchase_date'     => $data['purchase_date'],
                'invoice_reference' => $data['invoice_reference'] ?? null,
                'notes'             => $data['notes'] ?? null,
                'quantity'          => null,
                'unit_amount'       => null,
                'line_total'        => 0,
            ]);
            $header->save();

            $total = 0.0;
            foreach ($data['items'] as $row) {
                // Backend always recalculates; ignore frontend line_total
                $lineTotal = round((float) $row['quantity'] * (float) $row['unit_amount'], 2);
                $total += $lineTotal;

                $item = new PurchaseItem([
                    'purchase_id' => $header->id,
                    'project_id'  => $row['project_id'] ?? null,
                    'product_id'  => $row['product_id'] ?? null,
                    'supplier'    => $row['supplier'] ?? null,
                    'unit'        => $row['unit'] ?? null,
                    'quantity'    => $row['quantity'],
                    'unit_amount' => $row['unit_amount'],
                    'line_total'  => $lineTotal,
                ]);
                $item->save();
            }

            $header->total_amount = round($total, 2);
            $header->line_total   = round($total, 2);
            $header->saveQuietly();

            return $header;
        });

        return response()->json([
            'id'           => $purchase->id,
            'total_amount' => (float) $purchase->total_amount,
            'message'      => 'Purchase recorded.',
        ], 201);
    }

    public function destroy(Purchase $purchase): JsonResponse
    {
        $purchase->delete();

        return response()->json(['message' => 'Purchase deleted.']);
    }

    private static function mapPurchase(Purchase $p): array
    {
        return [
            'id'                => $p->id,
            'project_id'        => $p->project_id,
            'project_name'      => $p->project?->name,
            'project_number'    => $p->project?->project_number,
            'product_id'        => $p->product_id,
            'product_name'      => $p->product?->name,
            'product_unit'      => $p->product?->unit,
            'purchase_date'     => $p->purchase_date?->toDateString(),
            'quantity'          => $p->quantity !== null ? (float) $p->quantity : null,
            'unit_amount'       => $p->unit_amount !== null ? (float) $p->unit_amount : null,
            'line_total'        => (float) $p->line_total,
            'total_amount'      => $p->total_amount !== null ? (float) $p->total_amount : null,
            'supplier'          => $p->supplier,
            'invoice_reference' => $p->invoice_reference,
            'notes'             => $p->notes,
            'items'             => $p->items->map(fn ($i) => [
                'id'             => $i->id,
                'project_id'     => $i->project_id,
                'project_name'   => $i->project?->name,
                'project_number' => $i->project?->project_number,
                'product_id'     => $i->product_id,
                'product_name'   => $i->product?->name,
                'product_unit'   => $i->product?->unit,
                'supplier'       => $i->supplier,
                'unit'           => $i->unit,
                'quantity'       => (float) $i->quantity,
                'unit_amount'    => (float) $i->unit_amount,
                'line_total'     => (float) $i->line_total,
            ]),
        ];
    }
}
