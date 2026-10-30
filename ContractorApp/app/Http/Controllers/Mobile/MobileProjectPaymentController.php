<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ProjectPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileProjectPaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $projectId = $request->integer('project_id') ?: null;

        $payments = ProjectPayment::query()
            ->when($projectId, fn ($q, $id) => $q->where('project_id', $id))
            ->orderByDesc('payment_date')
            ->latest()
            ->get()
            ->map(fn (ProjectPayment $p) => [
                'id'               => $p->id,
                'project_id'       => $p->project_id,
                'payment_date'     => $p->payment_date?->toDateString(),
                'amount_received'  => (float) $p->amount_received,
                'payment_method'   => $p->payment_method,
                'receipt_reference'=> $p->receipt_reference,
                'notes'            => $p->notes,
            ]);

        return response()->json($payments);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id'        => ['required', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'payment_date'      => ['required', 'date'],
            'amount_received'   => ['required', 'numeric', 'min:0'],
            'payment_method'    => ['nullable', 'string', 'max:100'],
            'receipt_reference' => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string', 'max:5000'],
        ]);

        $payment = ProjectPayment::create($data);

        return response()->json(['id' => $payment->id, 'message' => 'Payment recorded.'], 201);
    }

    public function update(Request $request, ProjectPayment $payment): JsonResponse
    {
        $data = $request->validate([
            'payment_date'      => ['sometimes', 'date'],
            'amount_received'   => ['sometimes', 'numeric', 'min:0'],
            'payment_method'    => ['nullable', 'string', 'max:100'],
            'receipt_reference' => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string', 'max:5000'],
        ]);

        $payment->update($data);

        return response()->json(['message' => 'Payment updated.']);
    }

    public function destroy(ProjectPayment $payment): JsonResponse
    {
        $payment->delete();

        return response()->json(['message' => 'Payment deleted.']);
    }
}
