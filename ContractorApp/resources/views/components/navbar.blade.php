@php
    $user = auth()->user();
    $quickActions = $user->isContractorUser()
        ? array_filter([
            $user->can('create', App\Models\Client::class) ? ['Client', 'bi-person-plus', route('clients.create')] : null,
            $user->can('create', App\Models\Project::class) ? ['Project', 'bi-building-add', route('projects.create')] : null,
            $user->can('create', App\Models\ProjectLocation::class) ? ['Location', 'bi-geo-alt', route('locations.create')] : null,
            $user->can('create', App\Models\Work::class) ? ['Work', 'bi-list-check', route('works.create')] : null,
        ])
        : [];
    $searchRoute = $user->isAdmin()
        ? ($user->can('projects.view') ? route('admin.projects.index') : null)
        : ($user->can('projects.view') ? route('projects.index') : null);
@endphp

<header class="topbar">
    <button type="button" class="btn btn-icon d-lg-none" id="sidebarToggle" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false">
        <i class="bi bi-list"></i>
    </button>

    @if ($searchRoute)
        <form class="topbar-search" method="get" action="{{ $searchRoute }}" role="search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" name="search" class="form-control" placeholder="Search projects…" aria-label="Search projects" />
        </form>
    @endif

    <div class="topbar-actions">
        @if ($quickActions)
            <div class="dropdown">
                <button class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-plus-lg"></i><span class="d-none d-md-inline"> New</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @foreach ($quickActions as [$label, $icon, $url])
                        <li><a class="dropdown-item" href="{{ $url }}"><i class="bi {{ $icon }} me-2"></i>{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="dropdown">
            <button class="btn user-menu-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                <span class="avatar">{{ $user->initials() }}</span>
                <span class="user-menu-text d-none d-md-flex">
                    <span class="fw-semibold">{{ $user->name }}</span>
                    <span class="small text-muted">{{ $user->role?->name }}</span>
                </span>
                <i class="bi bi-chevron-down small text-muted d-none d-md-inline"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li class="dropdown-header">
                    <div class="fw-semibold text-body">{{ $user->name }}</div>
                    <div class="small">{{ $user->email }}</div>
                </li>
                <li><hr class="dropdown-divider" /></li>
                @if ($user->isContractorUser() && $user->can('profile.view'))
                    <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="bi bi-person-badge me-2"></i>My Profile</a></li>
                @endif
                <li><a class="dropdown-item" href="{{ route('account.password.edit') }}"><i class="bi bi-key me-2"></i>Change Password</a></li>
                <li><hr class="dropdown-divider" /></li>
                <li>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item" data-loading-text="Signing out…"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
