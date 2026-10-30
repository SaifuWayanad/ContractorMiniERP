<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\Paginates;
use App\Http\Controllers\Api\Concerns\Scopes;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\CustomerPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    use Paginates, Scopes;

    public function index(Request $request): JsonResponse
    {
        $query = CustomerPayment::with(['project.customer'])->orderByDesc('payment_date');

        if (! $this->boolQuery($request, 'includeVoided')) {
            $query->where('is_voided', false);
        }

        if ($request->filled('projectId')) {
            $query->where('project_id', (int) $request->query('projectId'));
        }

        if ($request->filled('customerId')) {
            $query->whereHas('project', fn ($p) => $p->where('customer_id', (int) $request->query('customerId')));
        }

        if ($request->filled('startDate')) {
            $query->where('payment_date', '>=', $request->query('startDate'));
        }

        if ($request->filled('endDate')) {
            $query->where('payment_date', '<=', $request->query('endDate'));
        }

        return response()->json($this->paginate($query, $request, PaymentResource::class));
    }

    public function show(CustomerPayment $payment): JsonResponse
    {
        return response()->json((new PaymentResource($payment->load(['project.customer'])))->resolve());
    }

    public function store(Request $request): JsonResponse
    {
        $payment = CustomerPayment::create($this->attributes($request));

        return response()->json(['id' => $payment->id], Response::HTTP_CREATED);
    }

    public function update(Request $request, CustomerPayment $payment): Response|JsonResponse
    {
        if ($payment->is_voided) {
            return response()->json(['message' => 'Cannot update a voided payment.'], 409);
        }

        $payment->update($this->attributes($request));

        return response()->noContent();
    }

    public function destroy(CustomerPayment $payment): Response
    {
        $payment->update(['is_voided' => true]);

        return response()->noContent();
    }

    /** @return array<string, mixed> */
    private function attributes(Request $request): array
    {
        $data = $request->validate([
            'projectId' => ['required', 'integer', 'exists:projects,id'],
            'paymentDate' => ['required', 'date'],
            'amountReceived' => ['required', 'numeric'],
            'paymentMethod' => ['nullable', 'string', 'max:100'],
            'receiptReference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        return [
            'project_id' => $data['projectId'],
            'payment_date' => $data['paymentDate'],
            'amount_received' => $data['amountReceived'],
            'payment_method' => $data['paymentMethod'] ?? '',
            'receipt_reference' => $data['receiptReference'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }
}
