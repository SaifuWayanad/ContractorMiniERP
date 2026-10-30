@extends('layouts.app')

@section('title', 'Outstanding Report')

@section('content')
@php
    $items      = $report['items']  ?? [];
    $totals     = $report['totals'] ?? [];
    $pagination = ['total' => $report['total'] ?? 0, 'page' => $report['page'] ?? 1, 'pageSize' => $report['pageSize'] ?? 20];

    $customerList = $customers['data'] ?? $customers['items'] ?? (is_array($customers) ? array_values($customers) : []);
    $projectList  = $projects['data']  ?? $projects['items']  ?? (is_array($projects)  ? array_values($projects)  : []);
    $locationList = $locations['data'] ?? $locations['items'] ?? (is_array($locations) ? array_values($locations) : []);

    $hasAnyFilter = array_filter([request('customerId'), request('projectId'), request('search')]) !== [];

    $kpiCards = [
        [
            'label'       => 'Total Budget',
            'value'       => $totals['totalBudget']      ?? 0,
            'icon'        => 'bi-wallet2',
            'accent'      => '#3b82f6',
            'card_bg'     => '#eff6ff',
            'icon_bg'     => '#dbeafe',
            'icon_color'  => '#2563eb',
        ],
        [
            'label'       => 'Total Purchases',
            'value'       => $totals['totalPurchases']   ?? 0,
            'icon'        => 'bi-cart3',
            'accent'      => '#0891b2',
            'card_bg'     => '#ecfeff',
            'icon_bg'     => '#cffafe',
            'icon_color'  => '#0e7490',
        ],
        [
            'label'       => 'Total Expenses',
            'value'       => $totals['totalExpenses']    ?? 0,
            'icon'        => 'bi-receipt',
            'accent'      => '#d97706',
            'card_bg'     => '#fffbeb',
            'icon_bg'     => '#fef3c7',
            'icon_color'  => '#b45309',
        ],
        [
            'label'       => 'Total Cost',
            'value'       => $totals['totalProjectCost'] ?? 0,
            'icon'        => 'bi-calculator',
            'accent'      => '#dc2626',
            'card_bg'     => '#fef2f2',
            'icon_bg'     => '#fee2e2',
            'icon_color'  => '#b91c1c',
        ],
        [
            'label'       => 'Total Payment Received',
            'value'       => $totals['totalReceived']    ?? 0,
            'icon'        => 'bi-check-circle',
            'accent'      => '#16a34a',
            'card_bg'     => '#f0fdf4',
            'icon_bg'     => '#dcfce7',
            'icon_color'  => '#15803d',
        ],
        [
            'label'       => 'Total Balance Pending',
            'value'       => ($totals['totalBudget'] ?? 0) - ($totals['totalReceived'] ?? 0),
            'icon'        => 'bi-hourglass-split',
            'accent'      => '#64748b',
            'card_bg'     => '#f8fafc',
            'icon_bg'     => '#e2e8f0',
            'icon_color'  => '#475569',
        ],
    ];
@endphp

{{-- ─── KPI Summary Cards ──────────────────────────────────────────────────── --}}
<div class="row g-3 mb-3">
    @foreach($kpiCards as $kpi)
    <div class="col-12 col-sm-6 col-md-4">
        <div class="card kpi-card h-100" style="border-left: 3px solid {{ $kpi['accent'] }};background:{{ $kpi['card_bg'] }};">
            <div class="card-body d-flex align-items-center gap-3 py-3 px-3">
                <div class="kpi-icon" style="background:{{ $kpi['icon_bg'] }};color:{{ $kpi['icon_color'] }};">
                    <i class="bi {{ $kpi['icon'] }}"></i>
                </div>
                <div class="min-w-0">
                    <div class="kpi-label">{{ $kpi['label'] }}</div>
                    <div class="kpi-value">{{ number_format((float)$kpi['value'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ─── Filter Toolbar ─────────────────────────────────────────────────────── --}}
<div class="filter-toolbar mb-3">
    @if($error)
        <div class="alert alert-warning mb-2 py-2"><i class="bi bi-exclamation-triangle me-2"></i>{{ $error }}</div>
    @endif
    <form method="GET" action="{{ route('reports.outstanding') }}" id="reportFilterForm">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted" style="font-size:.8rem;"></i></span>
                    <input type="text" name="search" class="form-control form-control-sm border-start-0 ps-0"
                        placeholder="Project or customer…" value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label mb-1">Customer</label>
                <div class="cs-wrapper" id="customerFilterWrapper">
                    <div class="cs-trigger" tabindex="0" style="min-height:31px;padding:0.3rem 0.75rem;font-size:0.875rem;">
                        <span class="cs-value cs-placeholder">All Customers</span>
                        <i class="bi bi-chevron-down cs-arrow"></i>
                    </div>
                    <div class="cs-panel">
                        <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                        <div class="cs-list">
                            <div class="cs-item" data-value="">All Customers</div>
                            @forelse($customerList as $c)
                                <div class="cs-item" data-value="{{ $c['id'] ?? '' }}">{{ $c['customerName'] ?? '' }}</div>
                            @empty
                                <div class="cs-item no-data">No customers</div>
                            @endforelse
                        </div>
                    </div>
                    <input type="hidden" name="customerId" id="customerFilterInput" value="{{ request('customerId') }}">
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label mb-1">Project</label>
                <div class="cs-wrapper" id="projectFilterWrapper">
                    <div class="cs-trigger" tabindex="0" style="min-height:31px;padding:0.3rem 0.75rem;font-size:0.875rem;">
                        <span class="cs-value cs-placeholder">All Projects</span>
                        <i class="bi bi-chevron-down cs-arrow"></i>
                    </div>
                    <div class="cs-panel">
                        <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                        <div class="cs-list">
                            <div class="cs-item" data-value="">All Projects</div>
                            @forelse($projectList as $p)
                                <div class="cs-item" data-value="{{ $p['id'] ?? '' }}">{{ $p['projectName'] ?? '' }}</div>
                            @empty
                                <div class="cs-item no-data">No projects</div>
                            @endforelse
                        </div>
                    </div>
                    <input type="hidden" name="projectId" id="projectFilterInput" value="{{ request('projectId') }}">
                </div>
            </div>
            @if($hasAnyFilter)
            <div class="col-auto d-flex align-items-end">
                <a href="{{ route('reports.outstanding') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle me-1"></i>Reset
                </a>
            </div>
            @endif
        </div>
    </form>
</div>

{{-- ─── Report Table Card ──────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center py-2 px-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-graph-up-arrow text-primary"></i>
            <span class="fw-semibold" style="color:#1a3a5c;font-size:.95rem;">Project Outstanding Report</span>
            @if($pagination['total'] > 0)
                <span class="badge rounded-pill" style="background:#e2e8f0;color:#475569;font-weight:500;font-size:.72rem;">
                    {{ $pagination['total'] }} {{ Str::plural('project', $pagination['total']) }}
                </span>
            @endif
        </div>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
            <i class="bi bi-printer"></i><span class="d-none d-sm-inline">Print</span>
        </button>
    </div>

    @if(empty($items))
        <div class="empty-state">
            <i class="bi bi-graph-up-arrow d-block"></i>
            <p>No projects found{{ $hasAnyFilter ? ' matching the selected filters' : '' }}.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover report-table mb-0" id="reportTable">
                <thead>
                    <tr>
                        <th style="min-width:140px;">Project</th>
                        <th style="min-width:120px;">Customer</th>
                        <th style="min-width:100px;">Location</th>
                        <th class="text-end" style="min-width:100px;">Budget</th>
                        <th class="text-end" style="min-width:100px;">Purchases</th>
                        <th class="text-end" style="min-width:100px;">Expenses</th>
                        <th class="text-end" style="min-width:105px;">Total Cost</th>
                        <th class="text-end" style="min-width:115px;">Payments Recv'd</th>
                        <th class="text-end" style="min-width:105px;">Balance Due</th>
                        <th class="text-center" style="min-width:70px;">View</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $row)
                    @php
                        $balance = isset($row['balanceDue']) ? (float)$row['balanceDue'] : null;
                        $balancePositive = $balance !== null && $balance > 0.005;
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('projects.show', $row['projectId']) }}"
                               class="fw-semibold text-decoration-none project-link">
                                {{ $row['projectName'] ?? '—' }}
                            </a>
                        </td>
                        <td class="text-secondary">
                            @if(!empty($row['customerId']))
                                <a href="{{ route('customers.show', $row['customerId']) }}"
                                   class="text-decoration-none text-secondary">
                                    {{ $row['customerName'] ?? '—' }}
                                </a>
                            @else
                                {{ $row['customerName'] ?? '—' }}
                            @endif
                        </td>
                        <td class="text-secondary">{{ $row['locationName'] ?? '—' }}</td>
                        <td class="text-end num-cell">
                            {{ isset($row['budget']) ? number_format((float)$row['budget'], 2) : '—' }}
                        </td>
                        <td class="text-end num-cell">
                            <a href="{{ route('purchases.index', ['projectId' => $row['projectId']]) }}"
                               class="text-decoration-none text-secondary">
                                {{ number_format((float)($row['totalPurchases'] ?? 0), 2) }}
                            </a>
                        </td>
                        <td class="text-end num-cell">
                            <a href="{{ route('expenses.index', ['projectId' => $row['projectId']]) }}"
                               class="text-decoration-none text-secondary">
                                {{ number_format((float)($row['totalExpenses'] ?? 0), 2) }}
                            </a>
                        </td>
                        <td class="text-end num-cell fw-semibold" style="color:#1a3a5c;">
                            {{ number_format((float)($row['totalProjectCost'] ?? 0), 2) }}
                        </td>
                        <td class="text-end num-cell">
                            <a href="{{ route('payments.index', ['projectId' => $row['projectId']]) }}"
                               class="text-decoration-none" style="color:#16a34a;">
                                {{ number_format((float)($row['totalReceived'] ?? 0), 2) }}
                            </a>
                        </td>
                        <td class="text-end num-cell fw-semibold
                            {{ $balancePositive ? 'balance-due-positive' : 'balance-due-zero' }}">
                            {{ $balance !== null ? number_format($balance, 2) : '—' }}
                        </td>
                        <td class="text-center">
                            <a href="{{ route('projects.show', $row['projectId']) }}"
                               class="btn btn-sm btn-icon-ghost" title="View Project">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('_partials.pagination', ['pagination' => ['total' => $pagination['total'], 'currentPage' => $pagination['page'], 'pageSize' => $pagination['pageSize'], 'totalPages' => (int)ceil($pagination['total'] / $pagination['pageSize'])]])
    @endif
</div>

@endsection

@section('scripts')
<style>
/* ── Filter toolbar ── */
.filter-toolbar {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.filter-toolbar .form-label {
    font-size: 0.72rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 0.3rem;
}
.filter-toolbar .input-group-text {
    border-color: #ced4da; border-right: none; border-radius: 6px 0 0 6px;
    font-size: 0.85rem; padding-right: 0.4rem;
}
.filter-toolbar .form-control {
    border-left: none; padding-left: 0 !important;
}
.filter-toolbar .form-control:focus { border-left: none; }

/* ── Report table ── */
.report-table { font-size: 0.875rem; }
.report-table thead th {
    background-color: #f8fafc !important;
    color: #64748b !important;
    font-size: 0.7rem !important;
    letter-spacing: 0.06em;
    text-transform: uppercase !important;
    font-weight: 700;
    border-bottom: 1px solid #e2e8f0;
    padding: 0.65rem 0.75rem;
    white-space: nowrap;
}
.report-table tbody td {
    padding: 0.6rem 0.75rem;
    vertical-align: middle;
    border-color: #f1f5f9;
    font-size: 0.875rem;
    color: #374151;
}
.report-table tbody tr:hover td { background-color: #f8fafc; }
.num-cell { font-variant-numeric: tabular-nums; font-feature-settings: "tnum"; }
.project-link { color: #1a3a5c; }
.project-link:hover { color: #2a5080; text-decoration: underline !important; }
.balance-due-positive { color: #dc2626; }
.balance-due-zero { color: #16a34a; }

/* ── Ghost icon button ── */
.btn-icon-ghost {
    padding: 0.25rem 0.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: transparent;
    color: #64748b;
    font-size: 0.85rem;
    transition: background 0.15s, border-color 0.15s, color 0.15s;
}
.btn-icon-ghost:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #1a3a5c;
}

/* ── Print ── */
@media print {
    #sidebar, #topbar, .filter-toolbar, .btn, nav { display: none !important; }
    body, #main-wrapper { margin: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
    .kpi-card { border: 1px solid #dee2e6 !important; page-break-inside: avoid; }
}
</style>

<script>
new CustomSelect('customerFilterWrapper');
new CustomSelect('projectFilterWrapper');

const filterForm = document.getElementById('reportFilterForm');

// Auto-submit when custom select changes (CustomSelect dispatches 'change' on hidden input)
document.getElementById('customerFilterInput').addEventListener('change', () => filterForm.submit());
document.getElementById('projectFilterInput').addEventListener('change', () => filterForm.submit());

// Auto-submit on search with debounce
let searchTimer;
document.querySelector('input[name="search"]').addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => filterForm.submit(), 500);
});
</script>
@endsection
