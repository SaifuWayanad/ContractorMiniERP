@extends('layouts.app')

@section('title', 'Works')

@section('content')
<x-page-header title="Works" subtitle="Tasks across all your projects.">
    <x-slot:actions>
        @can('create', App\Models\Work::class)
            <a href="{{ route('works.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Work</a>
        @endcan
    </x-slot:actions>
</x-page-header>

{{-- Filters --}}
<form method="get" action="{{ route('works.index') }}" class="mb-4" data-auto-filter>
    <div class="row g-2 align-items-end">
        <div class="col-sm-4 col-md-3">
            <input type="text" class="form-control form-control-sm" name="search" value="{{ $filters['search'] }}" placeholder="Search works…" />
        </div>
        <div class="col-sm-4 col-md-3">
            <div class="cs-wrapper cs-sm" id="wrkClientWrapper">
                <div class="cs-trigger">
                    <span class="cs-value {{ $filters['client_id'] ? '' : 'cs-placeholder' }}">{{ $filters['client_id'] ? ($clients[$filters['client_id']] ?? 'All customers') : 'All customers' }}</span>
                    <i class="bi bi-chevron-down cs-arrow"></i>
                </div>
                <div class="cs-panel">
                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                    <div class="cs-list">
                        <div class="cs-item" data-value="">All customers</div>
                        @foreach($clients as $id => $name)
                            <div class="cs-item" data-value="{{ $id }}">{{ $name }}</div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" name="client_id" id="wrkClientInput" value="{{ $filters['client_id'] ?? '' }}">
            </div>
        </div>
        <div class="col-sm-4 col-md-3">
            <div class="cs-wrapper cs-sm" id="wrkProjectWrapper">
                <div class="cs-trigger">
                    <span class="cs-value {{ $filters['project_id'] ? '' : 'cs-placeholder' }}">{{ $filters['project_id'] ? ($projects[$filters['project_id']] ?? 'All projects') : 'All projects' }}</span>
                    <i class="bi bi-chevron-down cs-arrow"></i>
                </div>
                <div class="cs-panel">
                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                    <div class="cs-list">
                        <div class="cs-item" data-value="">All projects</div>
                        @foreach($projects as $id => $name)
                            <div class="cs-item" data-value="{{ $id }}">{{ $name }}</div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" name="project_id" id="wrkProjectInput" value="{{ $filters['project_id'] ?? '' }}">
            </div>
        </div>
        @if($filters['search'] || $filters['client_id'] || $filters['project_id'] || $filters['status'] || $filters['priority'] || $filters['due'])
        <div class="col-auto">
            <a href="{{ route('works.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
        @endif
    </div>
</form>

@if ($works->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-list-check" title="No works found" message="You haven't added any works yet.">
            @can('create', App\Models\Work::class)
                <a href="{{ route('works.create') }}" class="btn btn-primary btn-sm">Add Work</a>
            @endcan
        </x-empty-state>
    </div>
@else
    <x-table-card :paginator="$works">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Work</th>
                    <th class="d-none d-md-table-cell">Project</th>
                    <th class="d-none d-sm-table-cell">Due</th>
                    <th class="d-none d-lg-table-cell text-end">Amount</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($works as $work)
                    <tr>
                        <td>
                            <a href="{{ route('works.show', $work) }}" class="row-title">{{ $work->title }}</a>
                            <div class="cell-sub d-md-none">{{ $work->project?->name }}</div>
                        </td>
                        <td class="d-none d-md-table-cell">{{ $work->project?->name }}</td>
                        <td class="d-none d-sm-table-cell text-nowrap">
                            {{ $work->due_date?->format('d M Y') ?? '—' }}
                            @if ($work->isOverdue()) <div><span class="badge badge-soft-danger">Overdue</span></div> @endif
                        </td>
                        <td class="d-none d-lg-table-cell text-end">{{ $work->amount !== null ? '₹'.number_format((float)$work->amount, 2) : '—' }}</td>
                        <td><x-status-badge :status="$work->priority" /></td>
                        <td><x-status-badge :status="$work->status" /></td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-icon btn-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions for {{ $work->title }}"><i class="bi bi-three-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('works.show', $work) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                    @can('update', $work)
                                        @if ($work->status !== App\Enums\WorkStatus::Completed)
                                            <li>
                                                <form method="post" action="{{ route('works.status', $work) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="completed" />
                                                    <button type="submit" class="dropdown-item"><i class="bi bi-check2 me-2"></i>Mark completed</button>
                                                </form>
                                            </li>
                                        @endif
                                        <li><a class="dropdown-item" href="{{ route('works.edit', $work) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    @endcan
                                    @can('delete', $work)
                                        <li><hr class="dropdown-divider" /></li>
                                        <li><x-delete-button :action="route('works.destroy', $work)" class="dropdown-item text-danger" title="Delete this work?" :confirm="'Delete “'.$work->title.'”? This cannot be undone.'" /></li>
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
@push('scripts')
<script>
(function () {
    var form = document.querySelector('form[data-auto-filter]');
    function bindCs(wrapperId, inputId) {
        var cs = new CustomSelect(wrapperId);
        var input = document.getElementById(inputId);
        var orig = cs._select.bind(cs);
        cs._select = function(value, label) { orig(value, label); input.value = value; form.submit(); };
    }
    bindCs('wrkClientWrapper', 'wrkClientInput');
    bindCs('wrkProjectWrapper', 'wrkProjectInput');
})();
</script>
@endpush
@endsection
