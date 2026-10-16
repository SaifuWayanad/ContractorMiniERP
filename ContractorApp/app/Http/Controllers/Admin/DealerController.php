<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DealerRequest;
use App\Models\Contractor;
use App\Models\Dealer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DealerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Dealer::class);

        $filters = [
            'search'        => $request->string('search')->trim()->value(),
            'contractor_id' => $request->integer('contractor_id') ?: null,
        ];

        $dealers = Dealer::query()
            ->with('contractor:id,company_name')
            ->withCount('projects')
            ->when($filters['search'] !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($filters): void {
                $like = '%'.$filters['search'].'%';
                $q->where('name', 'like', $like)->orWhere('city', 'like', $like)->orWhere('phone', 'like', $like);
            }))
            ->when($filters['contractor_id'], fn (Builder $q, int $id) => $q->forContractor($id))
            ->latest()
            ->orderByDesc('id')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('admin.dealers.index', [
            'dealers'     => $dealers,
            'filters'     => $filters,
            'hasFilters'  => collect($filters)->filter()->isNotEmpty(),
            'contractors' => Contractor::orderBy('company_name')->pluck('company_name', 'id'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Dealer::class);

        return view('admin.dealers.create', [
            'dealer'      => new Dealer,
            'contractors' => Contractor::orderBy('company_name')->pluck('company_name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Dealer::class);

        $validated = $request->validate([
            'contractor_id' => ['required', 'integer', Rule::exists('contractors', 'id')],
            'name'          => ['required', 'string', 'max:120'],
            'city'          => ['nullable', 'string', 'max:80'],
            'phone'         => ['nullable', 'string', 'max:20'],
        ]);

        $dealer = new Dealer(Arr::except($validated, 'contractor_id'));
        $dealer->contractor_id = $validated['contractor_id'];
        $dealer->save();

        return to_route('admin.dealers.index')->with('toast', "Contact \"{$dealer->name}\" added.");
    }

    public function edit(Dealer $dealer): View
    {
        $this->authorize('update', $dealer);

        return view('admin.dealers.edit', [
            'dealer'      => $dealer,
            'contractors' => Contractor::orderBy('company_name')->pluck('company_name', 'id'),
        ]);
    }

    public function update(Request $request, Dealer $dealer): RedirectResponse
    {
        $this->authorize('update', $dealer);

        $validated = $request->validate([
            'contractor_id' => ['required', 'integer', Rule::exists('contractors', 'id')],
            'name'          => ['required', 'string', 'max:120'],
            'city'          => ['nullable', 'string', 'max:80'],
            'phone'         => ['nullable', 'string', 'max:20'],
        ]);

        $dealer->fill(Arr::except($validated, 'contractor_id'));
        $dealer->contractor_id = $validated['contractor_id'];
        $dealer->save();

        return to_route('admin.dealers.index')->with('toast', "Contact \"{$dealer->name}\" updated.");
    }

    public function destroy(Dealer $dealer): RedirectResponse
    {
        $this->authorize('delete', $dealer);

        $dealer->delete();

        return to_route('admin.dealers.index')->with('toast', "Contact \"{$dealer->name}\" deleted.");
    }
}
