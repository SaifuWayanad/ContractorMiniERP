@extends('layouts.app')

@section('title', 'Users')

@section('content')
<x-page-header title="Users" subtitle="Admin users and contractor logins.">
    <x-slot:actions>
        @can('create', App\Models\User::class)
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add User</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<x-filter-bar :action="route('admin.users.index')" :has-filters="$hasFilters">
    <x-search-input :value="$filters['search']" placeholder="Search name, username or email" />
    <select name="role_id" class="form-select" aria-label="Role" data-autosubmit>
        <option value="">All roles</option>
        @foreach ($roles as $id => $name)
            <option value="{{ $id }}" @selected($filters['role_id'] === $id)>{{ $name }}</option>
        @endforeach
    </select>
</x-filter-bar>

@if ($users->isEmpty())
    <div class="card"><x-empty-state icon="bi-people" :title="$hasFilters ? 'No users match your filters' : 'No users yet'" :message="$hasFilters ? 'Try a different search or clear the filters.' : 'Add a user to get started.'" /></div>
@else
    <x-table-card :paginator="$users">
        <table class="table table-hover align-middle">
            <thead><tr><th>Name</th><th class="d-none d-md-table-cell">Email</th><th>Role</th><th class="d-none d-lg-table-cell">Contractor</th><th>Status</th><th class="d-none d-xl-table-cell">Last sign-in</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @foreach ($users as $member)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar-sm">{{ $member->initials() }}</span>
                                <div class="min-w-0"><div class="fw-semibold">{{ $member->name }}@if ($member->is(auth()->user())) <span class="badge badge-soft-secondary">You</span>@endif</div><div class="cell-sub d-md-none">{{ $member->email }}</div></div>
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell">{{ $member->email }}</td>
                        <td>{{ $member->role?->name ?? '—' }}</td>
                        <td class="d-none d-lg-table-cell">{{ $member->contractor?->company_name ?? '—' }}</td>
                        <td><x-status-badge :status="$member->status" /></td>
                        <td class="d-none d-xl-table-cell text-nowrap">{{ $member->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                        <td class="text-end">
                            @canany(['update', 'delete'], $member)
                                <div class="dropdown">
                                    <button class="btn btn-icon btn-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions for {{ $member->name }}"><i class="bi bi-three-dots-vertical"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @can('update', $member)
                                            <li><a class="dropdown-item" href="{{ route('admin.users.edit', $member) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                            @unless ($member->is(auth()->user()))
                                                <li>
                                                    @if ($member->status === App\Enums\UserStatus::Active)
                                                        <x-delete-button :action="route('admin.users.status', $member)" method="PATCH" :fields="['status' => 'inactive']" label="Deactivate" icon="bi-pause-circle" variant="warning" loading-text="Updating…" class="dropdown-item" title="Deactivate this user?" :confirm="'“'.$member->name.'” will be signed out and unable to sign in. Their records are kept.'" button="Deactivate" />
                                                    @else
                                                        <x-delete-button :action="route('admin.users.status', $member)" method="PATCH" :fields="['status' => 'active']" label="Activate" icon="bi-play-circle" variant="success" loading-text="Updating…" class="dropdown-item" title="Activate this user?" :confirm="'“'.$member->name.'” will be able to sign in again.'" button="Activate" />
                                                    @endif
                                                </li>
                                            @endunless
                                        @endcan
                                        @can('delete', $member)
                                            <li><hr class="dropdown-divider" /></li>
                                            <li><x-delete-button :action="route('admin.users.destroy', $member)" class="dropdown-item text-danger" title="Delete this user?" :confirm="'Delete “'.$member->name.'”? They will no longer be able to sign in. Records they created are kept.'" /></li>
                                        @endcan
                                    </ul>
                                </div>
                            @endcanany
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table-card>
@endif
@endsection
