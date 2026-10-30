@extends('layouts.app')

@section('title', 'User Management')

@section('content')
@php
    $items = $data['data'] ?? $data['items'] ?? (is_array($data) && !isset($data['id']) ? array_values($data) : []);
    $pagination = $data['pagination'] ?? $data['meta'] ?? null;
    $currentUserId = session('auth_user')['id'] ?? null;
@endphp

@if(!empty($error))
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>{{ $error }}</div>
@endif

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-people-fill me-2"></i>User Management</span>
        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Add User
        </a>
    </div>

    <div class="card-body border-bottom">
        <form method="GET" action="{{ route('users.index') }}" class="row g-2">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by name or username..." value="{{ $search ?? '' }}">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">Search</button>
                @if($search)
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary ms-1">Clear</a>
                @endif
            </div>
        </form>
    </div>

    @if(count($items) > 0)
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $u)
                @php $u = is_array($u) ? $u : []; @endphp
                <tr>
                    <td class="fw-semibold">{{ $u['fullName'] ?? '—' }}</td>
                    <td class="text-muted">{{ $u['username'] ?? '—' }}</td>
                    <td>{{ $u['email'] ?? '—' }}</td>
                    <td>
                        @if(($u['role'] ?? '') === 'Admin')
                            <span class="badge bg-primary">Admin</span>
                        @else
                            <span class="badge bg-success">Contractor</span>
                        @endif
                    </td>
                    <td>
                        @if($u['isActive'] ?? true)
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('users.edit', $u['id']) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        @if(($u['id'] ?? null) != $currentUserId && ($u['isActive'] ?? true))
                        <form action="{{ route('users.destroy', $u['id']) }}" method="POST" class="d-inline delete-form"
                              data-confirm-message="Deactivate {{ $u['fullName'] ?? $u['username'] ?? 'this user' }}? They will no longer be able to log in.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-person-x"></i> Deactivate
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($pagination)
        @include('_partials.pagination', ['pagination' => $pagination])
    @endif

    @else
    <div class="empty-state">
        <i class="bi bi-people text-muted"></i>
        <p class="text-muted mt-2 mb-0">
            @if($search)
                No users found matching "{{ $search }}".
            @else
                No users found.
            @endif
        </p>
    </div>
    @endif
</div>
@endsection
