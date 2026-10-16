<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Contractor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public const ACTIONS = [
        'login' => 'Sign in',
        'logout' => 'Sign out',
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'status_changed' => 'Status changed',
        'completed' => 'Completed',
    ];

    public function __invoke(Request $request): View
    {
        Gate::authorize('activity.view');

        $action = $request->string('action')->value();
        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'contractor_id' => $request->string('contractor_id')->value() === 'none' ? 'none' : ($request->integer('contractor_id') ?: null),
            'action' => array_key_exists($action, self::ACTIONS) ? $action : null,
            'from' => $this->dateInput($request, 'from'),
            'to' => $this->dateInput($request, 'to'),
        ];

        $activity = ActivityLog::query()
            ->with(['user:id,name', 'contractor:id,company_name'])
            ->when($filters['search'] !== '', fn (Builder $query) => $query->where('description', 'like', '%'.$filters['search'].'%'))
            ->when($filters['contractor_id'] === 'none', fn (Builder $query) => $query->whereNull('contractor_id'))
            ->when(is_int($filters['contractor_id']), fn (Builder $query) => $query->where('contractor_id', $filters['contractor_id']))
            ->when($filters['action'], fn (Builder $query, string $action) => $query->where('action', $action))
            ->when($filters['from'], fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'], fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.activity.index', [
            'activity' => $activity,
            'filters' => $filters,
            'hasFilters' => collect($filters)->filter()->isNotEmpty(),
            'contractors' => Contractor::withTrashed()->orderBy('company_name')->pluck('company_name', 'id'),
        ]);
    }
}
