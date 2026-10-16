@extends('layouts.app')

@section('title', 'Contractors')

@section('content')
<x-page-header title="Contractors" subtitle="Every contractor account on the platform.">
    <x-slot:actions>
        @can('create', App\Models\Contractor::class)
            <a href="{{ route('admin.contractors.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Contractor</a>
        @endcan
    </x-slot:actions>
</x-page-header>

@if ($contractors->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-briefcase" title="No contractors yet" message="Add your first contractor to get started.">
            @can('create', App\Models\Contractor::class)
                <a href="{{ route('admin.contractors.create') }}" class="btn btn-primary btn-sm">Add Contractor</a>
            @endcan
        </x-empty-state>
    </div>
@else
    <x-table-card :paginator="$contractors">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Contractor</th>
                    <th class="d-none d-lg-table-cell">Contact Person</th>
                    <th class="d-none d-xl-table-cell">Email</th>
                    <th class="d-none d-xxl-table-cell">Phone</th>
                    <th class="text-center d-none d-md-table-cell">Clients</th>
                    <th class="text-center">Projects</th>
                    <th class="text-center d-none d-md-table-cell">Active</th>
                    <th>Status</th>
                    <th class="d-none d-lg-table-cell">Created</th>
                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($contractors as $contractor)
                    <tr>
                        <td>
                            <a href="{{ route('admin.contractors.show', $contractor) }}" class="row-title">{{ $contractor->company_name }}</a>
                            <div class="cell-sub">{{ collect([$contractor->city, $contractor->state])->filter()->implode(', ') }}</div>
                        </td>
                        <td class="d-none d-lg-table-cell">{{ $contractor->contact_person }}</td>
                        <td class="d-none d-xl-table-cell"><a href="mailto:{{ $contractor->email }}" class="text-decoration-none">{{ $contractor->email }}</a></td>
                        <td class="d-none d-xxl-table-cell text-nowrap">{{ $contractor->phone }}</td>
                        <td class="text-center d-none d-md-table-cell">{{ $contractor->clients_count }}</td>
                        <td class="text-center">{{ $contractor->projects_count }}</td>
                        <td class="text-center d-none d-md-table-cell">{{ $contractor->active_projects_count }}</td>
                        <td><x-status-badge :status="$contractor->status" /></td>
                        <td class="d-none d-lg-table-cell text-nowrap">{{ $contractor->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-icon btn-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions for {{ $contractor->company_name }}"><i class="bi bi-three-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('admin.contractors.show', $contractor) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                    @can('update', $contractor)
                                        <li><a class="dropdown-item" href="{{ route('admin.contractors.edit', $contractor) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    @endcan
                                    @can('delete', $contractor)
                                        <li><hr class="dropdown-divider" /></li>
                                        <li>
                                            <x-delete-button :action="route('admin.contractors.destroy', $contractor)" title="Delete contractor?" :confirm="$contractor->company_name.' and all its data will be archived.'" class="dropdown-item text-danger" />
                                        </li>
                                    @endcan
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table-card>
@endif
@endsection
