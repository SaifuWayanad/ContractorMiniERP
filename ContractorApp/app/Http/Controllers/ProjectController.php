<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Http\Requests\ProjectRequest;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Dealer;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProjectController extends Controller
{
    private const SORTS = ['newest', 'oldest', 'name', 'start', 'end', 'maturity', 'budget'];

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
            'maturity' => $query->orderByRaw('expected_maturity_date is null')->orderBy('expected_maturity_date'),
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

        $project->load(['client', 'dealer', 'dealers', 'locations' => fn ($query) => $query->orderBy('id')]);

        return view('projects.show', [
            'project'        => $project,
            'works'          => $project->works()->with('assignee:id,name')->orderByRaw('due_date is null')->orderBy('due_date')->get(),
            'meetings'       => $project->meetings()->with('createdBy:id,name')->orderByDesc('meeting_at')->get(),
            'dealerOptions'  => Dealer::orderBy('name')->get(['id', 'name', 'city']),
            'activity' => ActivityLog::query()
                ->where('contractor_id', $project->contractor_id)
                ->where(function (Builder $query) use ($project): void {
                    $query->where(fn (Builder $q) => $q->where('entity_type', 'project')->where('entity_id', $project->id))
                        ->orWhere(fn (Builder $q) => $q->where('entity_type', 'project_location')->whereIn('entity_id', $project->locations()->withTrashed()->select('id')))
                        ->orWhere(fn (Builder $q) => $q->where('entity_type', 'work')->whereIn('entity_id', $project->works()->withTrashed()->select('id')));
                })
                ->with('user:id,name')
                ->latest()
                ->orderByDesc('id')
                ->limit(12)
                ->get(),
        ]);
    }

    public function addContact(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);
        $data = $request->validate(['dealer_id' => ['required', 'integer', 'exists:dealers,id']]);
        $project->dealers()->syncWithoutDetaching([$data['dealer_id']]);
        return back()->with('toast', 'Contact added.');
    }

    public function removeContact(Project $project, Dealer $dealer): RedirectResponse
    {
        $this->authorize('update', $project);
        $project->dealers()->detach($dealer->id);
        return back()->with('toast', 'Contact removed.');
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
            'dealers' => Dealer::orderBy('name')->get(['id', 'name', 'city'])
                ->mapWithKeys(fn (Dealer $dealer): array => [$dealer->id => $dealer->city ? "{$dealer->name} ({$dealer->city})" : $dealer->name]),
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
