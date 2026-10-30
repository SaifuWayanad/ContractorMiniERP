@extends('layouts.app')

@section('title', 'Outstanding Report')

@use('Illuminate\Support\Number')

@section('content')
<x-page-header title="Outstanding Report" subtitle="Financial summary across all your projects.">
    <x-slot:actions>
        <a href="{{ route('reports.outstanding') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</a>
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
<form method="get" action="{{ route('reports.outstanding') }}" class="mb-4" id="outstandingFilterForm" data-auto-filter>
    <div class="row g-2 align-items-end">
        <div class="col-sm-6 col-md-3">
            <label class="form-label form-label-sm fw-semibold">Search Project</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control" name="search" value="{{ $search }}" placeholder="Project name…" />
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <label class="form-label form-label-sm fw-semibold">Customer</label>
            <div class="cs-wrapper cs-sm" id="rptClientWrapper">
                <div class="cs-trigger">
                    <span class="cs-value {{ $clientIdFilter ? '' : 'cs-placeholder' }}">
                        {{ $clientIdFilter ? ($clients[$clientIdFilter] ?? 'All customers') : 'All customers' }}
                    </span>
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
                <input type="hidden" name="client_id" id="rptClientInput" value="{{ $clientIdFilter ?? '' }}">
            </div>
        </div>
        <div class="col-sm-6 col-md-2">
            <label class="form-label form-label-sm fw-semibold">Location</label>
            <div class="cs-wrapper cs-sm" id="rptLocationWrapper">
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
                <input type="hidden" name="location" id="rptLocationInput" value="{{ $locationFilter ?? '' }}">
            </div>
        </div>
        @if($search || $clientIdFilter || $locationFilter)
        <div class="col-auto">
            <a href="{{ route('reports.outstanding') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
        @endif
    </div>
</form>

{{-- Table --}}
@if ($rows->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-bar-chart-line" title="No projects found" message="No projects match the current filters." />
    </div>
@else
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th class="d-none d-md-table-cell text-end">Budget</th>
                        <th class="d-none d-lg-table-cell text-end">Purchases</th>
                        <th class="d-none d-lg-table-cell text-end">Expenses</th>
                        <th class="d-none d-sm-table-cell text-end">Total Cost</th>
                        <th class="text-end">Collection</th>
                        <th class="text-end">Outstanding</th>
                        <th class="d-none d-xl-table-cell text-end">Net Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        @php $nb = $row['payments'] - $row['total_cost']; @endphp
                        <tr>
                            <td>
                                <a href="{{ route('projects.show', $row['project']) }}" class="row-title">{{ $row['project']->name }}</a>
                                @if ($row['project']->client)
                                    <div class="cell-sub">{{ $row['project']->client->name }}</div>
                                @endif
                            </td>
                            <td class="d-none d-md-table-cell text-end">{{ $row['budget'] > 0 ? Number::currency($row['budget'], 'INR', 'en_IN', 0) : '—' }}</td>
                            <td class="d-none d-lg-table-cell text-end">{{ Number::currency($row['purchases'], 'INR', 'en_IN', 0) }}</td>
                            <td class="d-none d-lg-table-cell text-end">{{ Number::currency($row['expenses'], 'INR', 'en_IN', 0) }}</td>
                            <td class="d-none d-sm-table-cell text-end">{{ Number::currency($row['total_cost'], 'INR', 'en_IN', 0) }}</td>
                            <td class="text-end fw-semibold">{{ Number::currency($row['payments'], 'INR', 'en_IN', 0) }}</td>
                            <td class="text-end fw-semibold {{ $row['outstanding'] > 0 ? 'text-danger' : 'text-success' }}">{{ Number::currency(abs($row['outstanding']), 'INR', 'en_IN', 0) }}</td>
                            <td class="d-none d-xl-table-cell text-end {{ $nb >= 0 ? 'text-success' : 'text-danger' }}">{{ Number::currency(abs($nb), 'INR', 'en_IN', 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($rows->hasPages())
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Showing {{ $rows->firstItem() }}–{{ $rows->lastItem() }} of {{ $rows->total() }} projects
                </div>
                {{ $rows->links() }}
            </div>
        @else
            <div class="card-footer text-muted small">
                {{ $rows->total() }} {{ Str::plural('project', $rows->total()) }}
            </div>
        @endif
    </div>
@endif

@push('scripts')
<script>
(function () {
    var form = document.getElementById('outstandingFilterForm');

    function bindCs(wrapperId, inputId) {
        var cs = new CustomSelect(wrapperId);
        var input = document.getElementById(inputId);
        var orig = cs._select.bind(cs);
        cs._select = function(value, label) { orig(value, label); input.value = value; form.submit(); };
    }

    bindCs('rptClientWrapper', 'rptClientInput');
    bindCs('rptLocationWrapper', 'rptLocationInput');
})();
</script>
@endpush
@endsection
