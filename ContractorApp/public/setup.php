<?php
// One-time admin setup script. DELETE this file after use.

if (($_GET['key'] ?? '') !== 'BM2026Setup') {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

header('Content-Type: application/json');

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Run migrations
    Artisan::call('migrate', ['--force' => true]);

    // Seed if roles table is empty
    if (DB::table('roles')->count() === 0) {
        Artisan::call('db:seed', ['--force' => true]);
    }

    // Find best admin role
    $role = DB::table('roles')
        ->where('name', 'Super Admin')
        ->orWhere('type', 'admin')
        ->orderBy('id')
        ->first();

    if (! $role) {
        die(json_encode(['error' => 'No roles found — seeder may have failed.']));
    }

    // Create or update admin user
    $existing = DB::table('users')->where('username', 'admin')->first();
    $hashed = password_hash('Admin@1234', PASSWORD_BCRYPT);

    if ($existing) {
        DB::table('users')->where('username', 'admin')->update([
            'name'       => 'Administrator',
            'email'      => 'admin@buildmate.tech',
            'password'   => $hashed,
            'status'     => 'active',
            'role_id'    => $role->id,
            'updated_at' => now(),
        ]);
        $action = 'updated';
    } else {
        DB::table('users')->insert([
            'name'       => 'Administrator',
            'username'   => 'admin',
            'email'      => 'admin@buildmate.tech',
            'password'   => $hashed,
            'status'     => 'active',
            'role_id'    => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $action = 'created';
    }

    echo json_encode([
        'success'  => true,
        'action'   => $action,
        'username' => 'admin',
        'password' => 'Admin@1234',
        'role'     => $role->name,
        'message'  => 'Done! Delete public/setup.php now.',
    ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
