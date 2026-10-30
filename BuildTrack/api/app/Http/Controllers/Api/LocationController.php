<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\Paginates;
use App\Http\Controllers\Api\Concerns\Scopes;
use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocationController extends Controller
{
    use Paginates, Scopes;

    public function index(Request $request): JsonResponse
    {
        $query = Location::orderBy('location_name');

        if (! $this->boolQuery($request, 'includeArchived')) {
            $query->where('is_archived', false);
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(fn ($q) => $q->where('location_name', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%"));
        }

        return response()->json($this->paginate($query, $request, LocationResource::class));
    }

    public function show(Location $location): JsonResponse
    {
        return response()->json((new LocationResource($location))->resolve());
    }

    public function store(Request $request): JsonResponse
    {
        $location = Location::create($this->validated($request));

        return response()->json((new LocationResource($location))->resolve(), Response::HTTP_CREATED);
    }

    public function update(Request $request, Location $location): Response
    {
        $location->update($this->validated($request, withArchived: true));

        return response()->noContent();
    }

    public function destroy(Location $location): Response|JsonResponse
    {
        $inUse = Customer::where('location_id', $location->id)->where('is_archived', false)->exists()
            || Project::where('location_id', $location->id)->where('is_archived', false)->exists();

        if ($inUse) {
            return response()->json(['message' => 'Cannot delete location currently in use by customers or projects.'], 409);
        }

        $location->update(['is_archived' => true]);

        return response()->noContent();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, bool $withArchived = false): array
    {
        $rules = [
            'locationName' => ['required', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
        if ($withArchived) {
            $rules['isArchived'] = ['boolean'];
        }

        $data = $request->validate($rules);

        return array_filter([
            'location_name' => $data['locationName'],
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'notes' => $data['notes'] ?? null,
            'is_archived' => $withArchived ? (bool) ($data['isArchived'] ?? false) : null,
        ], fn ($v) => $v !== null);
    }
}
