<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContractorStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContractorRequest;
use App\Models\ActivityLog;
use App\Models\Contractor;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\DashboardService;
use App\Services\ReportingPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ContractorController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Contractor::class);

        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'status' => ContractorStatus::tryFrom($request->string('status')->value())?->value,
            'from' => $this->dateInput($request, 'from'),
            'to' => $this->dateInput($request, 'to'),
        ];

        $contractors = Contractor::query()
            ->withCount([
                'clients',
                'projects',
                'projects as active_projects_count' => fn (Builder $query) => $query->where('status', ProjectStatus::Active),
            ])
            ->when($filters['search'] !== '', fn (Builder $query) => $query->search($filters['search']))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['from'], fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'], fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->orderByDesc('id')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('admin.contractors.index', [
            'contractors' => $contractors,
            'filters' => $filters,
            'hasFilters' => collect($filters)->filter()->isNotEmpty(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Contractor::class);

        $contractor = new Contractor(['country' => 'India']);
        $contractor->status = ContractorStatus::Active;

        return view('admin.contractors.create', ['contractor' => $contractor]);
    }

    public function store(ContractorRequest $request, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('create', Contractor::class);

        $contractor = DB::transaction(function () use ($request, $activity): Contractor {
            $contractor = new Contractor($request->safe()->except(['status', 'create_user', 'user_name', 'user_password', 'user_password_confirmation']));
            $contractor->status = ContractorStatus::tryFrom($request->string('status')->value()) ?? ContractorStatus::Active;
            $contractor->save();

            if ($request->boolean('create_user')) {
                $ownerName = $request->string('user_name')->value();
                $user = new User([
                    'name' => $ownerName,
                    'username' => strtolower(str_replace(' ', '.', $ownerName)),
                    'password' => $request->string('user_password')->value(),
                ]);
                $user->contractor_id = $contractor->id;
                $user->role_id = Role::where('slug', Role::CONTRACTOR_OWNER)->value('id');
                $user->status = UserStatus::Active;
                $user->save();

                $activity->record('created', "{$request->user()->name} created user \"{$user->name}\" for {$contractor->company_name}", $user, contractorId: $contractor->id);
            }

            return $contractor;
        });

        return to_route('admin.contractors.index')->with('toast', "Contractor \"{$contractor->company_name}\" created.");
    }

    public function show(Contractor $contractor, DashboardService $dashboard): View
    {
        $this->authorize('view', $contractor);

        $period = ReportingPeriod::fromRequest(new Request(['period' => 'month']));

        return view('admin.contractors.show', [
            'contractor' => $contractor,
            'summary' => $dashboard->summary($contractor->id, $period),
            'clients' => $contractor->clients()->withCount('projects')->latest()->paginate(10, ['*'], 'clients_page')->withQueryString(),
            'projects' => $contractor->projects()->with('client:id,name')->latest()->paginate(10, ['*'], 'projects_page')->withQueryString(),
            'locations' => $contractor->projectLocations()->with('project:id,name')->latest()->paginate(10, ['*'], 'locations_page')->withQueryString(),
            'works' => $contractor->works()->with('project:id,name')->open()->orderByRaw('due_date is null')->orderBy('due_date')->paginate(10, ['*'], 'works_page')->withQueryString(),
            'users' => $contractor->users()->with('role:id,name')->orderBy('name')->get(),
            'activity' => ActivityLog::where('contractor_id', $contractor->id)->with('user:id,name')->latest()->orderByDesc('id')->limit(15)->get(),
        ]);
    }

    public function edit(Contractor $contractor): View
    {
        $this->authorize('update', $contractor);

        return view('admin.contractors.edit', ['contractor' => $contractor]);
    }

    public function update(ContractorRequest $request, Contractor $contractor): RedirectResponse
    {
        $this->authorize('update', $contractor);

        $contractor->fill($request->safe()->except(['status', 'create_user', 'user_name', 'user_password', 'user_password_confirmation']));
        $contractor->status = ContractorStatus::from($request->string('status')->value());
        $contractor->save();

        return to_route('admin.contractors.show', $contractor)->with('toast', "Contractor \"{$contractor->company_name}\" updated.");
    }

    /**
     * Archives (soft-deletes) the contractor. Its data is kept and its users can no longer sign in.
     */
    public function destroy(Contractor $contractor): RedirectResponse
    {
        $this->authorize('delete', $contractor);

        $contractor->delete();

        return to_route('admin.contractors.index')->with('toast', "Contractor \"{$contractor->company_name}\" archived. Its data has been kept.");
    }
}
