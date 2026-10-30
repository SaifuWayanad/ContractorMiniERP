<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\Paginates;
use App\Http\Controllers\Api\Concerns\Scopes;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\Project;
use App\Models\Purchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    use Paginates, Scopes;

    public function index(Request $request): JsonResponse
    {
        $includeArchived = $this->boolQuery($request, 'includeArchived');

        $query = Project::with(['customer', 'location', 'assignedUser'])->orderByDesc('created_at');

        if (! $includeArchived) {
            $query->where('is_archived', false);
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(fn ($q) => $q->where('project_name', 'like', "%{$search}%")
                ->orWhere('project_address', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($c) => $c->where('customer_name', 'like', "%{$search}%")));
        }

        if ($request->filled('customerId')) {
            $query->where('customer_id', (int) $request->query('customerId'));
        }

        if ($request->filled('locationId')) {
            $query->where('location_id', (int) $request->query('locationId'));
        }

        return response()->json($this->paginate($query, $request, ProjectResource::class));
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json((new ProjectResource($project->load(['customer', 'location', 'assignedUser'])))->resolve());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $project = Project::create([
            'project_name' => $data['projectName'],
            'customer_id' => $data['customerId'],
            'location_id' => $data['locationId'] ?? null,
            'project_address' => $data['projectAddress'] ?? '',
            'start_date' => $data['startDate'] ?? null,
            'end_date' => $data['endDate'] ?? null,
            'budget' => $data['budget'] ?? null,
            'description' => $data['description'] ?? null,
            // Contractor users who create a project are assigned to it, as in the .NET controller.
            'assigned_user_id' => $data['assignedUserId'] ?? (! $request->user()->isAdmin() ? $request->user()->id : null),
        ]);

        return response()->json(['id' => $project->id], Response::HTTP_CREATED);
    }

    public function update(Request $request, Project $project): Response
    {
        $data = $request->validate($this->rules() + ['isArchived' => ['boolean']]);

        $project->update([
            'project_name' => $data['projectName'],
            'customer_id' => $data['customerId'],
            'location_id' => $data['locationId'] ?? null,
            'project_address' => $data['projectAddress'] ?? '',
            'start_date' => $data['startDate'] ?? null,
            'end_date' => $data['endDate'] ?? null,
            'budget' => $data['budget'] ?? null,
            'description' => $data['description'] ?? null,
            'is_archived' => (bool) ($data['isArchived'] ?? false),
            'assigned_user_id' => $data['assignedUserId'] ?? null,
        ]);

        return response()->noContent();
    }

    public function destroy(Project $project): Response
    {
        $project->update(['is_archived' => true]);

        return response()->noContent();
    }

    public function summary(Project $project): JsonResponse
    {
        $project->load('customer');

        $totalPurchases = (float) Purchase::where('project_id', $project->id)->where('is_archived', false)->sum('line_total');
        $totalExpenses = (float) Expense::where('project_id', $project->id)->where('is_archived', false)->sum('amount');
        $totalReceived = (float) CustomerPayment::where('project_id', $project->id)->where('is_voided', false)->sum('amount_received');

        $budget = $project->budget !== null ? (float) $project->budget : null;
        $totalCost = $totalPurchases + $totalExpenses;

        return response()->json([
            'projectId' => $project->id,
            'projectName' => $project->project_name,
            'customerName' => $project->customer?->customer_name ?? '',
            'budget' => $budget,
            'totalPurchases' => $totalPurchases,
            'totalExpenses' => $totalExpenses,
            'totalProjectCost' => $totalCost,
            'totalReceived' => $totalReceived,
            'outstandingAgainstBudget' => $budget !== null ? $budget - $totalReceived : null,
            'remainingBudget' => $budget !== null ? $budget - $totalCost : null,
            'isOverBudget' => $budget !== null && $totalCost > $budget,
        ]);
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'projectName' => ['required', 'string', 'max:200'],
            'customerId' => ['required', 'integer', 'exists:customers,id'],
            'locationId' => ['nullable', 'integer', 'exists:locations,id'],
            'projectAddress' => ['nullable', 'string', 'max:500'],
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date'],
            'budget' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            'assignedUserId' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
