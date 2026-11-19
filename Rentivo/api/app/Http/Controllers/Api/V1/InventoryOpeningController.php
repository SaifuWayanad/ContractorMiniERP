<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryOpening;
use App\Services\InventoryService;
use App\Services\NumberingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryOpeningController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly NumberingService $numberingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = InventoryOpening::with(['location:id,name,code', 'product:id,name,code,unit_of_measure'])
            ->when($request->location_id, fn ($q) => $q->where('location_id', $request->location_id))
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('opening_date', 'desc');

        return response()->json($query->paginate($request->per_page ?? 25));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'opening_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);

        $validated['opening_number'] = $this->numberingService->next('OPENING');
        $validated['status'] = 'DRAFT';
        $validated['created_by'] = $request->user()->id;

        $opening = InventoryOpening::create($validated);

        return response()->json($opening->load(['location:id,name', 'product:id,name,code']), 201);
    }

    public function show(InventoryOpening $opening): JsonResponse
    {
        return response()->json($opening->load(['location', 'product', 'createdBy:id,name']));
    }

    public function update(Request $request, InventoryOpening $opening): JsonResponse
    {
        if ($opening->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT openings can be edited.'], 422);
        }

        $validated = $request->validate([
            'location_id' => ['sometimes', 'exists:locations,id'],
            'product_id' => ['sometimes', 'exists:products,id'],
            'quantity' => ['sometimes', 'numeric', 'min:0.001'],
            'opening_date' => ['sometimes', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);

        $opening->update($validated);

        return response()->json($opening->fresh()->load(['location:id,name', 'product:id,name,code']));
    }

    public function destroy(InventoryOpening $opening): JsonResponse
    {
        if ($opening->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT openings can be deleted.'], 422);
        }

        $opening->delete();

        return response()->json(null, 204);
    }

    public function post(Request $request, InventoryOpening $opening): JsonResponse
    {
        if ($opening->status !== 'DRAFT') {
            return response()->json(['message' => 'Opening is already posted or cancelled.'], 422);
        }

        DB::transaction(function () use ($opening, $request) {
            $existing = InventoryOpening::where('product_id', $opening->product_id)
                ->where('location_id', $opening->location_id)
                ->where('status', 'POSTED')
                ->exists();

            if ($existing) {
                throw ValidationException::withMessages([
                    'product_id' => ['An opening already exists for this product at this location.'],
                ]);
            }

            $this->inventoryService->incrementBalance(
                $opening->product_id, 'WAREHOUSE', $opening->location_id, $opening->quantity
            );

            $this->inventoryService->createMovement([
                'product_id' => $opening->product_id,
                'movement_type' => 'OPENING',
                'source_type' => InventoryOpening::class,
                'source_id' => $opening->id,
                'to_location_type' => 'WAREHOUSE',
                'to_location_id' => $opening->location_id,
                'quantity' => $opening->quantity,
                'movement_date' => $opening->opening_date,
                'status' => 'POSTED',
                'created_by' => $request->user()->id,
                'posted_by' => $request->user()->id,
            ]);

            $opening->update([
                'status' => 'POSTED',
                'posted_by' => $request->user()->id,
                'posted_at' => now(),
            ]);
        });

        return response()->json($opening->fresh()->load(['location:id,name', 'product:id,name,code']));
    }

    public function cancel(Request $request, InventoryOpening $opening): JsonResponse
    {
        if ($opening->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT openings can be cancelled.'], 422);
        }

        $opening->update(['status' => 'CANCELLED']);

        return response()->json($opening->fresh());
    }
}
