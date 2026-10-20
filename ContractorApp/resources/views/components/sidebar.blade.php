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
                ['Contacts', 'bi-shop', 'admin.dealers.index', 'admin.dealers.*', 'dealers.view'],
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
            null => [
                ['Dashboard', 'bi-speedometer2', 'dashboard', 'dashboard', 'dashboard.view'],
            ],
            'My Business' => [
                ['Customers', 'bi-person-vcard', 'clients.index', 'clients.*', 'clients.view'],
                ['Projects', 'bi-building', 'projects.index', 'projects.index|projects.create|projects.show|projects.edit', 'projects.view'],
                ['Contacts', 'bi-shop', 'dealers.index', 'dealers.*', 'dealers.view'],
                ['Work Types', 'bi-tools', 'work-types.index', 'work-types.*', 'projects.view'],
            ],
            'Account' => [
                ['My Profile', 'bi-person-badge', 'profile.show', 'profile.*', 'profile.view'],
            ],
        ];
@endphp

<aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <a class="sidebar-brand" href="{{ $user->homeRoute() }}">
        <i class="bi bi-cone-striped" aria-hidden="true"></i>
        <span>{{ config('app.name') }}</span>
        @if ($user->isAdmin())
            <span class="sidebar-portal-tag">Admin</span>
        @endif
    </a>

    @if ($user->contractor)
        <div class="sidebar-tenant" title="{{ $user->contractor->company_name }}">
            <span class="tenant-mark">{{ $user->contractor->initials() }}</span>
            <span class="text-truncate">{{ $user->contractor->company_name }}</span>
        </div>
    @endif

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
