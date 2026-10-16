@extends('layouts.app')

@section('title', 'Roles')

@section('content')
<x-page-header title="Roles" subtitle="What each type of user can do.">
    <x-slot:actions>
        @can('create', App\Models\Role::class)
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Role</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<x-table-card>
    <table class="table table-hover align-middle">
        <thead><tr><th>Role</th><th>Portal</th><th class="text-center">Permissions</th><th class="text-center">Users</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
        <tbody>
            @foreach ($roles as $role)
                <tr>
                    <td>
                        <span class="fw-semibold">{{ $role->name }}</span>
                        @if ($role->is_system) <span class="badge badge-soft-secondary">Built-in</span> @endif
                        @if ($role->description) <div class="cell-sub">{{ $role->description }}</div> @endif
                    </td>
                    <td><x-status-badge :status="$role->type" /></td>
                    <td class="text-center">{{ $role->isSuperAdmin() ? 'All' : $role->permissions_count }}</td>
                    <td class="text-center">{{ $role->users_count }}</td>
                    <td class="text-end text-nowrap">
                        @can('update', $role)
                            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-icon btn-sm" aria-label="Edit {{ $role->name }}"><i class="bi bi-pencil"></i></a>
                        @endcan
                        @can('delete', $role)
                            <x-delete-button :action="route('admin.roles.destroy', $role)" label="" class="btn btn-icon btn-sm text-danger" title="Delete this role?" :confirm="'Delete the “'.$role->name.'” role? This cannot be undone.'" button="Delete" />
                        @endcan
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-table-card>
<p class="small text-muted mt-2"><i class="bi bi-info-circle"></i> The Super Admin role always has every permission. Built-in roles can't be deleted, and roles still assigned to users can't be deleted.</p>
@endsection
