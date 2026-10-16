<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Services\PermissionCatalogue;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Sync the permissions table with config/permissions.php. Safe to run repeatedly.
     */
    public function run(PermissionCatalogue $catalogue): void
    {
        foreach ($catalogue->groups() as $key => $group) {
            foreach ($group['abilities'] as $ability) {
                Permission::updateOrCreate(
                    ['name' => "{$key}.{$ability}"],
                    ['group' => $key, 'description' => ucfirst($ability).' '.mb_strtolower($group['label'])],
                );
            }
        }

        Permission::whereNotIn('name', $catalogue->all())->delete();
    }
}
