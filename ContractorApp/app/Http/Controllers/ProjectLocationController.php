<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectLocationRequest;
use App\Models\Project;
use App\Models\ProjectLocation;
use App\Services\LocationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Site locations of projects. New locations are created under a project resolved from the URL within
 * the signed-in contractor's projects, so a location can never be attached to another contractor's project.
 */
class ProjectLocationController extends Controller
{
    public function __construct(private LocationService $locations) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ProjectLocation::class);

        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'project_id' => $request->integer('project_id') ?: null,
            'state' => $request->string('state')->value(),
        ];

        $locations = ProjectLocation::query()
            ->with('project:id,name,project_number,client_id', 'project.client:id,name')
            ->whereHas('project')
            ->when($filters['search'] !== '', fn (Builder $query) => $query->search($filters['search']))
            ->when($filters['project_id'], fn (Builder $query, int $projectId) => $query->where('project_id', $projectId))
            ->when($filters['state'] !== '', fn (Builder $query) => $query->where('state', $filters['state']))
            ->orderBy('state')->orderBy('city')->orderBy('name')->orderBy('id')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('locations.index', [
            'locations' => $locations,
            'filters' => $filters,
            'hasFilters' => $filters['search'] !== '' || $filters['project_id'] || $filters['state'] !== '',
            'projects' => Project::orderBy('name')->pluck('name', 'id'),
            'states' => ProjectLocation::query()->distinct()->orderBy('state')->pluck('state'),
        ]);
    }

    /**
     * "Add location" without a project yet: pick one, then continue to that project's form.
     */
    public function chooseProject(Request $request): View|RedirectResponse
    {
        $this->authorize('create', ProjectLocation::class);

        if ($request->filled('project_id')) {
            $project = Project::findOrFail($request->integer('project_id'));

            return to_route('projects.locations.create', $project);
        }

        return view('locations.choose-project', [
            'projects' => Project::open()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function forProject(Project $project): RedirectResponse
    {
        $this->authorize('view', $project);

        return redirect(route('projects.show', $project).'#locations');
    }

    public function create(Project $project): View
    {
        $this->authorize('view', $project);
        $this->authorize('create', ProjectLocation::class);

        return view('locations.create', [
            'project' => $project,
            'location' => new ProjectLocation(['country' => 'India', 'state' => $project->primaryLocation()->value('state')]),
            'districtsByState' => $this->locations->districtsByState(),
        ]);
    }

    public function store(ProjectLocationRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('view', $project);
        $this->authorize('create', ProjectLocation::class);

        $location = new ProjectLocation($request->validated());
        $location->project()->associate($project);
        $location->save();

        return redirect(route('projects.show', $project).'#locations')->with('toast', "Location \"{$location->name}\" added.");
    }

    public function show(ProjectLocation $location): View
    {
        $this->authorize('view', $location);

        return view('locations.show', ['location' => $location->load('project.client')]);
    }

    public function edit(ProjectLocation $location): View
    {
        $this->authorize('update', $location);

        return view('locations.edit', [
            'location' => $location->load('project'),
            'project' => $location->project,
            'districtsByState' => $this->locations->districtsByState(),
        ]);
    }

    public function update(ProjectLocationRequest $request, ProjectLocation $location): RedirectResponse
    {
        $this->authorize('update', $location);

        $location->update($request->validated());

        return to_route('locations.show', $location)->with('toast', "Location \"{$location->name}\" updated.");
    }

    public function destroy(ProjectLocation $location): RedirectResponse
    {
        $this->authorize('delete', $location);

        $location->delete();

        return redirect(route('projects.show', $location->project_id).'#locations')->with('toast', "Location \"{$location->name}\" deleted.");
    }
}
