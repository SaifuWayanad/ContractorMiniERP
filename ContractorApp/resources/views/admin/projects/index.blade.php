@extends('layouts.app')

@section('title', 'All Projects')

@section('content')
<x-page-header title="All Projects" subtitle="Projects across every contractor.">
    <x-slot:actions>
        @can('create', App\Models\Project::class)
            <a href="{{ route('admin.projects.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Project</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<x-filter-bar :action="route('admin.projects.index')" :has-filters="$hasFilters">
    <select name="client_id" class="form-select" aria-label="Customer" data-autosubmit>
        <option value="">All customers</option>
        @foreach ($clients as $id => $name)
            <option value="{{ $id }}" @selected($filters['client_id'] === $id)>{{ $name }}</option>
        @endforeach
    </select>
    <select name="status" class="form-select" aria-label="Status" data-autosubmit>
        <option value="">All statuses</option>
        @foreach (App\Enums\ProjectStatus::options() as $value => $label)
            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
        @endforeach
    </select>
</x-filter-bar>

@if ($projects->isEmpty())
    <div class="card"><x-empty-state icon="bi-building" title="No projects yet" message="Projects appear here once contractors create them." /></div>
@else
    <x-table-card :paginator="$projects">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Contractor</th>
                    <th class="d-none d-md-table-cell">Customer</th>
                    <th class="d-none d-lg-table-cell">Start</th>
                    <th>Status</th>
                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($projects as $project)
                    <tr>
                        <td><a href="{{ route('admin.projects.show', $project) }}" class="row-title">{{ $project->name }}</a><div class="cell-sub">{{ $project->project_number }}</div></td>
                        <td>{{ $project->contractor?->company_name }}</td>
                        <td class="d-none d-md-table-cell">{{ $project->client?->name }}</td>
                        <td class="d-none d-lg-table-cell text-nowrap">{{ $project->start_date?->format('d M Y') ?? '—' }}</td>
                        <td><x-status-badge :status="$project->status" /></td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-icon btn-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions for {{ $project->name }}"><i class="bi bi-three-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('admin.projects.show', $project) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.projects.edit', $project) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    <li><hr class="dropdown-divider" /></li>
                                    <li>
                                        <x-delete-button :action="route('admin.projects.destroy', $project)" title="Delete project?" :confirm="$project->name.' will be permanently deleted.'" class="dropdown-item text-danger" />
                                    </li>
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
