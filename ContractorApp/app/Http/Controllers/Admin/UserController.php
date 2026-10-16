<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContractorStatus;
use App\Enums\RoleType;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Contractor;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'role_id' => $request->integer('role_id') ?: null,
            'contractor_id' => $request->string('contractor_id')->value() === 'none' ? 'none' : ($request->integer('contractor_id') ?: null),
            'status' => UserStatus::tryFrom($request->string('status')->value())?->value,
        ];

        $users = User::query()
            ->with(['role:id,name,type', 'contractor:id,company_name'])
            ->when($filters['search'] !== '', fn (Builder $query) => $query->search($filters['search']))
            ->when($filters['role_id'], fn (Builder $query, int $id) => $query->where('role_id', $id))
            ->when($filters['contractor_id'] === 'none', fn (Builder $query) => $query->whereNull('contractor_id'))
            ->when(is_int($filters['contractor_id']), fn (Builder $query) => $query->where('contractor_id', $filters['contractor_id']))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $filters,
            'hasFilters' => $filters['search'] !== '' || $filters['role_id'],
            'roles' => Role::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', User::class);

        $user = new User;
        $user->status = UserStatus::Active;
        $user->contractor_id = $request->integer('contractor_id') ?: null;

        return view('admin.users.create', $this->formData($user));
    }

    public function store(UserRequest $request, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = DB::transaction(function () use ($request): User {
            $user = new User($request->safe()->only(['name', 'username', 'email', 'phone', 'password']));
            $this->assignAccess($user, $request);

            $role = Role::find($user->role_id);
            if ($role?->type === RoleType::Contractor && $request->filled('company_name')) {
                $user->contractor_id = $this->createContractor($request->string('company_name')->value(), $user)->id;
            }

            $user->save();

            return $user;
        });

        $activity->record('created', "{$request->user()->name} created user \"{$user->name}\"", $user, contractorId: $user->contractor_id);

        return to_route('admin.users.index')->with('toast', "User \"{$user->name}\" created.");
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', $this->formData($user));
    }

    public function update(UserRequest $request, User $user, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->fill($request->safe()->only(['name', 'email', 'phone']));
        if ($request->filled('password')) {
            $user->password = $request->string('password')->value();
        }
        $this->assignAccess($user, $request);

        $role = Role::find($user->role_id);
        if ($role?->type === RoleType::Contractor && $user->contractor_id === null && $request->filled('company_name')) {
            $user->contractor_id = $this->createContractor($request->string('company_name')->value(), $user)->id;
        }

        $user->save();

        $activity->record('updated', "{$request->user()->name} updated user \"{$user->name}\"", $user, contractorId: $user->contractor_id);

        return to_route('admin.users.index')->with('toast', "User \"{$user->name}\" updated.");
    }

    public function destroy(Request $request, User $user, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();
        $activity->record('deleted', "{$request->user()->name} deleted user \"{$user->name}\"", $user, contractorId: $user->contractor_id);

        return to_route('admin.users.index')->with('toast', "User \"{$user->name}\" deleted.");
    }

    private function createContractor(string $companyName, User $user): Contractor
    {
        $contractor = new Contractor([
            'company_name' => $companyName,
            'contact_person' => $user->name,
            'email' => (string) $user->email,
            'phone' => (string) $user->phone,
        ]);
        $contractor->status = ContractorStatus::Active;
        $contractor->save();

        return $contractor;
    }

    /**
     * Role, contractor and status are set explicitly (never mass assigned).
     */
    private function assignAccess(User $user, UserRequest $request): void
    {
        $user->role_id = $request->integer('role_id');
        $user->status = $request->filled('status') ? UserStatus::from($request->string('status')->value()) : UserStatus::Active;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(User $user): array
    {
        $actor = auth()->user();

        return [
            'user' => $user,
            'roles' => Role::query()
                ->when(! $actor->isSuperAdmin(), fn (Builder $query) => $query->where('slug', '!=', Role::SUPER_ADMIN))
                ->orderBy('type')
                ->orderBy('name')
                ->get(['id', 'name', 'type']),
            'contractors' => Contractor::orderBy('company_name')->pluck('company_name', 'id'),
        ];
    }
}
