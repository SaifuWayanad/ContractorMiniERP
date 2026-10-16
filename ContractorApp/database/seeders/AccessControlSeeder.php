<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AccessControlSeeder extends Seeder
{
    /**
     * Permissions and built-in roles. Needed in every environment.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);
    }
}
