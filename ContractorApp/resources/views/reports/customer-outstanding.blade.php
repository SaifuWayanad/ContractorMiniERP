@extends('layouts.app')

@section('title', 'Customer-wise Outstanding')

@use('Illuminate\Support\Number')

@section('content')
<x-page-header title="Customer-wise Outstanding" subtitle="Financial summary grouped by customer.">
    <x-slot:actions>
        <a href="{{ route('reports.customer-outstanding') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</a>
    </x-slot:actions>
</x-page-header>

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl">
        <div class="kpi-card kpi-blue h-100">
            <div class="kpi-label"><i class="bi bi-wallet2 me-1"></i>Total Budget</div>
            <div class="kpi-value">{{ Number::currency((float) $totals['budget'], 'INR', 'en_IN', 0) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="kpi-card kpi-red h-100">
            <div class="kpi-label"><i class="bi bi-receipt me-1"></i>Total Cost</div>
            <div class="kpi-value">{{ Number::currency((float) $totals['total_cost'], 'INR', 'en_IN', 0) }}</div>
            <div class="kpi-sub">Purchases + Expenses</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="kpi-card kpi-green h-100">
            <div class="kpi-label"><i class="bi bi-cash-coin me-1"></i>Collection</div>
            <div class="kpi-value">{{ Number::currency((float) $totals['payments'], 'INR', 'en_IN', 0) }}</div>
            <div class="kpi-sub">Payments received</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="kpi-card kpi-yellow h-100">
            <div class="kpi-label"><i class="bi bi-hourglass-split me-1"></i>Outstanding</div>
            <div class="kpi-value">{{ Number::currency((float) $totals['outstanding'], 'INR', 'en_IN', 0) }}</div>
            <div class="kpi-sub">Budget − Collection</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="kpi-card kpi-purple h-100">
            <div class="kpi-label"><i class="bi bi-graph-up-arrow me-1"></i>Net Balance</div>
            @php $netBalance = $totals['payments'] - $totals['total_cost']; @endphp
            <div class="kpi-value {{ $netBalance >= 0 ? 'text-success' : 'text-danger' }}">{{ Number::currency(abs($netBalance), 'INR', 'en_IN', 0) }}</div>
            <div class="kpi-sub">Collection − Cost</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<form method="get" action="{{ route('reports.customer-outstanding') }}" class="mb-4" id="custFilterForm" data-auto-filter>
    <div class="row g-2 align-items-end">
        <div class="col-sm-6 col-md-4">
            <label class="form-label form-label-sm fw-semibold">Search Customer</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control" name="search" value="{{ $search }}" placeholder="Customer name…" />
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <label class="form-label form-label-sm fw-semibold">Location</label>
            <div class="cs-wrapper cs-sm" id="custLocationWrapper">
                <div class="cs-trigger">
                    <span class="cs-value {{ $locationFilter ? '' : 'cs-placeholder' }}">{{ $locationFilter ?: 'All locations' }}</span>
                    <i class="bi bi-chevron-down cs-arrow"></i>
                </div>
                <div class="cs-panel">
                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                    <div class="cs-list">
                        <div class="cs-item" data-value="">All locations</div>
                        @foreach($locations as $loc)
                            <div class="cs-item" data-value="{{ $loc }}">{{ $loc }}</div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" name="location" id="custLocationInput" value="{{ $locationFilter ?? '' }}">
            </div>
        </div>
        @if($search || $locationFilter)
        <div class="col-auto">
            <a href="{{ route('reports.customer-outstanding') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
        @endif
    </div>
</form>

{{-- Table --}}
@if ($rows->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-people" title="No data found" message="No customers match the current filters." />
    </div>
@else
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th class="text-center d-none d-sm-table-cell">Projects</th>
                        <th class="text-end d-none d-md-table-cell">Budget</th>
                        <th class="text-end d-none d-lg-table-cell">Total Cost</th>
                        <th class="text-end">Collection</th>
                        <th class="text-end">Outstanding</th>
                        <th class="text-end d-none d-xl-table-cell">Net Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                    @php $nb = $row['payments'] - $row['total_cost']; @endphp
                    <tr style="cursor:pointer;" onclick="window.location='{{ route('reports.outstanding', ['client_id' => $row['client']->id]) }}'">
                        <td>
                            <a href="{{ route('reports.outstanding', ['client_id' => $row['client']->id]) }}" class="row-title" onclick="event.stopPropagation()">{{ $row['client']->name }}</a>
                            <div class="cell-sub">{{ $row['projects'] }} {{ Str::plural('project', $row['projects']) }}</div>
                        </td>
                        <td class="text-center d-none d-sm-table-cell text-muted">{{ $row['projects'] }}</td>
                        <td class="text-end d-none d-md-table-cell">{{ $row['budget'] > 0 ? Number::currency($row['budget'], 'INR', 'en_IN', 0) : '—' }}</td>
                        <td class="text-end d-none d-lg-table-cell">{{ Number::currency($row['total_cost'], 'INR', 'en_IN', 0) }}</td>
                        <td class="text-end fw-semibold">{{ Number::currency($row['payments'], 'INR', 'en_IN', 0) }}</td>
                        <td class="text-end fw-semibold {{ $row['outstanding'] > 0 ? 'text-danger' : 'text-success' }}">{{ Number::currency(abs($row['outstanding']), 'INR', 'en_IN', 0) }}</td>
                        <td class="text-end d-none d-xl-table-cell {{ $nb >= 0 ? 'text-success' : 'text-danger' }}">{{ Number::currency(abs($nb), 'INR', 'en_IN', 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer text-muted small">
            {{ $rows->count() }} {{ \Illuminate\Support\Str::plural('customer', $rows->count()) }}
        </div>
    </div>
@endif

@push('scripts')
<script>
(function () {
    var form = document.getElementById('custFilterForm');
    var cs = new CustomSelect('custLocationWrapper');
    var input = document.getElementById('custLocationInput');
    var orig = cs._select.bind(cs);
    cs._select = function(value, label) { orig(value, label); input.value = value; form.submit(); };
})();
</script>
@endpush
@endsection
