<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Location::query()
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->location_type, fn ($q) => $q->where('location_type', $request->location_type))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name');

        return response()->json($query->paginate($request->per_page ?? 25));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:locations'],
            'name' => ['required', 'string', 'max:255'],
            'location_type' => ['required', 'in:WAREHOUSE,STORE,OTHER'],
            'address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $validated['created_by'] = $request->user()->id;

        return response()->json(Location::create($validated), 201);
    }

    public function show(Location $location): JsonResponse
    {
        return response()->json($location);
    }

    public function update(Request $request, Location $location): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:50', 'unique:locations,code,' . $location->id],
            'name' => ['sometimes', 'string', 'max:255'],
            'location_type' => ['sometimes', 'in:WAREHOUSE,STORE,OTHER'],
            'address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $validated['updated_by'] = $request->user()->id;
        $location->update($validated);

        return response()->json($location->fresh());
    }

    public function destroy(Location $location): JsonResponse
    {
        $location->delete();

        return response()->json(null, 204);
    }
}
