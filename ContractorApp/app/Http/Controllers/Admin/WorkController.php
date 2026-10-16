<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WorkPriority;
use App\Enums\WorkStatus;
use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\Project;
use App\Models\Work;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only list of every contractor's works.
 */
class WorkController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->authorize('viewAny', Work::class);

        $status = $request->string('status')->value();
        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'contractor_id' => $request->integer('contractor_id') ?: null,
            'project_id' => $request->integer('project_id') ?: null,
            'status' => $status === 'open' ? 'open' : WorkStatus::tryFrom($status)?->value,
            'priority' => WorkPriority::tryFrom($request->string('priority')->value())?->value,
            'from' => $this->dateInput($request, 'from'),
            'to' => $this->dateInput($request, 'to'),
        ];

        $works = Work::query()
            ->with(['contractor:id,company_name', 'project:id,name', 'assignee:id,name'])
            ->when($filters['search'] !== '', fn (Builder $query) => $query->search($filters['search']))
            ->when($filters['contractor_id'], fn (Builder $query, int $id) => $query->forContractor($id))
            ->when($filters['project_id'], fn (Builder $query, int $id) => $query->where('project_id', $id))
            ->when($filters['status'] === 'open', fn (Builder $query) => $query->open())
            ->when($filters['status'] && $filters['status'] !== 'open', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['priority'], fn (Builder $query, string $priority) => $query->where('priority', $priority))
            ->when($filters['from'], fn (Builder $query, string $from) => $query->whereDate('due_date', '>=', $from))
            ->when($filters['to'], fn (Builder $query, string $to) => $query->whereDate('due_date', '<=', $to))
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('admin.works.index', [
            'works' => $works,
            'filters' => $filters,
            'hasFilters' => $filters['project_id'] || $filters['status'],
            'projects' => Project::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
