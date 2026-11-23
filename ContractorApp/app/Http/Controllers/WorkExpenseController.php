<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Work;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkExpenseController extends Controller
{
    public function store(Request $request, Work $work): RedirectResponse
    {
        $this->authorize('update', $work);

        $validated = $request->validate([
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'expense_date'   => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $expense = new Expense($validated);
        $expense->heading         = 'Work Expense';
        $expense->category        = 'Work';
        $expense->work_id         = $work->id;
        $expense->project_id      = $work->project_id;
        $expense->is_work_related = true;
        $expense->save();

        return back()->with('toast', 'Expense added.');
    }

    public function destroy(Work $work, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $work);

        $expense->delete();

        return back()->with('toast', 'Expense deleted.');
    }
}
