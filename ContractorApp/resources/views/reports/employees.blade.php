@extends('layouts.app')

@section('title', 'Attendance Report')

@section('content')
<x-page-header title="Attendance Report" subtitle="Attendance summary by employee and month." />

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

    // Month CS — init with the display label, not the raw value
    (function () {
        var cs    = new CustomSelect('erMonthWrapper');
        var input = document.getElementById('erMonthInput');
        var orig  = cs._select.bind(cs);
        cs._select = function (v, l) { orig(v, l); input.value = v; form.submit(); };
        if (input.value) orig(input.value, {{ json_encode($months[$filters['month']] ?? '') }});
    })();

    // Employee CS
    (function () {
        var cs    = new CustomSelect('erEmpWrapper');
        var input = document.getElementById('erEmpInput');
        var orig  = cs._select.bind(cs);
        cs._select = function (v, l) { orig(v, l); input.value = v; form.submit(); };
        if (input.value) orig(input.value, {{ json_encode($allEmployees[$filters['employee_id']] ?? '') }});
    })();
})();
</script>
@endpush
@endsection
