<?php

namespace App\Http\Controllers;

use App\Enums\ClientStatus;
use App\Http\Requests\ClientRequest;
use App\Models\ActivityLog;
use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    private const SORTS = [
        'newest' => ['created_at', 'desc'],
        'oldest' => ['created_at', 'asc'],
        'name' => ['name', 'asc'],
        'projects' => ['projects_count', 'desc'],
    ];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Client::class);

        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'status' => ClientStatus::tryFrom($request->string('status')->value())?->value,
            'from' => $this->dateInput($request, 'from'),
            'to' => $this->dateInput($request, 'to'),
            'sort' => array_key_exists($request->string('sort')->value(), self::SORTS) ? $request->string('sort')->value() : 'newest',
        ];
        [$sortColumn, $sortDirection] = self::SORTS[$filters['sort']];

        $clients = Client::query()
            ->withCount('projects')
            ->when($filters['search'] !== '', fn (Builder $query) => $query->search($filters['search']))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['from'], fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'], fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->orderBy($sortColumn, $sortDirection)
            ->orderByDesc('id')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('clients.index', [
            'clients' => $clients,
            'filters' => $filters,
            'hasFilters' => $filters['search'] !== '' || $filters['status'] || $filters['from'] || $filters['to'],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Client::class);

        return view('clients.create', ['client' => new Client(['country' => 'India', 'status' => ClientStatus::Active])]);
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $this->authorize('create', Client::class);

        $data = $request->validated();
        $data['status'] ??= \App\Enums\ClientStatus::Active->value;
        $client = Client::create($data);

        return to_route('clients.index')->with('toast', "Customer \"{$client->name}\" created.");
    }

    public function quickStore(Request $request): JsonResponse
    {
        $this->authorize('create', Client::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'regex:'.ClientRequest::PHONE_PATTERN],
        ]);
        $data['status'] = ClientStatus::Active->value;
        $client = Client::create($data);

        return response()->json(['id' => $client->id, 'name' => $client->name], 201);
    }

    public function show(Client $client): View
    {
        $this->authorize('view', $client);

        return view('clients.show', [
            'client' => $client,
            'projects' => $client->projects()->with('primaryLocation')->latest()->orderByDesc('id')->get(),
            'activity' => ActivityLog::query()
                ->where('contractor_id', $client->contractor_id)
                ->where(function (Builder $query) use ($client): void {
                    $query->where(fn (Builder $q) => $q->where('entity_type', 'client')->where('entity_id', $client->id))
                        ->orWhere(fn (Builder $q) => $q->where('entity_type', 'project')->whereIn('entity_id', $client->projects()->select('id')));
                })
                ->with('user:id,name')
                ->latest()
                ->orderByDesc('id')
                ->limit(10)
                ->get(),
        ]);
    }

    public function edit(Client $client): View
    {
        $this->authorize('update', $client);

        return view('clients.edit', ['client' => $client]);
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->update($request->validated());

        return to_route('clients.show', $client)->with('toast', "Client \"{$client->name}\" updated.");
    }

    /**
     * Soft-deletes the client. Clients with projects must be deactivated instead, so project history stays intact.
     */
    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        if ($client->projects()->exists()) {
            return back()->with('error', "\"{$client->name}\" has projects, so it can't be deleted. Set it to Inactive instead.");
        }

        $client->delete();

        return to_route('clients.index')->with('toast', "Client \"{$client->name}\" deleted.");
    }
}
