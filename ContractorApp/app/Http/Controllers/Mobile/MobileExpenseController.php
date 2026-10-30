<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $projectId = $request->integer('project_id') ?: null;

        $expenses = Expense::query()
            ->when($projectId, fn ($q, $id) => $q->where('project_id', $id))
            ->orderByDesc('expense_date')
            ->latest()
            ->get()
            ->map(fn (Expense $e) => [
                'id'               => $e->id,
                'project_id'       => $e->project_id,
                'heading'          => $e->heading,
                'category'         => $e->category,
                'expense_date'     => $e->expense_date?->toDateString(),
                'amount'           => (float) $e->amount,
                'payment_method'   => $e->payment_method,
                'reference_number' => $e->reference_number,
                'notes'            => $e->notes,
            ]);

        return response()->json($expenses);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id'       => ['required', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'heading'          => ['required', 'string', 'max:200'],
            'category'         => ['nullable', 'string', 'max:200'],
            'expense_date'     => ['required', 'date'],
            'amount'           => ['required', 'numeric', 'min:0'],
            'payment_method'   => ['nullable', 'string', 'max:100'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:5000'],
        ]);

        $expense = Expense::create($data);

        return response()->json(['id' => $expense->id, 'message' => 'Expense recorded.'], 201);
    }

    public function update(Request $request, Expense $expense): JsonResponse
    {
        $data = $request->validate([
            'heading'          => ['sometimes', 'string', 'max:200'],
            'category'         => ['nullable', 'string', 'max:200'],
            'expense_date'     => ['sometimes', 'date'],
            'amount'           => ['sometimes', 'numeric', 'min:0'],
            'payment_method'   => ['nullable', 'string', 'max:100'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:5000'],
        ]);

        $expense->update($data);

        return response()->json(['message' => 'Expense updated.']);
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $expense->delete();

        return response()->json(['message' => 'Expense deleted.']);
    }
}
