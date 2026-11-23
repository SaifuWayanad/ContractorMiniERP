@extends('layouts.app')

@section('title', 'Employees')

@section('content')
<x-page-header title="Employees" subtitle="Manage your workforce.">
    <x-slot:actions>
        @can('create', App\Models\Employee::class)
            <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Employee</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<form method="get" action="{{ route('employees.index') }}" class="mb-4" data-auto-filter>
    <div class="row g-2 align-items-end">
        <div class="col-sm-6 col-md-4">
            <input type="text" class="form-control form-control-sm" name="search" value="{{ $filters['search'] }}" placeholder="Search name, code, phone…" style="min-height:calc(1.5em + 0.5rem + 2px)" />
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="cs-wrapper cs-sm" id="empStatusWrapper">
                <div class="cs-trigger">
                    <span class="cs-value {{ $filters['status'] ? '' : 'cs-placeholder' }}">{{ $filters['status'] ?: 'All Statuses' }}</span>
                    <i class="bi bi-chevron-down cs-arrow"></i>
                </div>
                <div class="cs-panel">
                    <div class="cs-list">
                        <div class="cs-item" data-value="">All Statuses</div>
                        @foreach($statusOptions as $s)
                            <div class="cs-item" data-value="{{ $s }}">{{ $s }}</div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" name="status" id="empStatusInput" value="{{ $filters['status'] ?? '' }}">
            </div>
        </div>
        @if($filters['search'] || $filters['status'])
            <div class="col-auto">
                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        @endif
    </div>
</form>

@if($employees->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-people" title="No employees yet" message="Add employees to track attendance and salary payments.">
            @can('create', App\Models\Employee::class)
                <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">Add Employee</a>
            @endcan
        </x-empty-state>
    </div>
@else
    <x-table-card :paginator="$employees">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Name</th>
                    <th class="d-none d-sm-table-cell">Code</th>
                    <th class="d-none d-md-table-cell">Designation</th>
                    <th class="d-none d-md-table-cell">Phone</th>
                    <th>Status</th>
                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $employee)
                <tr>
                    <td>
                        <a href="{{ route('employees.show', $employee) }}" class="row-title">{{ $employee->name }}</a>
                        <div class="cell-sub d-md-none">{{ $employee->designation }}</div>
                    </td>
                    <td class="d-none d-sm-table-cell text-muted">{{ $employee->employee_code ?? '—' }}</td>
                    <td class="d-none d-md-table-cell">{{ $employee->designation ?? '—' }}</td>
                    <td class="d-none d-md-table-cell">{{ $employee->phone ?? '—' }}</td>
                    <td>
                        <span class="badge {{ App\Models\EmployeeStatusOption::badgeClass($employee->status ?? '') }}">{{ $employee->status ?? '—' }}</span>
                    </td>
                    <td class="text-end">
                        <div class="dropdown">
                            <button class="btn btn-icon btn-sm" data-bs-toggle="dropdown" aria-label="Actions"><i class="bi bi-three-dots-vertical"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @can('view', $employee)
                                    <li><a class="dropdown-item" href="{{ route('employees.show', $employee) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                @endcan
                                @can('update', $employee)
                                    <li><a class="dropdown-item" href="{{ route('employees.edit', $employee) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                @endcan
                                @can('delete', $employee)
                                    <li><hr class="dropdown-divider" /></li>
                                    <li><x-delete-button :action="route('employees.destroy', $employee)" class="dropdown-item text-danger" title="Delete employee?" confirm="Delete this employee? This cannot be undone." /></li>
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
    var cs = new CustomSelect('empStatusWrapper');
    var input = document.getElementById('empStatusInput');
    var orig = cs._select.bind(cs);
    cs._select = function(v, l) { orig(v, l); input.value = v; form.submit(); };
})();
</script>
@endpush
@endsection
