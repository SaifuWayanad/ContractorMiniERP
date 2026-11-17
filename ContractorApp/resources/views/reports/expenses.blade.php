@extends('layouts.app')

@section('title', 'Expense Report')

@use('Illuminate\Support\Number')

@section('content')
<x-page-header title="Expense Report" subtitle="Transaction-wise expenses across all projects." />

@php
$periodLabels = ['today'=>'Today','this_week'=>'This Week','this_month'=>'This Month','last_month'=>'Last Month','this_year'=>'This Year','all_time'=>'All Time','custom'=>'Custom…'];
@endphp

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="kpi-card kpi-green h-100">
            <div class="kpi-label"><i class="bi bi-graph-down-arrow me-1"></i>Total Expenses</div>
            <div class="kpi-value">{{ Number::currency((float) $grandTotal, 'INR', 'en_IN', 0) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="kpi-card kpi-blue h-100">
            <div class="kpi-label"><i class="bi bi-receipt me-1"></i>Transactions</div>
            <div class="kpi-value">{{ $rows->count() }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="kpi-card kpi-yellow h-100">
            <div class="kpi-label"><i class="bi bi-building me-1"></i>Projects</div>
            <div class="kpi-value">{{ $rows->pluck('project')->filter()->pluck('id')->unique()->count() }}</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<form method="get" action="{{ route('reports.expenses') }}" class="mb-4" id="expensesFilterForm">
    <div class="row g-2 align-items-end">
        <div class="col-sm-6 col-md-3">
            <label class="form-label form-label-sm fw-semibold">Period</label>
            <div class="cs-wrapper cs-sm" id="expPeriodWrapper">
                <div class="cs-trigger">
                    <span class="cs-value">{{ $periodLabels[$period] ?? 'All Time' }}</span>
                    <i class="bi bi-chevron-down cs-arrow"></i>
                </div>
                <div class="cs-panel">
                    <div class="cs-list">
                        @foreach($periodLabels as $val => $label)
                            <div class="cs-item" data-value="{{ $val }}">{{ $label }}</div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" name="period" id="expPeriodInput" value="{{ $period }}">
            </div>
        </div>
        <div id="expCustomDateRange" class="col-sm-12 col-md-4 {{ $period === 'custom' ? '' : 'd-none' }}">
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label form-label-sm fw-semibold">From</label>
                    <input type="date" class="form-control form-control-sm" name="date_from"
                        value="{{ $period === 'custom' ? $dateFrom : '' }}"
                        onchange="document.getElementById('expensesFilterForm').submit()" />
                </div>
                <div class="col-6">
                    <label class="form-label form-label-sm fw-semibold">To</label>
                    <input type="date" class="form-control form-control-sm" name="date_to"
                        value="{{ $period === 'custom' ? $dateTo : '' }}"
                        onchange="document.getElementById('expensesFilterForm').submit()" />
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <label class="form-label form-label-sm fw-semibold">Project</label>
            <div class="cs-wrapper cs-sm" id="expProjectWrapper">
                <div class="cs-trigger">
                    <span class="cs-value {{ $projectId ? '' : 'cs-placeholder' }}">
                        {{ $projectId ? ($projects[$projectId] ?? 'All projects') : 'All projects' }}
                    </span>
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
                <input type="hidden" name="project_id" id="expProjectInput" value="{{ $projectId ?? '' }}">
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <label class="form-label form-label-sm fw-semibold">Category</label>
            <div class="cs-wrapper cs-sm" id="expCategoryWrapper">
                <div class="cs-trigger">
                    <span class="cs-value {{ $categoryFilter ? '' : 'cs-placeholder' }}">
                        {{ $categoryFilter ?: 'All categories' }}
                    </span>
                    <i class="bi bi-chevron-down cs-arrow"></i>
                </div>
                <div class="cs-panel">
                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                    <div class="cs-list">
                        <div class="cs-item" data-value="">All categories</div>
                        @foreach($allCategories as $cat)
                            <div class="cs-item" data-value="{{ $cat }}">{{ $cat }}</div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" name="category" id="expCategoryInput" value="{{ $categoryFilter ?? '' }}">
            </div>
        </div>
        @if($projectId || $categoryFilter || $period !== 'all_time')
        <div class="col-auto align-self-end">
            <a href="{{ route('reports.expenses') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
        @endif
    </div>
</form>

@if($rows->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-graph-down-arrow" title="No expenses found" message="No expense data matches the selected filters." />
    </div>
@else
<div class="card border" style="box-shadow:0 1px 4px rgba(0,0,0,.06)">
    <div class="table-responsive">
        <table class="table mb-0" style="border-collapse:separate;border-spacing:0">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0">
                    <th style="padding:.75rem 1rem;color:#374151;font-size:.8rem;letter-spacing:.04em;text-transform:uppercase;font-weight:600;white-space:nowrap;text-align:left">Date</th>
                    <th style="padding:.75rem 1rem;color:#374151;font-size:.8rem;letter-spacing:.04em;text-transform:uppercase;font-weight:600;white-space:nowrap;text-align:left">Project</th>
                    <th style="padding:.75rem 1rem;color:#374151;font-size:.8rem;letter-spacing:.04em;text-transform:uppercase;font-weight:600;white-space:nowrap;text-align:left">Category</th>
                    <th style="padding:.75rem 1rem;color:#374151;font-size:.8rem;letter-spacing:.04em;text-transform:uppercase;font-weight:600;white-space:nowrap;text-align:left">Heading</th>
                    <th style="padding:.75rem 1rem;color:#374151;font-size:.8rem;letter-spacing:.04em;text-transform:uppercase;font-weight:600;white-space:nowrap;text-align:left">Payment Method</th>
                    <th style="padding:.75rem 1rem;color:#374151;font-size:.8rem;letter-spacing:.04em;text-transform:uppercase;font-weight:600;white-space:nowrap;text-align:left">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="padding:.75rem 1rem;color:#6b7280;font-size:.875rem;white-space:nowrap">
                        {{ $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('d M Y') : '—' }}
                    </td>
                    <td style="padding:.75rem 1rem;font-size:.875rem;color:#374151">
                        {{ $row['project']?->name ?? '—' }}
                        @if($row['project']?->project_number)
                            <span style="color:#9ca3af;font-size:.78rem;margin-left:.25rem">#{{ $row['project']->project_number }}</span>
                        @endif
                    </td>
                    <td style="padding:.75rem 1rem;font-size:.875rem;color:#374151">
                        {{ $row['category'] ?? '—' }}
                    </td>
                    <td style="padding:.75rem 1rem;font-size:.875rem;color:#374151">
                        {{ $row['heading'] ?? '—' }}
                        @if($row['is_work'])
                            <span class="badge bg-secondary ms-1">Work</span>
                        @endif
                    </td>
                    <td style="padding:.75rem 1rem;font-size:.875rem;color:#6b7280;white-space:nowrap">
                        {{ $row['payment_method'] ?? '—' }}
                    </td>
                    <td style="padding:.75rem 1rem;font-size:.875rem;font-weight:600;color:#dc2626;white-space:nowrap">
                        ₹{{ number_format($row['amount'], 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f1f5f9">
                    <td colspan="5" style="padding:.875rem 1rem;font-weight:700;color:#111827;font-size:.9rem;border-top:2px solid #cbd5e1">Grand Total</td>
                    <td style="padding:.875rem 1rem;font-weight:700;color:#dc2626;font-size:.95rem;white-space:nowrap;border-top:2px solid #cbd5e1">
                        ₹{{ number_format($grandTotal, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

@push('scripts')
<script>
function handleExpPeriodChange(val) {
    var el = document.getElementById('expCustomDateRange');
    if (val === 'custom') {
        el.classList.remove('d-none');
    } else {
        el.classList.add('d-none');
        el.querySelectorAll('input[type=date]').forEach(function(i) { i.value = ''; });
        document.getElementById('expensesFilterForm').submit();
    }
}

(function () {
    var form = document.getElementById('expensesFilterForm');
    function bindCs(wrapperId, inputId, autoSubmit) {
        var cs = new CustomSelect(wrapperId);
        var input = document.getElementById(inputId);
        var orig = cs._select.bind(cs);
        cs._select = function(v, l) { orig(v, l); input.value = v; if (autoSubmit) form.submit(); };
        return cs;
    }
    var periodCs = bindCs('expPeriodWrapper', 'expPeriodInput', false);
    var periodOrig = periodCs._select.bind(periodCs);
    periodCs._select = function(v, l) { periodOrig(v, l); handleExpPeriodChange(v); };
    bindCs('expProjectWrapper', 'expProjectInput', true);
    bindCs('expCategoryWrapper', 'expCategoryInput', true);
})();
</script>
@endpush
@endsection
