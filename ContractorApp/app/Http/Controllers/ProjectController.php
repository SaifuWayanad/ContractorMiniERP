<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Http\Requests\ProjectRequest;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Product;
use App\Models\Project;
use App\Models\Purchase;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProjectController extends Controller
{
    private const SORTS = ['newest', 'oldest', 'name', 'start', 'end', 'budget'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Project::class);

        $sort = $request->string('sort')->value();
        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'client_id' => $request->integer('client_id') ?: null,
            'status' => ProjectStatus::tryFrom($request->string('status')->value())?->value,
            'from' => $this->dateInput($request, 'from'),
            'to' => $this->dateInput($request, 'to'),
            'sort' => in_array($sort, self::SORTS, true) ? $sort : 'newest',
        ];

        $query = Project::query()
            ->with(['client:id,name', 'primaryLocation'])
            ->when($filters['search'] !== '', fn (Builder $query) => $query->search($filters['search']))
            ->when($filters['client_id'], fn (Builder $query, int $clientId) => $query->where('client_id', $clientId))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['from'], fn (Builder $query, string $from) => $query->whereDate('start_date', '>=', $from))
            ->when($filters['to'], fn (Builder $query, string $to) => $query->whereDate('start_date', '<=', $to));

        match ($filters['sort']) {
            'oldest' => $query->oldest(),
            'name' => $query->orderBy('name'),
            'start' => $query->orderByRaw('start_date is null')->orderByDesc('start_date'),
            'end' => $query->orderByRaw('expected_end_date is null')->orderBy('expected_end_date'),
            'budget' => $query->orderByDesc('budget'),
            default => $query->latest(),
        };

        return view('projects.index', [
            'projects' => $query->orderByDesc('id')->paginate($this->perPage())->withQueryString(),
            'filters' => $filters,
            'hasFilters' => $filters['search'] !== '' || $filters['client_id'] || $filters['status'] || $filters['from'] || $filters['to'],
            'clients' => Client::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Project::class);

        $project = new Project([
            'status' => ProjectStatus::Planning,
            'start_date' => today(),
            'client_id' => $request->integer('client_id') ?: null,
        ]);

        return view('projects.create', $this->formData($project));
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $project = DB::transaction(function () use ($request): Project {
            $data = $request->validated();
            $data['project_number'] = filled($data['project_number'] ?? null) ? $data['project_number'] : $this->nextProjectNumber();
            $data['status'] ??= \App\Enums\ProjectStatus::Planning->value;

            return Project::create($data);
        });

        return to_route('projects.show', $project)->with('toast', "Project \"{$project->name}\" created.");
    }

    public function show(Project $project): View
    {
        $this->authorize('view', $project);

        $project->load(['client', 'locations' => fn ($query) => $query->orderBy('id')]);

        $purchases = Purchase::where(function (Builder $q) use ($project): void {
            $q->where('project_id', $project->id)
              ->orWhereHas('items', fn (Builder $q2) => $q2->where('project_id', $project->id));
        })->with(['product:id,name,unit', 'items.product:id,name,unit'])
          ->orderByDesc('purchase_date')->orderByDesc('id')->get();
        $expenses  = $project->expenses()->orderByDesc('expense_date')->get();
        $payments  = $project->payments()->orderByDesc('payment_date')->get();
        $works     = $project->works()->with('assignee:id,name')->orderByRaw('due_date is null')->orderBy('due_date')->get();

        $financials = [
            'purchases_total' => (float) $purchases->sum(function (Purchase $p) use ($project): float {
                if ($p->items->isEmpty()) {
                    return (float) $p->line_total;
                }
                return (float) $p->items->where('project_id', $project->id)->sum('line_total');
            }),
            'works_total'     => (float) $works->sum('amount'),
            'expenses_total'  => (float) $expenses->sum('amount') + (float) $works->sum('amount'),
            'payments_total'  => (float) $payments->sum('amount_received'),
            'budget'          => (float) ($project->budget ?? 0),
        ];
        $financials['total_cost']   = $financials['purchases_total'] + $financials['expenses_total'];
        $financials['outstanding']  = $financials['budget'] - $financials['payments_total'];
        $financials['profit']       = $financials['payments_total'] - $financials['total_cost'];

        return view('projects.show', [
            'project'        => $project,
            'works'          => $works,
            'meetings'       => $project->meetings()->with('createdBy:id,name')->orderByDesc('meeting_at')->get(),
            'purchases'      => $purchases,
            'expenses'       => $expenses,
            'payments'       => $payments,
            'financials'     => $financials,
            'activity' => ActivityLog::query()
                ->where('contractor_id', $project->contractor_id)
                ->where(function (Builder $query) use ($project): void {
                    $query->where(fn (Builder $q) => $q->where('entity_type', 'project')->where('entity_id', $project->id))
                        ->orWhere(fn (Builder $q) => $q->where('entity_type', 'project_location')->whereIn('entity_id', $project->locations()->withTrashed()->select('id')))
                        ->orWhere(fn (Builder $q) => $q->where('entity_type', 'work')->whereIn('entity_id', $project->works()->withTrashed()->select('id')))
                        ->orWhere(fn (Builder $q) => $q->where('entity_type', 'purchase')->whereIn('entity_id', $project->purchases()->withTrashed()->select('id')))
                        ->orWhere(fn (Builder $q) => $q->where('entity_type', 'expense')->whereIn('entity_id', $project->expenses()->withTrashed()->select('id')))
                        ->orWhere(fn (Builder $q) => $q->where('entity_type', 'project_payment')->whereIn('entity_id', $project->payments()->withTrashed()->select('id')));
                })
                ->with('user:id,name')
                ->latest()
                ->orderByDesc('id')
                ->limit(20)
                ->get(),
        ]);
    }

    public function addPurchases(Project $project): View
    {
        $this->authorize('view', $project);
        $this->authorize('create', Purchase::class);

        $products = Product::orderBy('name')
            ->get(['id', 'name', 'unit', 'default_rate'])
            ->map(fn ($p) => [
                'id'   => $p->id,
                'name' => $p->name,
                'unit' => $p->unit ?? '',
                'rate' => (float) ($p->default_rate ?? 0),
            ]);

        return view('projects.purchases-add', [
            'project'  => $project,
            'products' => $products,
        ]);
    }

    public function storePurchases(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('view', $project);
        $this->authorize('create', Purchase::class);

        $contractorId = $request->user()->contractor_id;

        $validated = $request->validate([
            'purchase_date'          => ['required', 'date'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['nullable', 'integer', Rule::exists('products', 'id')->where('contractor_id', $contractorId)->whereNull('deleted_at')],
            'items.*.quantity'       => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_amount'    => ['nullable', 'numeric', 'min:0'],
            'items.*.line_total'     => ['nullable', 'numeric', 'min:0'],
        ]);

        $validItems = [];
        $batchTotal = 0.0;

        foreach ($validated['items'] as $item) {
            $qty       = isset($item['quantity']) && $item['quantity'] > 0 ? (float) $item['quantity'] : null;
            $rate      = isset($item['unit_amount']) && $item['unit_amount'] > 0 ? (float) $item['unit_amount'] : null;
            $lineTotal = isset($item['line_total']) && $item['line_total'] > 0 ? (float) $item['line_total'] : null;

            if (!$lineTotal && !($qty && $rate)) {
                continue;
            }

            $resolved = $lineTotal ?? round($qty * $rate, 2);
            $batchTotal += $resolved;
            $validItems[] = [
                'product_id'  => $item['product_id'] ?? null,
                'quantity'    => $qty,
                'unit_amount' => $rate,
                'line_total'  => $resolved,
            ];
        }

        if (empty($validItems)) {
            return back()->withInput()->with('toast', 'No purchases saved — please add at least one item with a total.');
        }

        $purchase = Purchase::create([
            'project_id'    => $project->id,
            'purchase_date' => $validated['purchase_date'],
            'line_total'    => $batchTotal,
            'total_amount'  => $batchTotal,
        ]);

        foreach ($validItems as $item) {
            $purchase->items()->create([
                'project_id'  => $project->id,
                'product_id'  => $item['product_id'],
                'quantity'    => $item['quantity'],
                'unit_amount' => $item['unit_amount'],
                'line_total'  => $item['line_total'],
            ]);
        }

        $count = count($validItems);
        return to_route('projects.show', $project)
            ->with('toast', "{$count} item" . ($count !== 1 ? 's' : '') . ' recorded.');
    }

    public function editPurchase(Project $project, Purchase $purchase): View
    {
        $this->authorize('view', $project);
        $this->authorize('update', $purchase);

        $purchase->load(['items.product:id,name,unit', 'items.project:id,name,project_number', 'product:id,name,unit']);

        $products = Product::orderBy('name')
            ->get(['id', 'name', 'unit', 'default_rate'])
            ->map(fn ($p) => [
                'id'   => $p->id,
                'name' => $p->name,
                'unit' => $p->unit ?? '',
                'rate' => (float) ($p->default_rate ?? 0),
            ]);

        $isBatch        = $purchase->items->isNotEmpty();
        $isMultiProject = $purchase->project_id === null;

        $existingItems = $isBatch
            ? $purchase->items->map(fn ($i) => [
                'project_id'    => $i->project_id,
                'project_label' => $i->project ? ($i->project->name . ($i->project->project_number ? ' (' . $i->project->project_number . ')' : '')) : null,
                'product_id'    => $i->product_id,
                'product_label' => $i->product ? ($i->product->name . ($i->product->unit ? ' (' . $i->product->unit . ')' : '')) : null,
                'quantity'      => $i->quantity !== null ? rtrim(rtrim(number_format((float) $i->quantity, 4, '.', ''), '0'), '.') : null,
                'unit_amount'   => $i->unit_amount !== null ? number_format((float) $i->unit_amount, 2, '.', '') : null,
                'line_total'    => number_format((float) $i->line_total, 2, '.', ''),
            ])
            : collect([[
                'project_id'    => $project->id,
                'project_label' => $project->name . ' (' . $project->project_number . ')',
                'product_id'    => $purchase->product_id,
                'product_label' => $purchase->product ? ($purchase->product->name . ($purchase->product->unit ? ' (' . $purchase->product->unit . ')' : '')) : null,
                'quantity'      => $purchase->quantity !== null ? rtrim(rtrim(number_format((float) $purchase->quantity, 4, '.', ''), '0'), '.') : null,
                'unit_amount'   => $purchase->unit_amount !== null ? number_format((float) $purchase->unit_amount, 2, '.', '') : null,
                'line_total'    => number_format((float) $purchase->line_total, 2, '.', ''),
            ]]);

        $projectsJson = $isMultiProject
            ? Project::orderBy('name')->get(['id', 'name', 'project_number'])->map(fn ($p) => [
                'id'     => $p->id,
                'name'   => $p->name,
                'number' => $p->project_number,
            ])
            : collect();

        return view('projects.purchases-edit', [
            'project'        => $project,
            'purchase'       => $purchase,
            'products'       => $products,
            'existingItems'  => $existingItems,
            'isMultiProject' => $isMultiProject,
            'projectsJson'   => $projectsJson,
        ]);
    }

    public function updatePurchase(Request $request, Project $project, Purchase $purchase): RedirectResponse
    {
        $this->authorize('view', $project);
        $this->authorize('update', $purchase);

        $contractorId = $request->user()->contractor_id;

        $validated = $request->validate([
            'purchase_date'          => ['required', 'date'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.project_id'     => ['nullable', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'items.*.product_id'     => ['nullable', 'integer', Rule::exists('products', 'id')->where('contractor_id', $contractorId)->whereNull('deleted_at')],
            'items.*.quantity'       => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_amount'    => ['nullable', 'numeric', 'min:0'],
            'items.*.line_total'     => ['nullable', 'numeric', 'min:0'],
        ]);

        $validItems = [];
        $batchTotal = 0.0;

        foreach ($validated['items'] as $item) {
            $qty       = isset($item['quantity']) && $item['quantity'] > 0 ? (float) $item['quantity'] : null;
            $rate      = isset($item['unit_amount']) && $item['unit_amount'] > 0 ? (float) $item['unit_amount'] : null;
            $lineTotal = isset($item['line_total']) && $item['line_total'] > 0 ? (float) $item['line_total'] : null;

            if (!$lineTotal && !($qty && $rate)) continue;

            $resolved    = $lineTotal ?? round($qty * $rate, 2);
            $batchTotal += $resolved;
            $validItems[] = [
                'project_id'  => isset($item['project_id']) && $item['project_id'] ? (int) $item['project_id'] : $project->id,
                'product_id'  => $item['product_id'] ?? null,
                'quantity'    => $qty,
                'unit_amount' => $rate,
                'line_total'  => $resolved,
            ];
        }

        if (empty($validItems)) {
            return back()->withInput()->with('toast', 'No items — add at least one product with a total.');
        }

        $projectIds   = array_unique(array_column($validItems, 'project_id'));
        $newProjectId = count($projectIds) === 1 ? $projectIds[0] : null;

        $purchase->update([
            'project_id'    => $newProjectId,
            'purchase_date' => $validated['purchase_date'],
            'product_id'    => null,
            'quantity'      => null,
            'unit_amount'   => null,
            'line_total'    => $batchTotal,
            'total_amount'  => $batchTotal,
        ]);

        $purchase->items()->delete();
        foreach ($validItems as $item) {
            $purchase->items()->create($item);
        }

        $redirect = $request->input('from') === 'purchases'
            ? to_route('purchases.index')
            : to_route('projects.show', $project);

        return $redirect->with('toast', 'Purchase updated.');
    }

    public function edit(Project $project): View
    {
        $this->authorize('update', $project);

        return view('projects.edit', $this->formData($project));
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $data = $request->validated();
        $data['project_number'] = filled($data['project_number'] ?? null) ? $data['project_number'] : $project->project_number;
        $project->update($data);

        return to_route('projects.show', $project)->with('toast', "Project \"{$project->name}\" updated.");
    }

    public function updateMeeting(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'next_meeting_at'    => ['nullable', 'date'],
            'next_meeting_notes' => ['nullable', 'string', 'max:250'],
        ]);

        $project->update($data);

        return back()->with('toast', 'Meeting schedule updated.');
    }

    /**
     * Soft-deletes the project together with its locations and works.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        DB::transaction(function () use ($project): void {
            $project->works()->get()->each->delete();
            $project->locations()->get()->each->delete();
            $project->delete();
        });

        return to_route('projects.index')->with('toast', "Project \"{$project->name}\" deleted.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Project $project): array
    {
        return [
            'project' => $project,
            'clients' => Client::query()
                ->where(fn (Builder $query) => $query->where('status', 'active')->orWhere('id', $project->client_id))
                ->orderBy('name')
                ->pluck('name', 'id'),
            'workTypes' => \App\Models\WorkType::orderBy('name')->get(['id', 'name']),
            'locations' => Project::whereNotNull('location')->where('location', '!=', '')->distinct()->orderBy('location')->pluck('location'),
        ];
    }

    /**
     * Next free PRJ-0001 style number for the signed-in contractor (deleted projects keep their numbers).
     */
    private function nextProjectNumber(): string
    {
        $sequence = Project::withTrashed()->count() + 1;

        do {
            $number = sprintf('PRJ-%04d', $sequence++);
        } while (Project::withTrashed()->where('project_number', $number)->exists());

        return $number;
    }
}
