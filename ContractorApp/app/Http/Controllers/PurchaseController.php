<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Client;
use App\Models\Product;
use App\Models\Project;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Purchase::class);

        $search   = $request->string('search')->trim()->value();
        $filters  = [
            'search'     => $search,
            'client_id'  => $request->integer('client_id') ?: null,
            'project_id' => $request->integer('project_id') ?: null,
        ];

        $purchases = Purchase::query()
            ->with(['project:id,name', 'product:id,name', 'items.product:id,name', 'items.project:id,name'])
            ->where(fn (Builder $q) => $q->whereNotNull('project_id')->orWhereHas('items'))
            ->when($filters['client_id'], fn (Builder $q, int $cid) => $q->whereHas('project', fn (Builder $p) => $p->where('client_id', $cid)))
            ->when($filters['project_id'], fn (Builder $q, int $pid) => $q->where('project_id', $pid))
            ->when($search !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($search) {
                $like = '%'.$search.'%';
                $q->where('supplier', 'like', $like)
                    ->orWhere('invoice_reference', 'like', $like)
                    ->orWhereHas('product', fn (Builder $p) => $p->where('name', 'like', $like));
            }))
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate($this->perPage())
            ->withQueryString();

        $clients  = Client::orderBy('name')->pluck('name', 'id');
        $projects = $filters['client_id']
            ? Project::where('client_id', $filters['client_id'])->orderBy('name')->pluck('name', 'id')
            : Project::orderBy('name')->pluck('name', 'id');

        return view('purchases.index', [
            'purchases' => $purchases,
            'filters'   => $filters,
            'clients'   => $clients,
            'projects'  => $projects,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Purchase::class);

        return view('purchases.create', $this->batchFormData());
    }

    public function storeBatch(Request $request): RedirectResponse
    {
        $this->authorize('create', Purchase::class);

        $contractorId = $request->user()->contractor_id;

        $validated = $request->validate([
            'purchase_date'       => ['required', 'date'],
            'invoice_reference'   => ['nullable', 'string', 'max:100'],
            'supplier'            => ['nullable', 'string', 'max:200'],
            'notes'               => ['nullable', 'string', 'max:5000'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.project_id'  => ['required', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'items.*.product_id'  => ['nullable', 'integer', Rule::exists('products', 'id')->where('contractor_id', $contractorId)->whereNull('deleted_at')],
            'items.*.quantity'    => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.line_total'  => ['nullable', 'numeric', 'min:0'],
        ]);

        $validItems = [];
        $batchTotal = 0.0;

        foreach ($validated['items'] as $item) {
            $qty       = isset($item['quantity'])    && $item['quantity']    > 0 ? (float) $item['quantity']    : null;
            $rate      = isset($item['unit_amount']) && $item['unit_amount'] > 0 ? (float) $item['unit_amount'] : null;
            $lineTotal = isset($item['line_total'])  && $item['line_total']  > 0 ? (float) $item['line_total']  : null;

            if (!$lineTotal && !($qty && $rate)) {
                continue;
            }

            $resolved    = $lineTotal ?? round($qty * $rate, 2);
            $batchTotal += $resolved;
            $validItems[] = [
                'project_id'  => $item['project_id'],
                'product_id'  => $item['product_id'] ?? null,
                'quantity'    => $qty,
                'unit_amount' => $rate,
                'line_total'  => $resolved,
            ];
        }

        if (empty($validItems)) {
            return back()->withInput()->withErrors(['items' => 'No purchases saved — add at least one item with a line total.']);
        }

        $projectIds    = array_unique(array_column($validItems, 'project_id'));
        $singleProject = count($projectIds) === 1 ? $projectIds[0] : null;

        $purchase = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $singleProject, $batchTotal, $validItems): Purchase {
            $purchase = Purchase::create([
                'project_id'        => $singleProject,
                'purchase_date'     => $validated['purchase_date'],
                'invoice_reference' => $validated['invoice_reference'] ?? null,
                'supplier'          => $validated['supplier'] ?? null,
                'notes'             => $validated['notes'] ?? null,
                'line_total'        => $batchTotal,
                'total_amount'      => $batchTotal,
            ]);

            foreach ($validItems as $item) {
                $purchase->items()->create($item);
            }

            return $purchase;
        });

        $count = count($validItems);
        return to_route('purchases.index')
            ->with('toast', "{$count} item" . ($count !== 1 ? 's' : '') . ' recorded.');
    }

    public function store(PurchaseRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Purchase::class);

        $purchase = Purchase::create($request->validated());
        $purchase->load('product:id,name,unit');

        if ($request->expectsJson()) {
            return response()->json([
                'id'           => $purchase->id,
                'product_name' => $purchase->product?->name,
                'product_unit' => $purchase->product?->unit,
                'purchase_date'=> $purchase->purchase_date?->format('d M Y'),
                'quantity'     => $purchase->quantity !== null ? (float) $purchase->quantity : null,
                'unit_amount'  => $purchase->unit_amount !== null ? (float) $purchase->unit_amount : null,
                'line_total'   => (float) $purchase->line_total,
            ], 201);
        }

        $redirect = $request->input('from') === 'project'
            ? to_route('projects.show', $purchase->project_id)
            : to_route('purchases.index');

        return $redirect->with('toast', 'Purchase recorded.');
    }

    public function editBatch(Purchase $purchase): View
    {
        $this->authorize('update', $purchase);

        $purchase->load(['items.product:id,name,unit', 'items.project:id,name,project_number', 'product:id,name,unit', 'project:id,name,project_number']);

        $isBatch = $purchase->items->isNotEmpty();

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
                'project_id'    => $purchase->project_id,
                'project_label' => $purchase->project ? ($purchase->project->name . ($purchase->project->project_number ? ' (' . $purchase->project->project_number . ')' : '')) : null,
                'product_id'    => $purchase->product_id,
                'product_label' => $purchase->product ? ($purchase->product->name . ($purchase->product->unit ? ' (' . $purchase->product->unit . ')' : '')) : null,
                'quantity'      => $purchase->quantity !== null ? rtrim(rtrim(number_format((float) $purchase->quantity, 4, '.', ''), '0'), '.') : null,
                'unit_amount'   => $purchase->unit_amount !== null ? number_format((float) $purchase->unit_amount, 2, '.', '') : null,
                'line_total'    => number_format((float) $purchase->line_total, 2, '.', ''),
            ]]);

        return view('purchases.edit-batch', array_merge($this->batchFormData(), [
            'purchase'      => $purchase,
            'existingItems' => $existingItems,
        ]));
    }

    public function updateBatch(Request $request, Purchase $purchase): RedirectResponse
    {
        $this->authorize('update', $purchase);

        $contractorId = $request->user()->contractor_id;

        $validated = $request->validate([
            'purchase_date'       => ['required', 'date'],
            'invoice_reference'   => ['nullable', 'string', 'max:100'],
            'supplier'            => ['nullable', 'string', 'max:200'],
            'notes'               => ['nullable', 'string', 'max:5000'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.project_id'  => ['required', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'items.*.product_id'  => ['nullable', 'integer', Rule::exists('products', 'id')->where('contractor_id', $contractorId)->whereNull('deleted_at')],
            'items.*.quantity'    => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.line_total'  => ['nullable', 'numeric', 'min:0'],
        ]);

        $validItems = [];
        $batchTotal = 0.0;

        foreach ($validated['items'] as $item) {
            $qty       = isset($item['quantity'])    && $item['quantity']    > 0 ? (float) $item['quantity']    : null;
            $rate      = isset($item['unit_amount']) && $item['unit_amount'] > 0 ? (float) $item['unit_amount'] : null;
            $lineTotal = isset($item['line_total'])  && $item['line_total']  > 0 ? (float) $item['line_total']  : null;

            if (!$lineTotal && !($qty && $rate)) continue;

            $resolved    = $lineTotal ?? round($qty * $rate, 2);
            $batchTotal += $resolved;
            $validItems[] = [
                'project_id'  => (int) $item['project_id'],
                'product_id'  => $item['product_id'] ?? null,
                'quantity'    => $qty,
                'unit_amount' => $rate,
                'line_total'  => $resolved,
            ];
        }

        if (empty($validItems)) {
            return back()->withInput()->withErrors(['items' => 'No purchases saved — add at least one item with a line total.']);
        }

        $projectIds   = array_unique(array_column($validItems, 'project_id'));
        $singleProject = count($projectIds) === 1 ? $projectIds[0] : null;

        \Illuminate\Support\Facades\DB::transaction(function () use ($purchase, $validated, $singleProject, $batchTotal, $validItems): void {
            $purchase->update([
                'project_id'        => $singleProject,
                'purchase_date'     => $validated['purchase_date'],
                'invoice_reference' => $validated['invoice_reference'] ?? null,
                'supplier'          => $validated['supplier'] ?? null,
                'notes'             => $validated['notes'] ?? null,
                'product_id'        => null,
                'quantity'          => null,
                'unit_amount'       => null,
                'line_total'        => $batchTotal,
                'total_amount'      => $batchTotal,
            ]);

            $purchase->items()->delete();
            foreach ($validItems as $item) {
                $purchase->items()->create($item);
            }
        });

        return to_route('purchases.index')->with('toast', 'Purchase updated.');
    }

    public function show(Purchase $purchase, Request $request): View
    {
        $this->authorize('view', $purchase);

        $purchase->load(['product:id,name,unit', 'items.product:id,name,unit', 'items.project:id,name,project_number', 'project:id,name,project_number']);

        $filterProjectId = $request->integer('project_id') ?: null;

        return view('purchases.show', compact('purchase', 'filterProjectId'));
    }

    public function edit(Purchase $purchase): View
    {
        $this->authorize('update', $purchase);

        return view('purchases.edit', $this->formData($purchase));
    }

    public function update(PurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        $this->authorize('update', $purchase);

        $purchase->update($request->validated());

        $redirect = $request->input('from') === 'project'
            ? to_route('projects.show', $purchase->project_id)
            : to_route('purchases.index');

        return $redirect->with('toast', 'Purchase updated.');
    }

    public function destroy(Request $request, Purchase $purchase): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $purchase);

        $projectId = $purchase->project_id;
        $purchase->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Purchase deleted.']);
        }

        return to_route('projects.show', $projectId)->with('toast', 'Purchase deleted.');
    }

    /** @return array<string, mixed> */
    private function formData(Purchase $purchase): array
    {
        $projects = Project::query()
            ->with('client:id,name')
            ->where(fn (Builder $q) => $q->open()->orWhere('id', $purchase->project_id))
            ->orderBy('name')
            ->get(['id', 'name', 'project_number', 'client_id']);

        $products = Product::orderBy('name')->get(['id', 'name', 'unit', 'default_rate']);

        return [
            'purchase'     => $purchase,
            'projects'     => $projects->pluck('name', 'id'),
            'projectsJson' => $projects->mapWithKeys(fn ($p) => [$p->id => ['client' => $p->client?->name ?? '']]),
            'products'     => $products->pluck('name', 'id'),
            'productsJson' => $products->mapWithKeys(fn ($p) => [$p->id => ['unit' => $p->unit ?? '', 'rate' => (float) ($p->default_rate ?? 0)]]),
        ];
    }

    /** @return array<string, mixed> */
    private function batchFormData(): array
    {
        $projects = Project::query()
            ->with('client:id,name')
            ->open()
            ->orderBy('name')
            ->get(['id', 'name', 'project_number', 'client_id']);

        $products = Product::orderBy('name')->get(['id', 'name', 'unit', 'default_rate']);

        return [
            'projectsJson' => $projects->map(fn ($p) => [
                'id'     => $p->id,
                'name'   => $p->name,
                'number' => $p->project_number,
                'client' => $p->client?->name ?? '',
            ]),
            'productsJson' => $products->map(fn ($p) => [
                'id'   => $p->id,
                'name' => $p->name,
                'unit' => $p->unit ?? '',
                'rate' => (float) ($p->default_rate ?? 0),
            ]),
        ];
    }
}
