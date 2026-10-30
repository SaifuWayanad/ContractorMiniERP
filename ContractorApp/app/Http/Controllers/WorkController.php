<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Enums\WorkPriority;
use App\Enums\WorkStatus;
use App\Http\Requests\WorkRequest;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Models\Work;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkController extends Controller
{
    public const DUE_FILTERS = [
        'overdue' => 'Overdue',
        'today' => 'Due today',
        'week' => 'Due in 7 days',
        'month' => 'Due in 30 days',
        'none' => 'No due date',
    ];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Work::class);

        $status = $request->string('status')->value();
        $due = $request->string('due')->value();
        $filters = [
            'search'     => $request->string('search')->trim()->value(),
            'client_id'  => $request->integer('client_id') ?: null,
            'project_id' => $request->integer('project_id') ?: null,
            'status'     => $status === 'open' ? 'open' : WorkStatus::tryFrom($status)?->value,
            'priority'   => WorkPriority::tryFrom($request->string('priority')->value())?->value,
            'due'        => array_key_exists($due, self::DUE_FILTERS) ? $due : null,
        ];
        $today = today();

        $works = Work::query()
            ->with(['project:id,name', 'assignee:id,name'])
            ->when($filters['search'] !== '', fn (Builder $query) => $query->search($filters['search']))
            ->when($filters['client_id'], fn (Builder $query, int $cid) => $query->where('client_id', $cid))
            ->when($filters['project_id'], fn (Builder $query, int $projectId) => $query->where('project_id', $projectId))
            ->when($filters['status'] === 'open', fn (Builder $query) => $query->open())
            ->when($filters['status'] && $filters['status'] !== 'open', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['priority'], fn (Builder $query, string $priority) => $query->where('priority', $priority))
            ->when($filters['due'], fn (Builder $query, string $due) => match ($due) {
                'overdue' => $query->open()->whereDate('due_date', '<', $today),
                'today' => $query->whereDate('due_date', $today),
                'week' => $query->whereBetween('due_date', [$today, $today->copy()->addDays(7)]),
                'month' => $query->whereBetween('due_date', [$today, $today->copy()->addDays(30)]),
                'none' => $query->whereNull('due_date'),
            })
            ->orderByRaw('case when status in (?, ?, ?) then 0 else 1 end', WorkStatus::open())
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->paginate($this->perPage())
            ->withQueryString();

        $clients  = Client::orderBy('name')->pluck('name', 'id');
        $projects = $filters['client_id']
            ? Project::where('client_id', $filters['client_id'])->orderBy('name')->pluck('name', 'id')
            : Project::orderBy('name')->pluck('name', 'id');

        return view('works.index', [
            'works'      => $works,
            'filters'    => $filters,
            'hasFilters' => collect($filters)->except('search')->filter()->isNotEmpty() || $filters['search'] !== '',
            'clients'    => $clients,
            'projects'   => $projects,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Work::class);

        $work = new Work(['status' => WorkStatus::Pending, 'priority' => WorkPriority::Medium, 'start_date' => today()]);
        $work->project_id = $request->integer('project_id') ?: null;

        return view('works.create', $this->formData($work));
    }

    public function store(WorkRequest $request): RedirectResponse
    {
        $this->authorize('create', Work::class);

        $work = new Work($request->safe()->except(['project_id', 'assigned_to', 'from', 'from_project_id']));
        $this->assignRelations($work, $request);
        $work->save();

        if ($request->input('from') === 'project') {
            return to_route('projects.show', $work->project_id)->with('toast', "Work \"{$work->title}\" created.")->withFragment('works');
        }

        return to_route('works.show', $work)->with('toast', "Work \"{$work->title}\" created.");
    }

    public function show(Work $work): View
    {
        $this->authorize('view', $work);

        return view('works.show', ['work' => $work->load(['project', 'client', 'assignee'])]);
    }

    public function edit(Work $work): View
    {
        $this->authorize('update', $work);

        return view('works.edit', $this->formData($work));
    }

    public function update(WorkRequest $request, Work $work): RedirectResponse
    {
        $this->authorize('update', $work);

        $work->fill($request->safe()->except(['project_id', 'assigned_to', 'from', 'from_project_id']));
        $this->assignRelations($work, $request);
        $work->save();

        if ($request->input('from') === 'project') {
            return to_route('projects.show', $work->project_id)->with('toast', "Work \"{$work->title}\" updated.")->withFragment('works');
        }

        return to_route('works.show', $work)->with('toast', "Work \"{$work->title}\" updated.");
    }

    public function destroy(Work $work): RedirectResponse
    {
        $this->authorize('delete', $work);

        $work->delete();

        return to_route('works.index')->with('toast', "Work \"{$work->title}\" deleted.");
    }

    private function assignRelations(Work $work, WorkRequest $request): void
    {
        $project = Project::findOrFail($request->integer('project_id'));

        $work->project_id = $project->id;
        $work->client_id  = $project->client_id;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Work $work): array
    {
        return [
            'work' => $work,
            'projects' => Project::query()
                ->where(fn (Builder $query) => $query->open()->orWhere('id', $work->project_id))
                ->orderBy('name')
                ->pluck('name', 'id'),
            'assignees' => User::query()
                ->where('contractor_id', auth()->user()->contractor_id)
                ->where(fn (Builder $query) => $query->where('status', UserStatus::Active)->orWhere('id', $work->assigned_to))
                ->orderBy('name')
                ->pluck('name', 'id'),
        ];
    }
}
