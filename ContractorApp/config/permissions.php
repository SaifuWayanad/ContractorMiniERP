<?php

/*
|--------------------------------------------------------------------------
| Permission catalogue
|--------------------------------------------------------------------------
|
| Every permission the application checks. Each group lists its abilities
| and the portals whose roles may be granted them: "admin" roles belong to
| internal users, "contractor" roles to users of a contractor. Run
| `php artisan db:seed --class=PermissionSeeder` after changing this file.
|
*/

return [
    'groups' => [
        'dashboard' => ['label' => 'Dashboard', 'abilities' => ['view'], 'portals' => ['admin', 'contractor']],
        'contractors' => ['label' => 'Contractors', 'abilities' => ['view', 'create', 'edit', 'delete'], 'portals' => ['admin']],
        'clients' => ['label' => 'Clients', 'abilities' => ['view', 'create', 'edit', 'delete'], 'portals' => ['admin', 'contractor']],
        'projects' => ['label' => 'Projects', 'abilities' => ['view', 'create', 'edit', 'delete'], 'portals' => ['admin', 'contractor']],
        'locations' => ['label' => 'Project Locations', 'abilities' => ['view', 'create', 'edit', 'delete'], 'portals' => ['admin', 'contractor']],
        'works' => ['label' => 'Works', 'abilities' => ['view', 'create', 'edit', 'delete'], 'portals' => ['admin', 'contractor']],
        'products' => ['label' => 'Products', 'abilities' => ['view', 'create', 'edit', 'delete'], 'portals' => ['contractor']],
        'purchases' => ['label' => 'Purchases', 'abilities' => ['view', 'create', 'edit', 'delete'], 'portals' => ['contractor']],
        'expenses' => ['label' => 'Expenses', 'abilities' => ['view', 'create', 'edit', 'delete'], 'portals' => ['contractor']],
        'payments' => ['label' => 'Payments Received', 'abilities' => ['view', 'create', 'edit', 'delete'], 'portals' => ['contractor']],
        'visits' => ['label' => 'Visits', 'abilities' => ['view', 'create', 'edit', 'delete'], 'portals' => ['contractor']],
        'profile' => ['label' => 'Contractor Profile', 'abilities' => ['view', 'edit'], 'portals' => ['contractor']],
        'reports' => ['label' => 'Reports', 'abilities' => ['view'], 'portals' => ['admin', 'contractor']],
        'activity' => ['label' => 'Activity', 'abilities' => ['view'], 'portals' => ['admin', 'contractor']],
        'users' => ['label' => 'Users', 'abilities' => ['view', 'create', 'edit', 'delete'], 'portals' => ['admin']],
        'roles' => ['label' => 'Roles & Permissions', 'abilities' => ['view', 'create', 'edit', 'delete'], 'portals' => ['admin']],
        'notifications' => ['label' => 'Notifications', 'abilities' => ['view', 'send'], 'portals' => ['admin']],
        'settings' => ['label' => 'Settings', 'abilities' => ['view', 'edit'], 'portals' => ['admin']],
    ],
];
