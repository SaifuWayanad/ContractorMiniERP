<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionCatalogue;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Create the built-in roles. Existing roles keep any permission changes made in the admin portal.
     */
    public function run(PermissionCatalogue $catalogue): void
    {
        $roles = [
            [
                'slug' => Role::SUPER_ADMIN, 'name' => 'Super Admin', 'type' => RoleType::Admin, 'is_system' => true,
                'description' => 'Full access to the entire application.',
                'permissions' => $catalogue->forRoleType(RoleType::Admin),
            ],
            [
                'slug' => Role::ADMIN, 'name' => 'Admin', 'type' => RoleType::Admin, 'is_system' => false,
                'description' => 'Management user. Monitors contractors and their data; access is configurable.',
                'permissions' => $catalogue->forRoleType(RoleType::Admin),
            ],
            [
                'slug' => Role::CONTRACTOR_OWNER, 'name' => 'Contractor Owner', 'type' => RoleType::Contractor, 'is_system' => true,
                'description' => "Full access to the contractor's own business data and profile.",
                'permissions' => $catalogue->forRoleType(RoleType::Contractor),
            ],
            [
                'slug' => Role::CONTRACTOR_STAFF, 'name' => 'Contractor Staff', 'type' => RoleType::Contractor, 'is_system' => false,
                'description' => 'Team member who can view and update records but not delete them.',
                'permissions' => [
                    'dashboard.view', 'clients.view', 'clients.create', 'clients.edit',
                    'projects.view', 'projects.create', 'projects.edit',
                    'locations.view', 'locations.create', 'locations.edit',
                    'works.view', 'works.create', 'works.edit',
                    'dealers.view', 'profile.view', 'activity.view',
                ],
            ],
        ];

        foreach ($roles as $definition) {
            $role = Role::firstWhere('slug', $definition['slug']);

            if ($role === null) {
                $role = new Role(['name' => $definition['name'], 'description' => $definition['description']]);
                $role->slug = $definition['slug'];
                $role->type = $definition['type'];
                $role->is_system = $definition['is_system'];
                $role->save();
                $role->permissions()->sync(Permission::whereIn('name', $definition['permissions'])->pluck('id'));
            } elseif ($role->isSuperAdmin()) {
                $role->permissions()->sync(Permission::whereIn('name', $definition['permissions'])->pluck('id'));
            }
        }
    }
}
