<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\Project;
use App\Models\ProjectLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only list of every contractor's project locations.
 */
class ProjectLocationController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->authorize('viewAny', ProjectLocation::class);

        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'contractor_id' => $request->integer('contractor_id') ?: null,
            'project_id' => $request->integer('project_id') ?: null,
            'state' => $request->string('state')->value() ?: null,
        ];

        $locations = ProjectLocation::query()
            ->with(['contractor:id,company_name', 'project:id,name'])
            ->when($filters['search'] !== '', fn (Builder $query) => $query->search($filters['search']))
            ->when($filters['contractor_id'], fn (Builder $query, int $id) => $query->forContractor($id))
            ->when($filters['project_id'], fn (Builder $query, int $id) => $query->where('project_id', $id))
            ->when($filters['state'], fn (Builder $query, string $state) => $query->where('state', $state))
            ->orderBy('state')->orderBy('city')->orderBy('id')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('admin.locations.index', [
            'locations' => $locations,
            'filters' => $filters,
            'hasFilters' => (bool) $filters['state'],
            'states' => ProjectLocation::query()->distinct()->orderBy('state')->pluck('state'),
        ]);
    }
}
