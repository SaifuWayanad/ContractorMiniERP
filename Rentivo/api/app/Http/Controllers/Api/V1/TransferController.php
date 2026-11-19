<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transfer;
use App\Services\TransferService;
use App\Services\NumberingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function __construct(
        private readonly TransferService $transferService,
        private readonly NumberingService $numberingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Transfer::with(['fromProject:id,name,code', 'toProject:id,name,code', 'fromCustomer:id,name', 'toCustomer:id,name'])
            ->when($request->from_project_id, fn ($q) => $q->where('from_project_id', $request->from_project_id))
            ->when($request->to_project_id, fn ($q) => $q->where('to_project_id', $request->to_project_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('transfer_date', 'desc');

        return response()->json($query->paginate($request->per_page ?? 25));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_project_id' => ['required', 'exists:projects,id'],
            'to_project_id' => ['required', 'exists:projects,id', 'different:from_project_id'],
            'from_customer_id' => ['required', 'exists:customers,id'],
            'to_customer_id' => ['required', 'exists:customers,id'],
            'transfer_date' => ['required', 'date'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'operator_id' => ['nullable', 'exists:operators,id'],
            'remarks' => ['nullable', 'string'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.source_dispatch_detail_id' => ['required', 'exists:dispatch_details,id'],
            'details.*.product_id' => ['required', 'exists:products,id'],
            'details.*.quantity_transferred' => ['required', 'numeric', 'min:0.001'],
            'details.*.remarks' => ['nullable', 'string'],
        ]);

        $transfer = DB::transaction(function () use ($validated, $request) {
            $transfer = Transfer::create([
                'transfer_number' => $this->numberingService->next('TRANSFER'),
                'from_project_id' => $validated['from_project_id'],
                'to_project_id' => $validated['to_project_id'],
                'from_customer_id' => $validated['from_customer_id'],
                'to_customer_id' => $validated['to_customer_id'],
                'transfer_date' => $validated['transfer_date'],
                'vehicle_id' => $validated['vehicle_id'] ?? null,
                'operator_id' => $validated['operator_id'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'status' => 'DRAFT',
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['details'] as $detail) {
                $transfer->transferDetails()->create($detail);
            }

            return $transfer;
        });

        return response()->json($transfer->load(['fromProject:id,name', 'toProject:id,name', 'transferDetails.product:id,name,code']), 201);
    }

    public function show(Transfer $transfer): JsonResponse
    {
        return response()->json($transfer->load([
            'fromProject', 'toProject', 'fromCustomer', 'toCustomer',
            'vehicle', 'operator', 'transferDetails.product',
        ]));
    }

    public function update(Request $request, Transfer $transfer): JsonResponse
    {
        if ($transfer->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT transfers can be edited.'], 422);
        }

        $transfer->update($request->only(['transfer_date', 'vehicle_id', 'operator_id', 'remarks']));

        return response()->json($transfer->fresh());
    }

    public function destroy(Transfer $transfer): JsonResponse
    {
        if ($transfer->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT transfers can be deleted.'], 422);
        }

        $transfer->transferDetails()->delete();
        $transfer->delete();

        return response()->json(null, 204);
    }

    public function submit(Request $request, Transfer $transfer): JsonResponse
    {
        if ($transfer->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT transfers can be submitted.'], 422);
        }

        $transfer->update(['status' => 'SUBMITTED']);

        return response()->json($transfer->fresh());
    }

    public function approve(Request $request, Transfer $transfer): JsonResponse
    {
        if ($transfer->status !== 'SUBMITTED') {
            return response()->json(['message' => 'Only SUBMITTED transfers can be approved.'], 422);
        }

        $transfer->update([
            'status' => 'APPROVED',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json($transfer->fresh());
    }

    public function post(Request $request, Transfer $transfer): JsonResponse
    {
        $this->transferService->post($transfer->load('transferDetails'), $request->user()->id);

        return response()->json($transfer->fresh());
    }

    public function cancel(Request $request, Transfer $transfer): JsonResponse
    {
        if ($transfer->status === 'POSTED') {
            return response()->json(['message' => 'Posted transfers cannot be cancelled.'], 422);
        }

        $transfer->update(['status' => 'CANCELLED']);

        return response()->json($transfer->fresh());
    }
}
