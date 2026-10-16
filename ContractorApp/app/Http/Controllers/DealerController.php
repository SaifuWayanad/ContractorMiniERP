<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Http\Requests\DealerRequest;
use App\Models\Dealer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The contractor's material dealers, used on the project form.
 */
class DealerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Dealer::class);

        $search = $request->string('search')->trim()->value();

        return view('dealers.index', [
            'dealers' => Dealer::query()
                ->withCount('projects')
                ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $q) => $q->where('name', 'like', "%{$search}%")->orWhere('city', 'like', "%{$search}%")))
                ->orderBy('name')
                ->orderBy('id')
                ->paginate($this->perPage())
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Dealer::class);

        return view('dealers.create', ['dealer' => new Dealer]);
    }

    public function store(DealerRequest $request): RedirectResponse
    {
        $this->authorize('create', Dealer::class);

        $dealer = Dealer::create($request->validated());

        return to_route('dealers.index')->with('toast', "Dealer \"{$dealer->name}\" added.");
    }

    public function quickStore(Request $request): JsonResponse
    {
        $this->authorize('create', Dealer::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'regex:'.ClientRequest::PHONE_PATTERN],
        ]);
        $dealer = Dealer::create($data);

        return response()->json(['id' => $dealer->id, 'name' => $dealer->name], 201);
    }

    public function edit(Dealer $dealer): View
    {
        $this->authorize('update', $dealer);

        return view('dealers.edit', ['dealer' => $dealer]);
    }

    public function update(DealerRequest $request, Dealer $dealer): RedirectResponse
    {
        $this->authorize('update', $dealer);

        $dealer->update($request->validated());

        return to_route('dealers.index')->with('toast', "Dealer \"{$dealer->name}\" updated.");
    }

    /**
     * Soft-deletes the dealer; projects keep showing its name.
     */
    public function destroy(Dealer $dealer): RedirectResponse
    {
        $this->authorize('delete', $dealer);

        $dealer->delete();

        return to_route('dealers.index')->with('toast', "Dealer \"{$dealer->name}\" deleted.");
    }
}
