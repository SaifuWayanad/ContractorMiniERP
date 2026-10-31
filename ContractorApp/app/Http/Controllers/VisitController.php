<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VisitController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Visit::class);

        $search   = $request->string('search')->trim()->value();
        $clientId = $request->integer('client_id') ?: null;

        $visits = Visit::query()
            ->with('client:id,name')
            ->when($search !== '', fn (Builder $q) => $q->whereHas('client', fn (Builder $c) => $c->where('name', 'like', '%'.$search.'%'))
                ->orWhere('purpose', 'like', '%'.$search.'%')
                ->orWhere('description', 'like', '%'.$search.'%'))
            ->when($clientId, fn (Builder $q, int $id) => $q->where('client_id', $id))
            ->orderByDesc('meeting_date')
            ->orderByDesc('id')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('visits.index', [
            'visits'   => $visits,
            'search'   => $search,
            'clientId' => $clientId,
            'clients'  => Client::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Visit::class);

        return view('visits.create', $this->formData(new Visit(['meeting_date' => today()])));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Visit::class);

        $data = $this->validateVisit($request);
        Visit::create($data);

        return to_route('visits.index')->with('toast', 'Visit logged.');
    }

    public function edit(Visit $visit): View
    {
        $this->authorize('update', $visit);

        return view('visits.edit', $this->formData($visit));
    }

    public function update(Request $request, Visit $visit): RedirectResponse
    {
        $this->authorize('update', $visit);

        $visit->update($this->validateVisit($request));

        return to_route('visits.index')->with('toast', 'Visit updated.');
    }

    public function destroy(Visit $visit): RedirectResponse
    {
        $this->authorize('delete', $visit);

        $visit->delete();

        return to_route('visits.index')->with('toast', 'Visit deleted.');
    }

    /** @return array<string, mixed> */
    private function validateVisit(Request $request): array
    {
        $contractorId = $request->user()->contractor_id;

        return $request->validate([
            'client_id'         => ['required', 'integer', \Illuminate\Validation\Rule::exists('clients', 'id')->where('contractor_id', $contractorId)->whereNull('deleted_at')],
            'meeting_date'      => ['required', 'date'],
            'purpose'           => ['nullable', 'string', 'max:200'],
            'location'          => ['nullable', 'string', 'max:200'],
            'description'       => ['nullable', 'string', 'max:5000'],
            'next_meeting_date' => ['nullable', 'date', 'after_or_equal:meeting_date'],
        ]);
    }

    /** @return array<string, mixed> */
    private function formData(Visit $visit): array
    {
        $clientLocations = Client::orderBy('name')
            ->get(['id', 'city'])
            ->mapWithKeys(fn ($c) => [$c->id => $c->city ?: ''])
            ->all();

        $visitLocations = Visit::whereNotNull('location')
            ->where('location', '!=', '')
            ->distinct()
            ->orderBy('location')
            ->pluck('location')
            ->merge(
                Project::whereNotNull('location')->where('location', '!=', '')->distinct()->pluck('location')
            )
            ->unique()
            ->sort()
            ->values();

        return [
            'visit'           => $visit,
            'clients'         => Client::orderBy('name')->pluck('name', 'id'),
            'clientLocations' => $clientLocations,
            'visitLocations'  => $visitLocations,
        ];
    }
}
