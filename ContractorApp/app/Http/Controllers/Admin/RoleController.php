<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\ActivityLogger;
use App\Services\PermissionCatalogue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(private PermissionCatalogue $catalogue) {}

    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        return view('admin.roles.index', [
            'roles' => Role::withCount(['users', 'permissions'])->orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Role::class);

        $role = new Role;
        $role->type = RoleType::tryFrom($request->string('type')->value()) ?? RoleType::Contractor;

        return view('admin.roles.create', $this->formData($role, []));
    }

    public function store(RoleRequest $request, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $role = new Role($request->safe()->only(['name', 'description']));
        $role->type = RoleType::from($request->string('type')->value());
        $role->slug = Str::slug($role->name).'-'.Str::lower(Str::random(4));
        $role->is_system = false;
        $role->save();
        $role->permissions()->sync(Permission::whereIn('name', $request->validated('permissions', []))->pluck('id'));

        $activity->record('created', "{$request->user()->name} created role \"{$role->name}\"", $role);

        return to_route('admin.roles.index')->with('toast', "Role \"{$role->name}\" created.");
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        return view('admin.roles.edit', $this->formData($role, $role->permissions()->pluck('name')->all()));
    }

    public function update(RoleRequest $request, Role $role, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('update', $role);

        $role->update($request->safe()->only(['name', 'description']));
        $role->permissions()->sync(Permission::whereIn('name', $request->validated('permissions', []))->pluck('id'));

        $activity->record('updated', "{$request->user()->name} updated role \"{$role->name}\" and its permissions", $role);

        return to_route('admin.roles.index')->with('toast', "Role \"{$role->name}\" updated.");
    }

    public function destroy(Request $request, Role $role, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('delete', $role);

        $role->delete();
        $activity->record('deleted', "{$request->user()->name} deleted role \"{$role->name}\"");

        return to_route('admin.roles.index')->with('toast', "Role \"{$role->name}\" deleted.");
    }

    /**
     * @param  list<string>  $selected
     * @return array<string, mixed>
     */
    private function formData(Role $role, array $selected): array
    {
        return [
            'role' => $role,
            'groups' => $this->catalogue->groups(),
            'selected' => old('permissions', $selected),
        ];
    }
}
