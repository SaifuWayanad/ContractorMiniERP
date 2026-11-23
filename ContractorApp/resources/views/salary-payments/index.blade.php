@extends('layouts.app')

@section('title', 'Salary Payments')

@section('content')
<x-page-header title="Salary Payments" subtitle="Track employee salary payments.">
    <x-slot:actions>
        @can('create', App\Models\SalaryPayment::class)
            <a href="{{ route('salary-payments.bulk') }}" class="btn btn-primary btn-sm"><i class="bi bi-people-fill me-1"></i>Process Payroll</a>
        @endcan
    </x-slot:actions>
</x-page-header>

{{-- KPI --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-4 col-md-3">
        <div class="kpi-card kpi-blue h-100">
            <div class="kpi-label"><i class="bi bi-cash-stack me-1"></i>Total Paid</div>
            <div class="kpi-value">₹{{ number_format($totalPaid, 2) }}</div>
        </div>
    </div>
</div>

@php
    $monthOptions = [];
    for ($i = 23; $i >= 0; $i--) {
        $mo = now()->startOfMonth()->subMonths($i);
        $monthOptions[$mo->format('Y-m')] = $mo->format('F Y');
    }
    $selectedMonthLabel = $filters['month'] ? ($monthOptions[$filters['month']] ?? $filters['month']) : '';
@endphp
<form method="get" action="{{ route('salary-payments.index') }}" class="mb-4" id="spFilterForm">
    @if ($isEmployeePortal)
        <input type="hidden" name="employee_id" value="{{ $filters['employee_id'] }}">
    @endif
    <div class="row g-2 align-items-end">
        @unless ($isEmployeePortal)
        <div class="col-sm-6 col-md-3">
            <input type="text" class="form-control form-control-sm" name="search" value="{{ $filters['search'] }}" placeholder="Search employee…" style="min-height:calc(1.5em + 0.5rem + 2px)" />
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="cs-wrapper cs-sm" id="spEmpWrapper">
                <div class="cs-trigger">
                    <span class="cs-value {{ $filters['employee_id'] ? '' : 'cs-placeholder' }}">{{ $filters['employee_id'] ? ($employees[$filters['employee_id']] ?? 'All Employees') : 'All Employees' }}</span>
                    <i class="bi bi-chevron-down cs-arrow"></i>
                </div>
                <div class="cs-panel">
                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                    <div class="cs-list">
                        <div class="cs-item" data-value="">All Employees</div>
                        @foreach($employees as $id => $name)
                            <div class="cs-item" data-value="{{ $id }}">{{ $name }}</div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" name="employee_id" id="spEmpInput" value="{{ $filters['employee_id'] ?? '' }}">
            </div>
        </div>
        @endunless
        <div class="col-sm-6 col-md-3">
            <div class="cs-wrapper cs-sm" id="spMonthWrapper">
                <div class="cs-trigger">
                    <span class="cs-value {{ $filters['month'] ? '' : 'cs-placeholder' }}">{{ $selectedMonthLabel ?: 'All Months' }}</span>
                    <i class="bi bi-chevron-down cs-arrow"></i>
                </div>
                <div class="cs-panel">
                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                    <div class="cs-list">
                        <div class="cs-item" data-value="">All Months</div>
                        @foreach(array_reverse($monthOptions, true) as $val => $label)
                            <div class="cs-item" data-value="{{ $val }}">{{ $label }}</div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" name="month" id="spMonthInput" value="{{ $filters['month'] ?? '' }}">
            </div>
        </div>
        @if(! $isEmployeePortal && ($filters['search'] || $filters['employee_id'] || $filters['month']))
            <div class="col-auto">
                <a href="{{ route('salary-payments.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        @endif
    </div>
</form>

@if($payments->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-cash-stack" title="No salary payments yet" message="Record salary payments to track employee compensation.">
            @can('create', App\Models\SalaryPayment::class)
                <a href="{{ route('salary-payments.bulk') }}" class="btn btn-primary btn-sm">Process Payroll</a>
            @endcan
        </x-empty-state>
    </div>
@else
    <x-table-card :paginator="$payments">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Period</th>
                    <th class="d-none d-sm-table-cell">Date</th>
                    <th class="d-none d-md-table-cell">Method</th>
                    <th>Status</th>
                    <th class="text-end fw-semibold">Amount</th>
                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $sp)
                <tr>
                    <td>
                        <a href="{{ route('employees.show', $sp->employee_id) }}" class="row-title">{{ $sp->employee->name }}</a>
                    </td>
                    <td class="text-nowrap">{{ \Carbon\Carbon::createFromDate($sp->period_year, $sp->period_month, 1)->format('M Y') }}</td>
                    <td class="d-none d-sm-table-cell text-nowrap">{{ $sp->payment_date->format('d M Y') }}</td>
                    <td class="d-none d-md-table-cell text-muted">{{ $sp->payment_method ?? '—' }}</td>
                    <td><span class="badge {{ $sp->status->badgeClass() }}">{{ $sp->status->label() }}</span></td>
                    <td class="text-end fw-semibold">₹{{ number_format((float)$sp->amount, 2) }}</td>
                    <td class="text-end">
                        <div class="dropdown">
                            <button class="btn btn-icon btn-sm" data-bs-toggle="dropdown" aria-label="Actions"><i class="bi bi-three-dots-vertical"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @can('update', $sp)
                                    <li><a class="dropdown-item" href="{{ route('salary-payments.edit', $sp) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                @endcan
                                @can('delete', $sp)
                                    <li><hr class="dropdown-divider" /></li>
                                    <li><x-delete-button :action="route('salary-payments.destroy', $sp)" class="dropdown-item text-danger" title="Delete payment?" confirm="Delete this salary payment record?" /></li>
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
    var form = document.getElementById('spFilterForm');

    @unless ($isEmployeePortal)
    var empCs   = new CustomSelect('spEmpWrapper');
    var empInp  = document.getElementById('spEmpInput');
    var origEmp = empCs._select.bind(empCs);
    empCs._select = function (v, l) { origEmp(v, l); empInp.value = v; form.submit(); };
    @endunless

    var monCs   = new CustomSelect('spMonthWrapper');
    var monInp  = document.getElementById('spMonthInput');
    var origMon = monCs._select.bind(monCs);
    monCs._select = function (v, l) { origMon(v, l); monInp.value = v; form.submit(); };
    if (monInp.value) { monCs.orig(monInp.value, {{ json_encode($selectedMonthLabel) }}); }
})();
</script>
@endpush
@endsection
