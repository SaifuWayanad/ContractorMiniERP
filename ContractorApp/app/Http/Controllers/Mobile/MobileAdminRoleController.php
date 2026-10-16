<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;

class MobileAdminRoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'is_system'])
            ->map(fn (Role $r) => [
                'id'                => $r->id,
                'name'              => $r->name,
                'type'              => $r->type->value,
                'is_system'         => $r->is_system,
                'users_count'       => $r->users_count,
                'permissions_count' => $r->permissions_count,
            ]);

        return response()->json($roles);
    }

    public function show(Role $role): JsonResponse
    {
        $role->load('permissions');

        $grouped = $role->permissions
            ->groupBy('group')
            ->map(fn ($perms) => $perms->map(fn ($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'description' => $p->description,
            ])->values())
            ->toArray();

        return response()->json([
            'id'          => $role->id,
            'name'        => $role->name,
            'type'        => $role->type->value,
            'is_system'   => $role->is_system,
            'users_count' => $role->users()->count(),
            'permissions' => $grouped,
        ]);
    }
}
