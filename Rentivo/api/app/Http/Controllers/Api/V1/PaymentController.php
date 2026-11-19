<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Services\NumberingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function __construct(private readonly NumberingService $numberingService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Payment::with(['customer:id,name,code'])
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('payment_date', 'desc');

        return response()->json($query->paginate($request->per_page ?? 25));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:CASH,BANK,UPI,CHEQUE,OTHER'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['receipt_number'] = $this->numberingService->next('PAYMENT');
        $validated['status'] = 'DRAFT';
        $validated['received_by'] = $request->user()->id;

        $payment = Payment::create($validated);

        return response()->json($payment->load('customer:id,name'), 201);
    }

    public function show(Payment $payment): JsonResponse
    {
        return response()->json($payment->load(['customer', 'paymentAllocations.invoice']));
    }

    public function update(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT payments can be edited.'], 422);
        }

        $validated = $request->validate([
            'payment_date' => ['sometimes', 'date'],
            'payment_method' => ['sometimes', 'in:CASH,BANK,UPI,CHEQUE,OTHER'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ]);

        $payment->update($validated);

        return response()->json($payment->fresh());
    }

    public function destroy(Payment $payment): JsonResponse
    {
        if ($payment->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT payments can be deleted.'], 422);
        }

        $payment->delete();

        return response()->json(null, 204);
    }

    public function post(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT payments can be posted.'], 422);
        }

        $payment->update([
            'status' => 'POSTED',
            'posted_by' => $request->user()->id,
            'posted_at' => now(),
        ]);

        return response()->json($payment->fresh());
    }

    public function cancel(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->status === 'POSTED' && $payment->allocated_amount > 0) {
            return response()->json(['message' => 'Cannot cancel a payment with allocations. Reverse allocations first.'], 422);
        }

        $payment->update(['status' => 'CANCELLED']);

        return response()->json($payment->fresh());
    }

    public function allocate(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->status !== 'POSTED') {
            return response()->json(['message' => 'Only POSTED payments can be allocated.'], 422);
        }

        $validated = $request->validate([
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.invoice_id' => ['required', 'exists:invoices,id'],
            'allocations.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($payment, $validated) {
            $totalToAllocate = collect($validated['allocations'])->sum('amount');
            $available = $payment->amount - $payment->allocated_amount;

            if ($totalToAllocate > $available) {
                throw ValidationException::withMessages([
                    'allocations' => ["Allocation total {$totalToAllocate} exceeds available payment balance {$available}."],
                ]);
            }

            foreach ($validated['allocations'] as $alloc) {
                $invoice = Invoice::lockForUpdate()->findOrFail($alloc['invoice_id']);
                $invoiceOutstanding = $invoice->grand_total - $invoice->amount_paid;

                if ($alloc['amount'] > $invoiceOutstanding) {
                    throw ValidationException::withMessages([
                        'allocations' => ["Amount {$alloc['amount']} exceeds invoice outstanding {$invoiceOutstanding}."],
                    ]);
                }

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'invoice_id' => $alloc['invoice_id'],
                    'allocated_amount' => $alloc['amount'],
                ]);

                $invoice->increment('amount_paid', $alloc['amount']);

                $newAmountPaid = $invoice->fresh()->amount_paid;
                $newStatus = match (true) {
                    $newAmountPaid >= $invoice->grand_total => 'PAID',
                    $newAmountPaid > 0 => 'PART_PAID',
                    default => 'POSTED',
                };
                $invoice->update(['status' => $newStatus]);
            }

            $payment->increment('allocated_amount', $totalToAllocate);
        });

        return response()->json($payment->fresh()->load('paymentAllocations.invoice'));
    }
}
