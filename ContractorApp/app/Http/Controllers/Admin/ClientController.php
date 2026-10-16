<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClientStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Models\Contractor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Read-only view of every contractor's clients.
 */
class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Client::class);

        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'contractor_id' => $request->integer('contractor_id') ?: null,
            'status' => ClientStatus::tryFrom($request->string('status')->value())?->value,
            'from' => $this->dateInput($request, 'from'),
            'to' => $this->dateInput($request, 'to'),
        ];

        $clients = Client::query()
            ->with('contractor:id,company_name')
            ->withCount('projects')
            ->when($filters['search'] !== '', fn (Builder $query) => $query->search($filters['search']))
            ->when($filters['contractor_id'], fn (Builder $query, int $id) => $query->forContractor($id))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['from'], fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'], fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->orderByDesc('id')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('admin.clients.index', [
            'clients' => $clients,
            'filters' => $filters,
            'hasFilters' => collect($filters)->filter()->isNotEmpty(),
            'contractors' => Contractor::orderBy('company_name')->pluck('company_name', 'id'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Client::class);

        return view('admin.clients.create', [
            'client' => new Client,
            'contractors' => Contractor::orderBy('company_name')->pluck('company_name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Client::class);

        $validated = $request->validate([
            'contractor_id' => ['required', 'integer', Rule::exists('contractors', 'id')],
            'name'           => ['required', 'string', 'max:120'],
            'company'        => ['nullable', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'email'          => ['nullable', 'email', 'max:150'],
            'phone'          => ['nullable', 'string', 'regex:'.ClientRequest::PHONE_PATTERN],
            'status'         => ['nullable', Rule::enum(ClientStatus::class)],
            'notes'          => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['status'] ??= ClientStatus::Active->value;

        $client = new Client(Arr::except($validated, 'contractor_id'));
        $client->contractor_id = $validated['contractor_id'];
        $client->save();

        return to_route('admin.clients.index')->with('toast', "Customer \"{$client->name}\" created.");
    }

    public function show(Client $client): View
    {
        $this->authorize('view', $client);

        return view('admin.clients.show', [
            'client' => $client->load('contractor'),
            'projects' => $client->projects()->with('primaryLocation')->latest()->get(),
        ]);
    }

    public function edit(Client $client): View
    {
        $this->authorize('update', $client);

        return view('admin.clients.edit', ['client' => $client]);
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->update($request->validated());

        return to_route('admin.clients.show', $client)->with('toast', "Customer \"{$client->name}\" updated.");
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        $client->delete();

        return to_route('admin.clients.index')->with('toast', "Customer \"{$client->name}\" deleted.");
    }

    public function quickStore(Request $request): JsonResponse
    {
        $this->authorize('create', Client::class);

        $data = $request->validate([
            'contractor_id' => ['required', 'integer', Rule::exists('contractors', 'id')],
            'name'          => ['required', 'string', 'max:150'],
            'phone'         => ['nullable', 'string', 'max:20'],
        ]);

        $client = new Client(['name' => $data['name'], 'phone' => $data['phone'] ?? null, 'status' => ClientStatus::Active->value]);
        $client->contractor_id = $data['contractor_id'];
        $client->save();

        return response()->json(['id' => $client->id, 'name' => $client->name], 201);
    }
}
