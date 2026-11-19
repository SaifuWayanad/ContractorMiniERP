<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Vehicle::query()
            ->when($request->search, fn ($q) => $q->where('registration_number', 'like', "%{$request->search}%")->orWhere('make_model', 'like', "%{$request->search}%"))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('registration_number');

        return response()->json($query->paginate($request->per_page ?? 25));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'registration_number' => ['required', 'string', 'max:50', 'unique:vehicles'],
            'vehicle_type' => ['nullable', 'string', 'max:100'],
            'make_model' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'string', 'max:100'],
            'status' => ['in:ACTIVE,INACTIVE,MAINTENANCE'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        return response()->json(Vehicle::create($validated), 201);
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        return response()->json($vehicle);
    }

    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'registration_number' => ['sometimes', 'string', 'max:50', 'unique:vehicles,registration_number,' . $vehicle->id],
            'vehicle_type' => ['nullable', 'string', 'max:100'],
            'make_model' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'string', 'max:100'],
            'status' => ['in:ACTIVE,INACTIVE,MAINTENANCE'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $vehicle->update($validated);

        return response()->json($vehicle->fresh());
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $vehicle->update(['is_active' => false]);

        return response()->json($vehicle->fresh());
    }
}
