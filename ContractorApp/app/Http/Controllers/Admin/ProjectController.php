<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectRequest;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Contractor;
use App\Models\Dealer;
use App\Models\Project;
use App\Models\ProjectLocation;
use App\Services\LocationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Read-only view of every contractor's projects.
 */
class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Project::class);

        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'contractor_id' => $request->integer('contractor_id') ?: null,
            'client_id' => $request->integer('client_id') ?: null,
            'status' => ProjectStatus::tryFrom($request->string('status')->value())?->value,
            'from' => $this->dateInput($request, 'from'),
            'to' => $this->dateInput($request, 'to'),
        ];

        $projects = Project::query()
            ->with(['contractor:id,company_name', 'client:id,name', 'primaryLocation'])
            ->when($filters['search'] !== '', fn (Builder $query) => $query->search($filters['search']))
            ->when($filters['contractor_id'], fn (Builder $query, int $id) => $query->forContractor($id))
            ->when($filters['client_id'], fn (Builder $query, int $id) => $query->where('client_id', $id))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['from'], fn (Builder $query, string $from) => $query->whereDate('start_date', '>=', $from))
            ->when($filters['to'], fn (Builder $query, string $to) => $query->whereDate('start_date', '<=', $to))
            ->latest()
            ->orderByDesc('id')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('admin.projects.index', [
            'projects' => $projects,
            'filters' => $filters,
            'hasFilters' => $filters['client_id'] || $filters['status'],
            'clients' => Client::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request, LocationService $locations): View
    {
        $this->authorize('create', Project::class);

        $contractorId = $request->integer('contractor_id') ?: null;

        $dealers = $contractorId
            ? Dealer::forContractor($contractorId)->orderBy('name')->get(['id', 'name', 'city'])
                ->mapWithKeys(fn (Dealer $d): array => [$d->id => $d->city ? "{$d->name} ({$d->city})" : $d->name])
            : collect();

        return view('admin.projects.create', [
            'project'              => new Project,
            'contractors'          => Contractor::orderBy('company_name')->pluck('company_name', 'id'),
            'clients'              => $contractorId ? Client::forContractor($contractorId)->orderBy('name')->pluck('name', 'id') : collect(),
            'dealers'              => $dealers,
            'selectedContractorId' => $contractorId,
            'districtsByState'     => $locations->districtsByState(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $validated = $request->validate([
            'contractor_id'          => ['required', 'integer', Rule::exists('contractors', 'id')],
            'client_id'              => ['required', 'integer', Rule::exists('clients', 'id')->whereNull('deleted_at')],
            'name'                   => ['required', 'string', 'max:150'],
            'dealer_id'              => ['nullable', 'integer', Rule::exists('dealers', 'id')],
            'status'                 => ['nullable', Rule::enum(ProjectStatus::class)],
            'start_date'             => ['nullable', 'date'],
            'description'            => ['nullable', 'string', 'max:5000'],
            'location_name'          => ['nullable', 'string', 'max:120'],
            'location_address'       => ['required_with:location_name', 'nullable', 'string', 'max:250'],
            'location_city'          => ['required_with:location_name', 'nullable', 'string', 'max:80'],
            'location_state'         => ['required_with:location_name', 'nullable', 'string', 'max:80'],
            'location_district'      => ['nullable', 'string', 'max:80'],
            'location_country'       => ['nullable', 'string', 'max:80'],
            'location_postal_code'   => ['nullable', 'string', 'max:12'],
            'location_contact_person'=> ['nullable', 'string', 'max:100'],
            'location_contact_phone' => ['nullable', 'string', 'max:20'],
            'location_notes'         => ['nullable', 'string', 'max:5000'],
        ]);

        $project = DB::transaction(function () use ($validated): Project {
            $validated['status'] ??= ProjectStatus::Planning->value;
            $validated['project_number'] = filled($validated['project_number'] ?? null)
                ? $validated['project_number']
                : $this->nextProjectNumber();

            $project = new Project(Arr::except($validated, ['contractor_id', ...array_filter(array_keys($validated), fn ($k) => str_starts_with($k, 'location_'))]));
            $project->contractor_id = $validated['contractor_id'];
            $project->save();

            if (filled($validated['location_name'] ?? null)) {
                $location = new ProjectLocation([
                    'name'           => $validated['location_name'],
                    'address'        => $validated['location_address'],
                    'city'           => $validated['location_city'],
                    'state'          => $validated['location_state'],
                    'district'       => $validated['location_district'] ?? null,
                    'country'        => filled($validated['location_country'] ?? null) ? $validated['location_country'] : 'India',
                    'postal_code'    => $validated['location_postal_code'] ?? null,
                    'contact_person' => $validated['location_contact_person'] ?? null,
                    'contact_phone'  => $validated['location_contact_phone'] ?? null,
                    'notes'          => $validated['location_notes'] ?? null,
                ]);
                $location->project_id = $project->id;
                $location->contractor_id = $project->contractor_id;
                $location->save();
            }

            return $project;
        });

        return to_route('admin.projects.show', $project)->with('toast', "Project \"{$project->name}\" created.");
    }

    public function edit(Project $project): View
    {
        $this->authorize('update', $project);

        return view('admin.projects.edit', [
            'project' => $project,
            'clients' => Client::forContractor($project->contractor_id)->orderBy('name')->pluck('name', 'id'),
            'dealers' => Dealer::forContractor($project->contractor_id)->orderBy('name')->get(['id', 'name', 'city'])
                ->mapWithKeys(fn (Dealer $d): array => [$d->id => $d->city ? "{$d->name} ({$d->city})" : $d->name]),
        ]);
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return to_route('admin.projects.show', $project)->with('toast', "Project \"{$project->name}\" updated.");
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return to_route('admin.projects.index')->with('toast', "Project \"{$project->name}\" deleted.");
    }

    public function quickDealer(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', Project::class);

        $data = $request->validate([
            'contractor_id' => ['required', 'integer', Rule::exists('contractors', 'id')],
            'name'          => ['required', 'string', 'max:120'],
            'phone'         => ['nullable', 'string', 'max:20'],
        ]);

        $dealer = new Dealer(['name' => $data['name'], 'phone' => $data['phone'] ?? null]);
        $dealer->contractor_id = $data['contractor_id'];
        $dealer->save();

        return response()->json(['id' => $dealer->id, 'name' => $dealer->name], 201);
    }

    private function nextProjectNumber(): string
    {
        $sequence = Project::withTrashed()->count() + 1;

        do {
            $number = sprintf('PRJ-%04d', $sequence++);
        } while (Project::withTrashed()->where('project_number', $number)->exists());

        return $number;
    }

    public function show(Project $project): View
    {
        $this->authorize('view', $project);

        $project->load(['contractor', 'client', 'dealers', 'locations', 'works.assignee:id,name', 'meetings.createdBy:id,name']);

        return view('admin.projects.show', [
            'project' => $project,
            'activity' => ActivityLog::query()
                ->where('contractor_id', $project->contractor_id)
                ->where('entity_type', 'project')
                ->where('entity_id', $project->id)
                ->with('user:id,name')
                ->latest()
                ->orderByDesc('id')
                ->limit(10)
                ->get(),
        ]);
    }
}
