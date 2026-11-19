<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Dispatch;
use App\Models\DispatchDetail;
use App\Models\Product;
use App\Services\DispatchService;
use App\Services\NumberingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DispatchController extends Controller
{
    public function __construct(
        private readonly DispatchService $dispatchService,
        private readonly NumberingService $numberingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Dispatch::with(['customer:id,name,code', 'project:id,name,code'])
            ->when($request->search, fn ($q) => $q->where('dispatch_number', 'like', "%{$request->search}%"))
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->project_id, fn ($q) => $q->where('project_id', $request->project_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->from_date, fn ($q) => $q->where('dispatch_date', '>=', $request->from_date))
            ->when($request->to_date, fn ($q) => $q->where('dispatch_date', '<=', $request->to_date))
            ->orderBy('dispatch_date', 'desc')
            ->orderBy('id', 'desc');

        return response()->json($query->paginate($request->per_page ?? 25));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'project_id' => ['required', 'exists:projects,id'],
            'source_location_id' => ['required', 'exists:locations,id'],
            'dispatch_date' => ['required', 'date'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'operator_id' => ['nullable', 'exists:operators,id'],
            'remarks' => ['nullable', 'string'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.product_id' => ['required', 'exists:products,id'],
            'details.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'details.*.expected_return_date' => ['nullable', 'date'],
            'details.*.remarks' => ['nullable', 'string'],
        ]);

        $dispatch = DB::transaction(function () use ($validated, $request) {
            $dispatch = Dispatch::create([
                'dispatch_number' => $this->numberingService->next('DISPATCH'),
                'customer_id' => $validated['customer_id'],
                'project_id' => $validated['project_id'],
                'source_location_id' => $validated['source_location_id'],
                'dispatch_date' => $validated['dispatch_date'],
                'vehicle_id' => $validated['vehicle_id'] ?? null,
                'operator_id' => $validated['operator_id'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'status' => 'DRAFT',
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['details'] as $detailData) {
                $product = Product::findOrFail($detailData['product_id']);
                $dispatch->dispatchDetails()->create([
                    'product_id' => $product->id,
                    'quantity' => $detailData['quantity'],
                    'minimum_rental_days_snapshot' => $product->minimum_rental_days,
                    'minimum_rental_amount_snapshot' => $product->minimum_rental_amount,
                    'addon_amount_per_day_snapshot' => $product->addon_amount_per_day,
                    'unit_of_measure_snapshot' => $product->unit_of_measure,
                    'tax_rate_snapshot' => $product->defaultTaxRate?->percentage ?? 0,
                    'expected_return_date' => $detailData['expected_return_date'] ?? null,
                    'remarks' => $detailData['remarks'] ?? null,
                ]);
            }

            return $dispatch;
        });

        return response()->json($dispatch->load(['customer:id,name', 'project:id,name', 'dispatchDetails.product:id,name,code']), 201);
    }

    public function show(Dispatch $dispatch): JsonResponse
    {
        return response()->json($dispatch->load([
            'customer', 'project', 'sourceLocation',
            'vehicle', 'operator', 'dispatchDetails.product',
        ]));
    }

    public function update(Request $request, Dispatch $dispatch): JsonResponse
    {
        if ($dispatch->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT dispatches can be edited.'], 422);
        }

        $validated = $request->validate([
            'dispatch_date' => ['sometimes', 'date'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'operator_id' => ['nullable', 'exists:operators,id'],
            'remarks' => ['nullable', 'string'],
        ]);

        $dispatch->update($validated);

        return response()->json($dispatch->fresh()->load(['customer:id,name', 'project:id,name', 'dispatchDetails.product:id,name,code']));
    }

    public function destroy(Dispatch $dispatch): JsonResponse
    {
        if ($dispatch->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT dispatches can be deleted.'], 422);
        }

        $dispatch->dispatchDetails()->delete();
        $dispatch->delete();

        return response()->json(null, 204);
    }

    public function submit(Request $request, Dispatch $dispatch): JsonResponse
    {
        if ($dispatch->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT dispatches can be submitted.'], 422);
        }

        $dispatch->update(['status' => 'SUBMITTED']);

        return response()->json($dispatch->fresh());
    }

    public function approve(Request $request, Dispatch $dispatch): JsonResponse
    {
        if ($dispatch->status !== 'SUBMITTED') {
            return response()->json(['message' => 'Only SUBMITTED dispatches can be approved.'], 422);
        }

        $dispatch->update([
            'status' => 'APPROVED',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json($dispatch->fresh());
    }

    public function post(Request $request, Dispatch $dispatch): JsonResponse
    {
        $this->dispatchService->post($dispatch->load('dispatchDetails.product'), $request->user()->id);

        return response()->json($dispatch->fresh()->load(['dispatchDetails.product:id,name,code']));
    }

    public function cancel(Request $request, Dispatch $dispatch): JsonResponse
    {
        if ($dispatch->status === 'POSTED') {
            return response()->json(['message' => 'Posted dispatches cannot be directly cancelled. Use reversal workflow.'], 422);
        }

        $dispatch->update(['status' => 'CANCELLED']);

        return response()->json($dispatch->fresh());
    }
}
