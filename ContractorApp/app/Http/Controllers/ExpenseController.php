<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Project;
use App\Models\ProjectPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Expense::class);

        $search  = $request->string('search')->trim()->value();
        $filters = [
            'search'     => $search,
            'client_id'  => $request->integer('client_id') ?: null,
            'project_id' => $request->integer('project_id') ?: null,
            'category'   => $request->string('category')->trim()->value() ?: null,
        ];

        $expenses = Expense::query()
            ->with('project:id,name')
            ->when($filters['client_id'], fn (Builder $q, int $cid) => $q->whereHas('project', fn (Builder $p) => $p->where('client_id', $cid)))
            ->when($filters['project_id'], fn (Builder $q, int $pid) => $q->where('project_id', $pid))
            ->when($filters['category'], fn (Builder $q, string $cat) => $q->where('category', $cat))
            ->when($search !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($search) {
                $like = '%'.$search.'%';
                $q->where('heading', 'like', $like)
                    ->orWhere('category', 'like', $like);
            }))
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate($this->perPage())
            ->withQueryString();

        $clients  = Client::orderBy('name')->pluck('name', 'id');
        $projects = $filters['client_id']
            ? Project::where('client_id', $filters['client_id'])->orderBy('name')->pluck('name', 'id')
            : Project::orderBy('name')->pluck('name', 'id');

        return view('expenses.index', [
            'expenses'   => $expenses,
            'filters'    => $filters,
            'clients'    => $clients,
            'projects'   => $projects,
            'categories' => Expense::select('category')->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Expense::class);

        $expense = new Expense(['expense_date' => today()->toDateString()]);
        $expense->project_id = $request->integer('project_id') ?: null;

        return view('expenses.create', $this->formData($expense));
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $this->authorize('create', Expense::class);

        $expense = Expense::create($request->validated());

        $redirect = $request->input('from') === 'project'
            ? to_route('projects.show', $expense->project_id)
            : to_route('expenses.index');

        return $redirect->with('toast', "Expense \"{$expense->heading}\" recorded.");
    }

    public function edit(Expense $expense): View
    {
        $this->authorize('update', $expense);

        return view('expenses.edit', $this->formData($expense));
    }

    public function update(ExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);

        $expense->update($request->validated());

        $redirect = $request->input('from') === 'project'
            ? to_route('projects.show', $expense->project_id)
            : to_route('expenses.index');

        return $redirect->with('toast', "Expense \"{$expense->heading}\" updated.");
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        $projectId = $expense->project_id;
        $label     = $expense->heading;
        $expense->delete();

        return to_route('projects.show', $projectId)->with('toast', "Expense \"{$label}\" deleted.");
    }

    /** @return array<string, mixed> */
    private function formData(Expense $expense): array
    {
        return [
            'expense'        => $expense,
            'projects'       => Project::query()
                ->where(fn (Builder $q) => $q->open()->orWhere('id', $expense->project_id))
                ->orderBy('name')->pluck('name', 'id'),
            'categories'     => Expense::select('category')->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'paymentMethods' => $this->paymentMethods(),
        ];
    }

    private function paymentMethods(): Collection
    {
        $defaults = collect(['Bank Transfer', 'Card', 'Cash', 'Cheque', 'UPI']);
        $fromExpenses  = Expense::whereNotNull('payment_method')->distinct()->pluck('payment_method');
        $fromPayments  = ProjectPayment::whereNotNull('payment_method')->distinct()->pluck('payment_method');

        return $defaults->merge($fromExpenses)->merge($fromPayments)->unique()->sort()->values();
    }
}
