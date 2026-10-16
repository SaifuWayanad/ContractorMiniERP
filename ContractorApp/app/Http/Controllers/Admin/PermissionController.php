<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\PermissionCatalogue;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Read-only matrix of permissions and the roles that hold them. Permissions are defined in
 * config/permissions.php; they are granted by editing roles.
 */
class PermissionController extends Controller
{
    public function __invoke(PermissionCatalogue $catalogue): View
    {
        Gate::authorize('viewAny', Role::class);

        $roles = Role::with('permissions:id,name')->orderBy('type')->orderBy('name')->get();

        return view('admin.permissions.index', [
            'groups' => $catalogue->groups(),
            'roles' => $roles,
            'grants' => $roles->mapWithKeys(fn (Role $role): array => [
                $role->id => $role->isSuperAdmin() ? $catalogue->all() : $role->permissions->pluck('name')->all(),
            ]),
        ]);
    }
}
