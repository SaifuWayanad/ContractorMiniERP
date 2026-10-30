<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Creates the first admin only when there are no users, so re-running is safe.
     * Set ADMIN_PASSWORD to choose the password; otherwise a random one is printed once.
     */
    public function run(): void
    {
        if (User::exists()) {
            return;
        }

        $password = env('ADMIN_PASSWORD') ?: Str::random(20);

        User::create([
            'username' => 'admin',
            'password_hash' => Hash::make($password),
            'full_name' => 'Administrator',
            'email' => 'admin@buildtrack.local',
            'role' => User::ADMIN,
            'is_active' => true,
        ]);

        $this->command?->warn("Created admin user 'admin'. Password: {$password}");
    }
}
