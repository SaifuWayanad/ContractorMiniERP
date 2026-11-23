@extends('layouts.app')

@section('title', 'Employee Performance Report')

@section('content')
<x-page-header title="Employee Performance Report" subtitle="Work assignment and completion summary per employee." />

<form method="get" action="{{ route('reports.employee-performance') }}" class="mb-4" id="epFilterForm">
    <div class="row g-2 align-items-end">
        <div class="col-sm-6 col-md-3">
            <label class="form-label text-muted small mb-1">Employee</label>
            <div class="cs-wrapper cs-sm" id="epEmpWrapper">
                <div class="cs-trigger">
                    <span class="cs-value {{ $filters['employee_id'] ? '' : 'cs-placeholder' }}">{{ $filters['employee_id'] ? ($allEmployees[$filters['employee_id']] ?? 'All Employees') : 'All Employees' }}</span>
                    <i class="bi bi-chevron-down cs-arrow"></i>
                </div>
                <div class="cs-panel">
                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                    <div class="cs-list">
                        <div class="cs-item" data-value="">All Employees</div>
                        @foreach($allEmployees as $id => $name)
                            <div class="cs-item" data-value="{{ $id }}">{{ $name }}</div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" name="employee_id" id="epEmpInput" value="{{ $filters['employee_id'] ?? '' }}">
            </div>
        </div>
        @if($filters['employee_id'])
            <div class="col-auto">
                <a href="{{ route('reports.employee-performance') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        @endif
    </div>
</form>

@if($summary->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-people" title="No employees found" message="No employees to show performance for." />
    </div>
@else
    <x-table-card>
        <table class="table table-hover align-middle" style="border-collapse:separate;border-spacing:0">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th class="d-none d-md-table-cell">Designation</th>
                    <th class="text-center">Total Works</th>
                    <th class="text-center text-success">Completed</th>
                    <th class="text-center text-info">In Progress</th>
                    <th class="text-center text-warning">Pending</th>
                    <th class="text-center text-secondary">On Hold</th>
                </tr>
            </thead>
            <tbody>
                @foreach($summary as $row)
                <tr>
                    <td>
                        <a href="{{ route('employees.show', $row['employee']->id) }}" class="row-title">{{ $row['employee']->name }}</a>
                        <div class="cell-sub d-md-none text-muted small">{{ $row['employee']->designation }}</div>
                    </td>
                    <td class="d-none d-md-table-cell text-muted">{{ $row['employee']->designation ?? '—' }}</td>
                    <td class="text-center fw-semibold">{{ $row['total'] }}</td>
                    <td class="text-center"><span class="badge badge-soft-success">{{ $row['completed'] }}</span></td>
                    <td class="text-center"><span class="badge badge-soft-info">{{ $row['in_progress'] }}</span></td>
                    <td class="text-center"><span class="badge badge-soft-warning">{{ $row['pending'] }}</span></td>
                    <td class="text-center"><span class="badge badge-soft-secondary">{{ $row['on_hold'] }}</span></td>
                </tr>
                @endforeach
            </tbody>
            @if($summary->count() > 1)
            <tfoot>
                <tr class="fw-semibold">
                    <td colspan="2" style="border-top:2px solid #dee2e6">Total</td>
                    <td class="text-center" style="border-top:2px solid #dee2e6">{{ $summary->sum('total') }}</td>
                    <td class="text-center" style="border-top:2px solid #dee2e6"><span class="badge badge-soft-success">{{ $summary->sum('completed') }}</span></td>
                    <td class="text-center" style="border-top:2px solid #dee2e6"><span class="badge badge-soft-info">{{ $summary->sum('in_progress') }}</span></td>
                    <td class="text-center" style="border-top:2px solid #dee2e6"><span class="badge badge-soft-warning">{{ $summary->sum('pending') }}</span></td>
                    <td class="text-center" style="border-top:2px solid #dee2e6"><span class="badge badge-soft-secondary">{{ $summary->sum('on_hold') }}</span></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </x-table-card>
@endif

@push('scripts')
<script>
(function () {
    var form  = document.getElementById('epFilterForm');
    var cs    = new CustomSelect('epEmpWrapper');
    var input = document.getElementById('epEmpInput');
    var orig  = cs._select.bind(cs);
    cs._select = function (v, l) { orig(v, l); input.value = v; form.submit(); };
    if (input.value) cs._select(input.value, input.value);
})();
</script>
@endpush
@endsection
