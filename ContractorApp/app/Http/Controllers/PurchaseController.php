<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Client;
use App\Models\Product;
use App\Models\Project;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->with(['project:id,name', 'product:id,name'])
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

        $purchase = new Purchase(['purchase_date' => today()->toDateString()]);
        $purchase->project_id = $request->integer('project_id') ?: null;

        return view('purchases.create', $this->formData($purchase));
    }

    public function store(PurchaseRequest $request): RedirectResponse
    {
        $this->authorize('create', Purchase::class);

        $purchase = Purchase::create($request->validated());

        $redirect = $request->input('from') === 'project'
            ? to_route('projects.show', $purchase->project_id)
            : to_route('purchases.index');

        return $redirect->with('toast', 'Purchase recorded.');
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

    public function destroy(Purchase $purchase): RedirectResponse
    {
        $this->authorize('delete', $purchase);

        $projectId = $purchase->project_id;
        $purchase->delete();

        return to_route('projects.show', $projectId)->with('toast', 'Purchase deleted.');
    }

    /** @return array<string, mixed> */
    private function formData(Purchase $purchase): array
    {
        $projects = Project::query()
            ->with('client:id,name')
            ->where(fn (Builder $q) => $q->open()->orWhere('id', $purchase->project_id))
            ->orderBy('name')
            ->get(['id', 'name', 'client_id']);

        $products = Product::orderBy('name')->get(['id', 'name', 'unit', 'default_rate']);

        return [
            'purchase'     => $purchase,
            'projects'     => $projects->pluck('name', 'id'),
            'projectsJson' => $projects->mapWithKeys(fn ($p) => [$p->id => ['client' => $p->client?->name ?? '']]),
            'products'     => $products->pluck('name', 'id'),
            'productsJson' => $products->mapWithKeys(fn ($p) => [$p->id => ['unit' => $p->unit ?? '', 'rate' => (float) ($p->default_rate ?? 0)]]),
        ];
    }
}
