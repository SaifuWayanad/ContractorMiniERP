<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\InvoiceAllocation;
use App\Models\RentalPeriod;
use App\Models\ReturnDetail;
use App\Services\NumberingService;
use App\Services\RentalCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly NumberingService $numberingService,
        private readonly RentalCalculationService $calculationService,
    ) {}

    public function uninvoicedReturns(Request $request): JsonResponse
    {
        $query = ReturnDetail::with([
            'equipmentReturn:id,return_number,return_date,project_id,customer_id',
            'equipmentReturn.project:id,name,code',
            'equipmentReturn.customer:id,name,code',
            'product:id,name,code,unit_of_measure',
            'dispatchDetail',
            'invoiceAllocations',
        ])
            ->whereHas('equipmentReturn', fn ($q) => $q->where('status', 'POSTED'))
            ->when($request->customer_id, fn ($q) => $q->whereHas('equipmentReturn', fn ($r) => $r->where('customer_id', $request->customer_id)))
            ->when($request->project_id, fn ($q) => $q->whereHas('equipmentReturn', fn ($r) => $r->where('project_id', $request->project_id)))
            ->get()
            ->filter(fn ($detail) => $detail->uninvoiced_quantity > 0)
            ->map(fn ($detail) => [
                'return_detail_id' => $detail->id,
                'return_number' => $detail->equipmentReturn->return_number,
                'return_date' => $detail->equipmentReturn->return_date,
                'customer' => $detail->equipmentReturn->customer,
                'project' => $detail->equipmentReturn->project,
                'product' => $detail->product,
                'quantity_returned' => $detail->quantity_returned,
                'quantity_invoiced' => $detail->quantity_returned - $detail->uninvoiced_quantity,
                'uninvoiced_quantity' => $detail->uninvoiced_quantity,
                'rental_period' => $this->getRentalPeriodForDetail($detail),
                'estimated_amount' => $this->estimateAmount($detail),
            ])
            ->values();

        return response()->json($query);
    }

    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'invoice_date' => ['required', 'date'],
            'return_detail_ids' => ['required', 'array', 'min:1'],
            'return_detail_ids.*' => ['exists:return_details,id'],
        ]);

        $lines = $this->buildInvoiceLines($validated['return_detail_ids'], $validated['invoice_date']);

        return response()->json([
            'lines' => $lines,
            'subtotal' => collect($lines)->sum('line_amount'),
            'tax_total' => collect($lines)->sum('tax_amount'),
            'grand_total' => collect($lines)->sum('total_amount'),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['customer:id,name,code', 'project:id,name,code'])
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->project_id, fn ($q) => $q->where('project_id', $request->project_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->from_date, fn ($q) => $q->where('invoice_date', '>=', $request->from_date))
            ->when($request->to_date, fn ($q) => $q->where('invoice_date', '<=', $request->to_date))
            ->orderBy('invoice_date', 'desc');

        return response()->json($query->paginate($request->per_page ?? 25));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'invoice_type' => ['in:RENTAL,LOSS_RECOVERY,DAMAGE_RECOVERY,ADJUSTMENT'],
            'return_detail_ids' => ['required_if:invoice_type,RENTAL', 'array'],
            'return_detail_ids.*' => ['exists:return_details,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $invoice = DB::transaction(function () use ($validated, $request) {
            $lines = $this->buildInvoiceLines($validated['return_detail_ids'] ?? [], $validated['invoice_date']);

            $subtotal = collect($lines)->sum('line_amount');
            $discountTotal = 0;
            $taxTotal = collect($lines)->sum('tax_amount');
            $grandTotal = $subtotal - $discountTotal + $taxTotal;

            $invoice = Invoice::create([
                'invoice_number' => $this->numberingService->next('INVOICE'),
                'customer_id' => $validated['customer_id'],
                'project_id' => $validated['project_id'] ?? null,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'invoice_type' => $validated['invoice_type'] ?? 'RENTAL',
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'status' => 'DRAFT',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($lines as $line) {
                $detail = $invoice->invoiceDetails()->create([
                    'return_detail_id' => $line['return_detail_id'],
                    'rental_period_id' => $line['rental_period_id'] ?? null,
                    'dispatch_detail_id' => $line['dispatch_detail_id'] ?? null,
                    'product_id' => $line['product_id'],
                    'description_snapshot' => $line['description'],
                    'quantity' => $line['quantity'],
                    'rental_days' => $line['rental_days'],
                    'minimum_days_snapshot' => $line['minimum_days'],
                    'minimum_amount_snapshot' => $line['minimum_amount'],
                    'addon_per_day_snapshot' => $line['addon_per_day'],
                    'unit_amount' => $line['unit_amount'],
                    'line_amount' => $line['line_amount'],
                    'tax_rate_snapshot' => $line['tax_rate'],
                    'tax_amount' => $line['tax_amount'],
                    'total_amount' => $line['total_amount'],
                ]);

                if ($line['return_detail_id']) {
                    InvoiceAllocation::create([
                        'return_detail_id' => $line['return_detail_id'],
                        'invoice_detail_id' => $detail->id,
                        'quantity_invoiced' => $line['quantity'],
                        'amount_allocated' => $line['total_amount'],
                    ]);
                }
            }

            return $invoice;
        });

        return response()->json($invoice->load(['customer:id,name', 'invoiceDetails.product:id,name,code']), 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json($invoice->load([
            'customer', 'project', 'invoiceDetails.product', 'invoiceDetails.rentalPeriod',
            'paymentAllocations.payment',
        ]));
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT invoices can be edited.'], 422);
        }

        $invoice->update($request->only(['notes', 'due_date']));

        return response()->json($invoice->fresh());
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        if ($invoice->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT invoices can be deleted.'], 422);
        }

        DB::transaction(function () use ($invoice) {
            $invoice->invoiceDetails->each(fn ($d) => $d->invoiceAllocations()->delete());
            $invoice->invoiceDetails()->delete();
            $invoice->delete();
        });

        return response()->json(null, 204);
    }

    public function post(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT invoices can be posted.'], 422);
        }

        $invoice->update([
            'status' => 'POSTED',
            'posted_by' => $request->user()->id,
            'posted_at' => now(),
        ]);

        return response()->json($invoice->fresh());
    }

    public function cancel(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->status === 'POSTED' && $invoice->amount_paid > 0) {
            return response()->json(['message' => 'Cannot cancel an invoice with payments. Use credit note workflow.'], 422);
        }

        DB::transaction(function () use ($invoice) {
            if ($invoice->status === 'DRAFT') {
                $invoice->invoiceDetails->each(fn ($d) => $d->invoiceAllocations()->delete());
            }
            $invoice->update(['status' => 'CANCELLED']);
        });

        return response()->json($invoice->fresh());
    }

    private function buildInvoiceLines(array $returnDetailIds, string $invoiceDate): array
    {
        $lines = [];

        foreach ($returnDetailIds as $returnDetailId) {
            $returnDetail = ReturnDetail::with(['product', 'dispatchDetail', 'invoiceAllocations', 'equipmentReturn'])->findOrFail($returnDetailId);

            $uninvoicedQty = $returnDetail->uninvoiced_quantity;
            if ($uninvoicedQty <= 0) {
                throw ValidationException::withMessages([
                    'return_detail_ids' => ["Return detail {$returnDetailId} has no uninvoiced quantity."],
                ]);
            }

            $rentalPeriod = RentalPeriod::where('source_dispatch_detail_id', $returnDetail->dispatch_detail_id)
                ->where('status', 'CLOSED')
                ->where('quantity', '>=', $uninvoicedQty)
                ->orderBy('rental_end_date', 'desc')
                ->first();

            $days = 0;
            $unitAmount = 0;

            if ($rentalPeriod) {
                $calc = $this->calculationService->calculateRentalPeriod($rentalPeriod);
                $days = $calc['days'];
                $unitAmount = $calc['unit_amount'];
            }

            $dd = $returnDetail->dispatchDetail;
            $lineAmount = round($uninvoicedQty * $unitAmount, 2);
            $taxRate = (float) ($dd->tax_rate_snapshot ?? 0);
            $taxAmount = round($lineAmount * $taxRate / 100, 2);

            $lines[] = [
                'return_detail_id' => $returnDetailId,
                'rental_period_id' => $rentalPeriod?->id,
                'dispatch_detail_id' => $returnDetail->dispatch_detail_id,
                'product_id' => $returnDetail->product_id,
                'description' => $returnDetail->product->name,
                'quantity' => $uninvoicedQty,
                'rental_days' => $days,
                'minimum_days' => $dd->minimum_rental_days_snapshot,
                'minimum_amount' => $dd->minimum_rental_amount_snapshot,
                'addon_per_day' => $dd->addon_amount_per_day_snapshot,
                'unit_amount' => $unitAmount,
                'line_amount' => $lineAmount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total_amount' => $lineAmount + $taxAmount,
            ];
        }

        return $lines;
    }

    private function getRentalPeriodForDetail(ReturnDetail $detail): ?array
    {
        $period = RentalPeriod::where('source_dispatch_detail_id', $detail->dispatch_detail_id)
            ->where('status', 'CLOSED')
            ->first();

        if (!$period) {
            return null;
        }

        return [
            'start_date' => $period->rental_start_date,
            'end_date' => $period->rental_end_date,
        ];
    }

    private function estimateAmount(ReturnDetail $detail): float
    {
        $period = RentalPeriod::where('source_dispatch_detail_id', $detail->dispatch_detail_id)->first();

        if (!$period || !$period->rental_end_date) {
            return 0;
        }

        $calc = $this->calculationService->calculateRentalPeriod($period);

        return round($detail->uninvoiced_quantity * ($calc['unit_amount'] ?? 0), 2);
    }
}
