<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\Paginates;
use App\Http\Controllers\Api\Concerns\Scopes;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExpenseController extends Controller
{
    use Paginates, Scopes;

    public function index(Request $request): JsonResponse
    {
        $query = Expense::with(['project.customer'])->orderByDesc('expense_date');

        if (! $this->boolQuery($request, 'includeArchived')) {
            $query->where('is_archived', false);
        }

        if ($request->filled('projectId')) {
            $query->where('project_id', (int) $request->query('projectId'));
        }

        if ($request->filled('customerId')) {
            $query->whereHas('project', fn ($p) => $p->where('customer_id', (int) $request->query('customerId')));
        }

        if ($heading = trim((string) $request->query('expenseHeading', ''))) {
            $query->where('expense_heading', 'like', "%{$heading}%");
        }

        if ($request->filled('startDate')) {
            $query->where('expense_date', '>=', $request->query('startDate'));
        }

        if ($request->filled('endDate')) {
            $query->where('expense_date', '<=', $request->query('endDate'));
        }

        return response()->json($this->paginate($query, $request, ExpenseResource::class));
    }

    public function show(Expense $expense): JsonResponse
    {
        return response()->json((new ExpenseResource($expense->load(['project.customer'])))->resolve());
    }

    public function store(Request $request): JsonResponse
    {
        $expense = Expense::create($this->attributes($request));

        return response()->json(['id' => $expense->id], Response::HTTP_CREATED);
    }

    public function update(Request $request, Expense $expense): Response
    {
        $expense->update($this->attributes($request, withArchived: true));

        return response()->noContent();
    }

    public function destroy(Expense $expense): Response
    {
        $expense->update(['is_archived' => true]);

        return response()->noContent();
    }

    /** @return array<string, mixed> */
    private function attributes(Request $request, bool $withArchived = false): array
    {
        $rules = [
            'projectId' => ['required', 'integer', 'exists:projects,id'],
            'expenseHeading' => ['required', 'string', 'max:200'],
            'expenseDescription' => ['nullable', 'string', 'max:500'],
            'expenseDate' => ['required', 'date'],
            'amount' => ['required', 'numeric'],
            'paymentMethod' => ['nullable', 'string', 'max:100'],
            'referenceNumber' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
        if ($withArchived) {
            $rules['isArchived'] = ['boolean'];
        }

        $data = $request->validate($rules);

        $attributes = [
            'project_id' => $data['projectId'],
            'expense_heading' => $data['expenseHeading'],
            'expense_description' => $data['expenseDescription'] ?? '',
            'expense_date' => $data['expenseDate'],
            'amount' => $data['amount'],
            'payment_method' => $data['paymentMethod'] ?? '',
            'reference_number' => $data['referenceNumber'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
        if ($withArchived) {
            $attributes['is_archived'] = (bool) ($data['isArchived'] ?? false);
        }

        return $attributes;
    }
}
