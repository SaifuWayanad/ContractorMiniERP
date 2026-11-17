@extends('layouts.app')

@section('title', 'Employee Report')

@section('content')
<x-page-header title="Employee Report" subtitle="Attendance summary by employee and month." />

<form method="get" action="{{ route('reports.employees') }}" class="mb-4" data-auto-filter>
    <div class="row g-2 align-items-end">
        <div class="col-sm-6 col-md-3">
            <div class="cs-wrapper cs-sm" id="erMonthWrapper">
                <div class="cs-trigger">
                    <span class="cs-value {{ $filters['month'] ? '' : 'cs-placeholder' }}">{{ $months[$filters['month']] ?? 'Select Month' }}</span>
                    <i class="bi bi-chevron-down cs-arrow"></i>
                </div>
                <div class="cs-panel">
                    <div class="cs-list">
                        @foreach($months as $val => $label)
                            <div class="cs-item" data-value="{{ $val }}">{{ $label }}</div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" name="month" id="erMonthInput" value="{{ $filters['month'] }}">
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="cs-wrapper cs-sm" id="erEmpWrapper">
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
                <input type="hidden" name="employee_id" id="erEmpInput" value="{{ $filters['employee_id'] ?? '' }}">
            </div>
        </div>
    </div>
</form>

@if($summary->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-people" title="No employees found" message="Add employees to view attendance reports." />
    </div>
@else
    <x-table-card>
        <table class="table table-hover align-middle" style="border-collapse:separate;border-spacing:0">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th class="d-none d-md-table-cell">Designation</th>
                    <th class="text-center text-success">Present</th>
                    <th class="text-center text-danger">Absent</th>
                    <th class="text-center text-warning">Half Day</th>
                    <th class="text-center text-info">Leave</th>
                    <th class="text-center">Total</th>
                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
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
                    <td class="text-center"><span class="badge badge-soft-success">{{ $row['present'] }}</span></td>
                    <td class="text-center"><span class="badge badge-soft-danger">{{ $row['absent'] }}</span></td>
                    <td class="text-center"><span class="badge badge-soft-warning">{{ $row['half_day'] }}</span></td>
                    <td class="text-center"><span class="badge badge-soft-info">{{ $row['leave'] }}</span></td>
                    <td class="text-center fw-semibold">{{ $row['total_days'] }}</td>
                    <td class="text-end">
                        <a href="{{ route('attendance.index', ['employee_id' => $row['employee']->id]) }}" class="btn btn-icon btn-sm text-primary" title="View attendance"><i class="bi bi-calendar-week"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
            @if($summary->count() > 1)
            <tfoot>
                <tr class="fw-semibold">
                    <td colspan="2" style="border-top:2px solid #dee2e6">Total</td>
                    <td class="text-center" style="border-top:2px solid #dee2e6"><span class="badge badge-soft-success">{{ $summary->sum('present') }}</span></td>
                    <td class="text-center" style="border-top:2px solid #dee2e6"><span class="badge badge-soft-danger">{{ $summary->sum('absent') }}</span></td>
                    <td class="text-center" style="border-top:2px solid #dee2e6"><span class="badge badge-soft-warning">{{ $summary->sum('half_day') }}</span></td>
                    <td class="text-center" style="border-top:2px solid #dee2e6"><span class="badge badge-soft-info">{{ $summary->sum('leave') }}</span></td>
                    <td class="text-center" style="border-top:2px solid #dee2e6">{{ $summary->sum('total_days') }}</td>
                    <td style="border-top:2px solid #dee2e6"></td>
                </tr>
            </tfoot>
            @endif
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
        cs._select = function(v, l) { orig(v, l); input.value = v; form.submit(); };
        if (input.value) cs._select(input.value, input.value);
    }
    bindCs('erMonthWrapper', 'erMonthInput');
    bindCs('erEmpWrapper', 'erEmpInput');
})();
</script>
@endpush
@endsection
