@extends('layouts.app')

@section('title', 'Projects')

@use('Illuminate\Support\Number')

@section('content')
<x-page-header title="Projects" subtitle="All your jobs, by client, status and schedule.">
    <x-slot:actions>
        @can('create', App\Models\Project::class)
            <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Create Project</a>
        @endcan
    </x-slot:actions>
</x-page-header>

@if ($projects->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-building" title="No projects found" message="You haven't created any projects yet.">
            @can('create', App\Models\Project::class)
                <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm">Create Project</a>
            @endcan
        </x-empty-state>
    </div>
@else
    <x-table-card :paginator="$projects">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Project</th>
                    <th class="d-none d-md-table-cell">Client</th>
                    <th class="d-none d-xl-table-cell">Location</th>
                    <th class="d-none d-lg-table-cell">Schedule</th>
                    <th class="d-none d-xxl-table-cell text-end">Budget</th>
                    <th class="d-none d-xl-table-cell">Maturity</th>
                    <th>Status</th>
                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($projects as $project)
                    @php($maturity = $project->maturityStatus())
                    <tr>
                        <td>
                            <a href="{{ route('projects.show', $project) }}" class="row-title">{{ $project->name }}</a>
                            <div class="cell-sub">{{ $project->project_number }}@if ($project->type_of_work) · {{ $project->type_of_work }}@endif</div>
                            <div class="cell-sub d-md-none">{{ $project->client?->name }}</div>
                        </td>
                        <td class="d-none d-md-table-cell">{{ $project->client?->name }}</td>
                        <td class="d-none d-xl-table-cell">{{ $project->primaryLocation?->shortAddress() ?? '—' }}</td>
                        <td class="d-none d-lg-table-cell text-nowrap">
                            <div class="small">{{ $project->start_date?->format('d M Y') ?? '—' }} → {{ $project->expected_end_date?->format('d M Y') ?? '—' }}</div>
                            @if ($project->isOverdue()) <span class="badge badge-soft-danger">Overdue</span> @endif
                        </td>
                        <td class="d-none d-xxl-table-cell text-end text-nowrap">{{ $project->budget !== null ? Number::currency((float) $project->budget, 'INR', 'en_IN', 0) : '—' }}</td>
                        <td class="d-none d-xl-table-cell text-nowrap">
                            <div class="small">{{ $project->expected_maturity_date?->format('d M Y') ?? '—' }}</div>
                            @if ($project->expected_maturity_date) <span class="badge {{ $maturity['class'] }}">{{ $maturity['label'] }}</span> @endif
                        </td>
                        <td><x-status-badge :status="$project->status" /></td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-icon btn-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions for {{ $project->name }}"><i class="bi bi-three-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('projects.show', $project) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                    @can('update', $project)
                                        <li><a class="dropdown-item" href="{{ route('projects.edit', $project) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                        <li><hr class="dropdown-divider" /></li>
                                        <li><h6 class="dropdown-header">Change status</h6></li>
                                        @foreach (App\Enums\ProjectStatus::cases() as $status)
                                            @continue($status === $project->status)
                                            <li>
                                                <form method="post" action="{{ route('projects.status', $project) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="{{ $status->value }}" />
                                                    <button type="submit" class="dropdown-item"><span class="badge {{ $status->badgeClass() }}">{{ $status->label() }}</span></button>
                                                </form>
                                            </li>
                                        @endforeach
                                        <li><hr class="dropdown-divider" /></li>
                                    @endcan
                                    @can('create', App\Models\ProjectLocation::class)
                                        <li><a class="dropdown-item" href="{{ route('projects.locations.create', $project) }}"><i class="bi bi-geo-alt me-2"></i>Add location</a></li>
                                    @endcan
                                    @can('create', App\Models\Work::class)
                                        <li><a class="dropdown-item" href="{{ route('works.create', ['project_id' => $project->id]) }}"><i class="bi bi-list-check me-2"></i>Add work</a></li>
                                    @endcan
                                    @can('delete', $project)
                                        <li><hr class="dropdown-divider" /></li>
                                        <li><x-delete-button :action="route('projects.destroy', $project)" class="dropdown-item text-danger" title="Delete this project?" :confirm="'Delete “'.$project->name.'” with its locations and works? This cannot be undone.'" /></li>
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
