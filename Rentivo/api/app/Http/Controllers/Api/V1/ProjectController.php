<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Project::with(['customer:id,name,code'])
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_dir ?? 'desc');

        return response()->json($query->paginate($request->per_page ?? 25));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'code' => ['required', 'string', 'max:50', 'unique:projects'],
            'name' => ['required', 'string', 'max:255'],
            'site_address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'expected_end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'default_location_id' => ['nullable', 'exists:locations,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['status'] = 'DRAFT';
        $project = Project::create($validated);

        return response()->json($project->load('customer:id,name'), 201);
    }

    public function show(Project $project): JsonResponse
    {
        $project->load(['customer', 'defaultLocation']);

        $project->equipment_balances = \App\Models\InventoryBalance::with('product:id,name,code,unit_of_measure')
            ->where('location_type', 'PROJECT')
            ->where('location_id', $project->id)
            ->where('quantity_on_hand', '>', 0)
            ->get();

        return response()->json($project);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        if (in_array($project->status, ['CLOSED', 'CANCELLED'])) {
            return response()->json(['message' => 'Cannot edit a closed or cancelled project.'], 422);
        }

        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:50', 'unique:projects,code,' . $project->id],
            'name' => ['sometimes', 'string', 'max:255'],
            'site_address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'start_date' => ['sometimes', 'date'],
            'expected_end_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'in:DRAFT,ACTIVE,CLOSURE_IN_PROGRESS,CANCELLED'],
            'default_location_id' => ['nullable', 'exists:locations,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['updated_by'] = $request->user()->id;
        $project->update($validated);

        return response()->json($project->fresh()->load('customer:id,name'));
    }

    public function destroy(Project $project): JsonResponse
    {
        if ($project->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT projects can be deleted.'], 422);
        }

        $project->delete();

        return response()->json(null, 204);
    }
}
