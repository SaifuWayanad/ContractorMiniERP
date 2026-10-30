<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\Paginates;
use App\Http\Controllers\Api\Concerns\Scopes;
use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\ProjectResource;
use App\Models\Customer;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends Controller
{
    use Paginates, Scopes;

    public function index(Request $request): JsonResponse
    {
        $query = Customer::with('location')->orderBy('customer_name');

        if (! $this->boolQuery($request, 'includeArchived')) {
            $query->where('is_archived', false);
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(fn ($q) => $q->where('customer_name', 'like', "%{$search}%")
                ->orWhere('phone_number', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%"));
        }

        return response()->json($this->paginate($query, $request, CustomerResource::class));
    }

    public function show(Customer $customer): JsonResponse
    {
        return response()->json((new CustomerResource($customer->load('location')))->resolve());
    }

    public function store(Request $request): JsonResponse
    {
        $customer = Customer::create($this->validated($request));

        return response()->json((new CustomerResource($customer->load('location')))->resolve(), Response::HTTP_CREATED);
    }

    public function update(Request $request, Customer $customer): Response
    {
        $customer->update($this->validated($request, withArchived: true));

        return response()->noContent();
    }

    public function destroy(Customer $customer): Response|JsonResponse
    {
        if (Project::where('customer_id', $customer->id)->where('is_archived', false)->exists()) {
            return response()->json(['message' => 'Cannot delete customer with active projects.'], 409);
        }

        $customer->update(['is_archived' => true]);

        return response()->noContent();
    }

    public function projects(Request $request, Customer $customer): JsonResponse
    {
        $query = Project::with(['customer', 'location', 'assignedUser'])
            ->where('customer_id', $customer->id)
            ->orderByDesc('created_at');

        if (! $this->boolQuery($request, 'includeArchived')) {
            $query->where('is_archived', false);
        }

        return response()->json(ProjectResource::collection($query->get())->resolve($request));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, bool $withArchived = false): array
    {
        $rules = [
            'customerName' => ['required', 'string', 'max:200'],
            'phoneNumber' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'locationId' => ['nullable', 'integer', 'exists:locations,id'],
            'notes' => ['nullable', 'string'],
        ];
        if ($withArchived) {
            $rules['isArchived'] = ['boolean'];
        }

        $data = $request->validate($rules);

        return array_filter([
            'customer_name' => $data['customerName'],
            'phone_number' => $data['phoneNumber'] ?? '',
            'address' => $data['address'] ?? '',
            'location_id' => $data['locationId'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_archived' => $withArchived ? (bool) ($data['isArchived'] ?? false) : null,
        ], fn ($v) => $v !== null);
    }
}
