@php
    $user = auth()->user();

    $sections = $user->isAdmin()
        ? [
            null => [
                ['Dashboard', 'bi-speedometer2', 'admin.dashboard', 'admin.dashboard', 'dashboard.view'],
            ],
            'Management' => [
                ['Contractors', 'bi-briefcase', 'admin.contractors.index', 'admin.contractors.*', 'contractors.view'],
                ['Clients', 'bi-person-vcard', 'admin.clients.index', 'admin.clients.*', 'clients.view'],
                ['Projects', 'bi-building', 'admin.projects.index', 'admin.projects.*', 'projects.view'],
                ['Locations', 'bi-geo-alt', 'admin.locations.index', 'admin.locations.*', 'locations.view'],
                ['Works', 'bi-list-check', 'admin.works.index', 'admin.works.*', 'works.view'],
            ],
            'Access Control' => [
                ['Users', 'bi-people', 'admin.users.index', 'admin.users.*', 'users.view'],
                ['Roles', 'bi-shield-lock', 'admin.roles.index', 'admin.roles.*', 'roles.view'],
                ['Permissions', 'bi-key', 'admin.permissions.index', 'admin.permissions.*', 'roles.view'],
            ],
            'System' => [
                ['Notifications', 'bi-bell', 'admin.notifications.index', 'admin.notifications.*', 'notifications.view'],
                ['Activity', 'bi-clock-history', 'admin.activity.index', 'admin.activity.*', 'activity.view'],
                ['Settings', 'bi-gear', 'admin.settings.edit', 'admin.settings.*', 'settings.view'],
            ],
        ]
        : [
            'MAIN' => [
                ['Dashboard', 'bi-speedometer2', 'dashboard', 'dashboard', 'dashboard.view'],
            ],
            'WORK' => [
                ['Projects', 'bi-building', 'projects.index', 'projects.index|projects.create|projects.show|projects.edit', 'projects.view'],
                ['Customers', 'bi-person-vcard', 'clients.index', 'clients.*', 'clients.view'],
                ['Works', 'bi-list-check', 'works.index', 'works.*', 'works.view'],
            ],
            'VISITS' => [
                ['Visits', 'bi-calendar-check', 'visits.index', 'visits.*', 'visits.view'],
            ],
            'INVENTORY' => [
                ['Products', 'bi-box-seam', 'products.index', 'products.*', 'products.view'],
            ],
            'FINANCE' => [
                ['Purchases', 'bi-cart3', 'purchases.index', 'purchases.*', 'purchases.view'],
                ['Expenses', 'bi-receipt', 'expenses.index', 'expenses.*', 'expenses.view'],
                ['Payments', 'bi-cash-coin', 'payments.index', 'payments.*', 'payments.view'],
            ],
            'REPORTS' => [
                ['Project-wise', 'bi-bar-chart-line', 'reports.outstanding', 'reports.outstanding', 'reports.view'],
                ['Customer-wise', 'bi-people', 'reports.customer-outstanding', 'reports.customer-outstanding', 'reports.view'],
            ],
        ];
@endphp

<aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <a class="sidebar-brand" href="{{ $user->homeRoute() }}">
        <i class="bi bi-cone-striped" aria-hidden="true"></i>
        <span>{{ config('app.name') }}</span>
    </a>

    <nav class="sidebar-nav">
        @foreach ($sections as $heading => $items)
            @php($visible = array_filter($items, fn (array $item): bool => $user->can($item[4])))
            @if ($visible)
                @if ($heading)
                    <div class="sidebar-heading">{{ $heading }}</div>
                @endif
                @foreach ($visible as [$label, $icon, $route, $pattern, $permission])
                    @php($active = request()->routeIs(...explode('|', $pattern)))
                    <a @class(['sidebar-link', 'active' => $active]) href="{{ route($route) }}" @if ($active) aria-current="page" @endif>
                        <i class="bi {{ $icon }}" aria-hidden="true"></i>
                        <span>{{ $label }}</span>
                    </a>
                @endforeach
            @endif
        @endforeach
    </nav>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
