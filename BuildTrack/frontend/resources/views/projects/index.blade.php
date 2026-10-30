@extends('layouts.app')

@section('title', 'Projects')

@section('content')
@php
    $items = $projects['data'] ?? $projects['items'] ?? (is_array($projects) && !isset($projects['id']) ? array_values($projects) : []);
    $pagination = $projects['pagination'] ?? $projects['meta'] ?? null;
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-kanban me-2"></i>Projects</span>
        <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>New Project
        </a>
    </div>

    <div class="search-bar">
        <form method="GET" action="{{ route('projects.index') }}" id="searchForm" class="d-flex gap-2 align-items-center">
            <div class="input-group input-group-sm" style="max-width:380px;">
                <span class="input-group-text"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search projects…" value="{{ request('search') }}">
            </div>
            @if(request('search'))
                <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle me-1"></i>Reset
                </a>
            @endif
        </form>
    </div>

    @if($error)
        <div class="px-3 pt-3">
            <div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-2"></i>{{ $error }}</div>
        </div>
    @endif

    <div class="card-body p-0">
        @if(empty($items))
            <div class="empty-state">
                <i class="bi bi-kanban d-block"></i>
                <p>No projects found.</p>
                <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm">Create First Project</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Customer</th>
                            <th>Location</th>
                            <th>Start Date</th>
                            <th class="text-end">Budget</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $project)
                        <tr>
                            <td class="fw-semibold">
                                <a href="{{ route('projects.show', $project['id']) }}">{{ $project['projectName'] ?? '' }}</a>
                            </td>
                            <td>{{ $project['customerName'] ?? '—' }}</td>
                            <td>{{ $project['locationName'] ?? '—' }}</td>
                            <td>{{ isset($project['startDate']) ? date('d M Y', strtotime($project['startDate'])) : '—' }}</td>
                            <td class="text-end" style="font-variant-numeric:tabular-nums;">{{ isset($project['budget']) ? number_format((float)$project['budget'], 2) : '—' }}</td>
                            <td class="text-center" style="white-space:nowrap;">
                                <a href="{{ route('projects.edit', $project['id']) }}" class="btn-action me-1" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('projects.destroy', $project['id']) }}" method="POST" class="d-inline delete-form" data-confirm-message="Are you sure you want to delete this project?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action btn-action-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('_partials.pagination', ['pagination' => $pagination ?? ['total' => count($items)]])
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
let _st;
const _sf = document.getElementById('searchForm');
_sf.querySelector('input[name="search"]').addEventListener('input', function() {
    clearTimeout(_st);
    _st = setTimeout(() => _sf.submit(), 500);
});
</script>
@endsection
