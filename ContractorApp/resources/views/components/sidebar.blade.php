@php
    $user = auth()->user();
    $isEmployeePortal = $user->isContractorUser() && $user->isEmployeePortal();

    // Build employee portal sidebar from contractor's configured sections
    if ($isEmployeePortal) {
        $enabledSections = $user->contractor?->enabledPortalSections() ?? ['dashboard', 'projects', 'works'];
        $allPortalItems = [
            'dashboard'       => ['MAIN',      'Dashboard',       'bi-speedometer2',  'dashboard',          'dashboard',                                                    'dashboard.view'],
            'projects'        => ['WORK',       'Projects',        'bi-building',      'projects.index',     'projects.index|projects.create|projects.show|projects.edit',   'projects.view'],
            'works'           => ['WORK',       'Works',           'bi-list-check',    'works.index',        'works.*',                                                      'works.view'],
            'attendance'      => ['EMPLOYEES',  'Attendance',      'bi-calendar-check','attendance.calendar-view', 'attendance.*',                                            'attendance.view'],
            'salary_payments' => ['EMPLOYEES',  'Salary Payments', 'bi-cash-coin',     'salary-payments.index','salary-payments.*',                                          'salary_payments.view'],
            'visits'          => ['VISITS',     'Visits',          'bi-geo-alt',       'visits.index',       'visits.*',                                                     'visits.view'],
        ];
        $employeePortalSections = [];
        foreach ($enabledSections as $slug) {
            if (isset($allPortalItems[$slug])) {
                [$group, $label, $icon, $route, $pattern, $perm] = $allPortalItems[$slug];
                $employeePortalSections[$group][] = [$label, $icon, $route, $pattern, $perm];
            }
        }
    }

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
        : ($isEmployeePortal ? $employeePortalSections : [
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
                ['Collections', 'bi-cash-coin', 'payments.index', 'payments.*', 'payments.view'],
            ],
            'EMPLOYEES' => [
                ['Employees', 'bi-people-fill', 'employees.index', 'employees.*', 'employees.view'],
                ['Attendance', 'bi-calendar-check', 'attendance.index', 'attendance.*', 'attendance.view'],
                ['Salary Payments', 'bi-cash-coin', 'salary-payments.index', 'salary-payments.*', 'salary_payments.view'],
                ['Portal Settings', 'bi-toggles', 'employee-portal-settings.edit', 'employee-portal-settings.*', 'employees.view'],
            ],
            'REPORTS' => [
                ['Project-wise', 'bi-bar-chart-line', 'reports.outstanding', 'reports.outstanding', 'reports.view'],
                ['Customer-wise', 'bi-people', 'reports.customer-outstanding', 'reports.customer-outstanding', 'reports.view'],
                ['Collections', 'bi-cash-stack', 'reports.collections', 'reports.collections', 'reports.view'],
                ['Expenses', 'bi-graph-down-arrow', 'reports.expenses', 'reports.expenses', 'reports.view'],
                ['Attendance', 'bi-person-check', 'reports.employees', 'reports.employees', 'reports.view'],
                ['Performance', 'bi-trophy', 'reports.employee-performance', 'reports.employee-performance', 'reports.view'],
            ],
        ]);
@endphp

<aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <a class="sidebar-brand" href="{{ $user->homeRoute() }}">
        <i class="bi bi-cone-striped" aria-hidden="true"></i>
        <span>{{ config('app.name') }}</span>
    </a>

    <nav class="sidebar-nav">
        @foreach ($sections as $heading => $items)
            @php($visible = $isEmployeePortal
                ? $items  // contractor already decided which sections to show
                : array_filter($items, fn (array $item): bool => $user->can($item[4])))
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
