@extends('layouts.app')

@section('title', 'All Works')

@section('content')
<x-page-header title="All Works" subtitle="Tasks across every contractor. Read-only." />

<x-filter-bar :action="route('admin.works.index')" :has-filters="$hasFilters">
    <select name="project_id" class="form-select" aria-label="Project" data-autosubmit>
        <option value="">All projects</option>
        @foreach ($projects as $id => $name)
            <option value="{{ $id }}" @selected($filters['project_id'] === $id)>{{ $name }}</option>
        @endforeach
    </select>
    <select name="status" class="form-select" aria-label="Status" data-autosubmit>
        <option value="">All statuses</option>
        <option value="open" @selected($filters['status'] === 'open')>Pending (not done)</option>
        @foreach (App\Enums\WorkStatus::options() as $value => $label)
            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
        @endforeach
    </select>
</x-filter-bar>

@if ($works->isEmpty())
    <div class="card"><x-empty-state icon="bi-list-check" title="No works yet" message="Works appear here once contractors add them." /></div>
@else
    <x-table-card :paginator="$works">
        <table class="table table-hover align-middle">
            <thead><tr><th>Work</th><th>Contractor</th><th class="d-none d-md-table-cell">Project</th><th class="d-none d-lg-table-cell">Assigned to</th><th class="d-none d-sm-table-cell">Due</th><th class="d-none d-md-table-cell">Priority</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @foreach ($works as $work)
                    <tr>
                        <td class="fw-semibold">{{ $work->title }}</td>
                        <td>{{ $work->contractor?->company_name }}</td>
                        <td class="d-none d-md-table-cell">@if ($work->project)<a href="{{ route('admin.projects.show', $work->project) }}" class="text-decoration-none">{{ $work->project->name }}</a>@endif</td>
                        <td class="d-none d-lg-table-cell">{{ $work->assignee?->name ?? '—' }}</td>
                        <td class="d-none d-sm-table-cell text-nowrap">{{ $work->due_date?->format('d M Y') ?? '—' }} @if ($work->isOverdue())<span class="badge badge-soft-danger">Overdue</span>@endif</td>
                        <td class="d-none d-md-table-cell"><x-status-badge :status="$work->priority" /></td>
                        <td><x-status-badge :status="$work->status" /></td>
                        <td class="text-end">
                            @if ($work->project)
                                <a href="{{ route('admin.projects.show', $work->project) }}" class="btn btn-icon btn-sm" title="View project"><i class="bi bi-eye"></i></a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table-card>
@endif
@endsection
