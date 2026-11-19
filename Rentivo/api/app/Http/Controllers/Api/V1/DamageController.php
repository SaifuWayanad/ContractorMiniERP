<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Damage;
use App\Services\InventoryService;
use App\Services\NumberingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DamageController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly NumberingService $numberingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Damage::with(['customer:id,name', 'project:id,name'])
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('damage_date', 'desc');

        return response()->json($query->paginate($request->per_page ?? 25));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'project_id' => ['required', 'exists:projects,id'],
            'damage_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.source_dispatch_detail_id' => ['required', 'exists:dispatch_details,id'],
            'details.*.product_id' => ['required', 'exists:products,id'],
            'details.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'details.*.damage_description' => ['nullable', 'string'],
            'details.*.estimated_repair_cost' => ['nullable', 'numeric', 'min:0'],
            'details.*.disposition' => ['required', 'in:REPAIR,WRITE_OFF,RETURN_TO_AVAILABLE'],
        ]);

        $damage = DB::transaction(function () use ($validated, $request) {
            $damage = Damage::create([
                'damage_number' => $this->numberingService->next('DAMAGE'),
                'customer_id' => $validated['customer_id'],
                'project_id' => $validated['project_id'],
                'damage_date' => $validated['damage_date'],
                'remarks' => $validated['remarks'] ?? null,
                'status' => 'DRAFT',
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['details'] as $detail) {
                $damage->damageDetails()->create($detail);
            }

            return $damage;
        });

        return response()->json($damage->load(['customer:id,name', 'project:id,name', 'damageDetails.product:id,name,code']), 201);
    }

    public function show(Damage $damage): JsonResponse
    {
        return response()->json($damage->load(['customer', 'project', 'damageDetails.product']));
    }

    public function update(Request $request, Damage $damage): JsonResponse
    {
        if ($damage->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT damages can be edited.'], 422);
        }

        $damage->update($request->only(['damage_date', 'remarks']));

        return response()->json($damage->fresh());
    }

    public function destroy(Damage $damage): JsonResponse
    {
        if ($damage->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT damages can be deleted.'], 422);
        }

        $damage->damageDetails()->delete();
        $damage->delete();

        return response()->json(null, 204);
    }

    public function submit(Request $request, Damage $damage): JsonResponse
    {
        $damage->update(['status' => 'SUBMITTED']);

        return response()->json($damage->fresh());
    }

    public function approve(Request $request, Damage $damage): JsonResponse
    {
        $damage->update(['status' => 'APPROVED', 'approved_by' => $request->user()->id, 'approved_at' => now()]);

        return response()->json($damage->fresh());
    }

    public function post(Request $request, Damage $damage): JsonResponse
    {
        if ($damage->status !== 'APPROVED') {
            return response()->json(['message' => 'Only APPROVED damages can be posted.'], 422);
        }

        DB::transaction(function () use ($damage, $request) {
            foreach ($damage->damageDetails as $detail) {
                // Decrement project inventory - damaged items move to quarantine (removed from available)
                $this->inventoryService->decrementBalance(
                    $detail->product_id, 'PROJECT', $damage->project_id, $detail->quantity
                );

                $this->inventoryService->createMovement([
                    'product_id' => $detail->product_id,
                    'movement_type' => $detail->disposition === 'WRITE_OFF' ? 'WRITE_OFF' : 'ADJUSTMENT',
                    'source_type' => Damage::class,
                    'source_id' => $damage->id,
                    'source_detail_id' => $detail->id,
                    'from_location_type' => 'PROJECT',
                    'from_location_id' => $damage->project_id,
                    'quantity' => $detail->quantity,
                    'movement_date' => $damage->damage_date,
                    'status' => 'POSTED',
                    'created_by' => $request->user()->id,
                    'posted_by' => $request->user()->id,
                ]);
            }

            $damage->update(['status' => 'POSTED', 'posted_by' => $request->user()->id, 'posted_at' => now()]);
        });

        return response()->json($damage->fresh());
    }
}
