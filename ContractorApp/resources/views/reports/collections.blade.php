@extends('layouts.app')

@section('title', 'Collection Report')

@use('Illuminate\Support\Number')

@section('content')
<x-page-header title="Collection Report" subtitle="Customer-wise collections with project and transaction drill-down.">
    <x-slot:actions>
        <a href="{{ route('reports.collections') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </a>
    </x-slot:actions>
</x-page-header>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="kpi-card kpi-green h-100">
            <div class="kpi-label"><i class="bi bi-cash-coin me-1"></i>Total Collections</div>
            <div class="kpi-value">{{ Number::currency((float) $grandTotal, 'INR', 'en_IN', 0) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="kpi-card kpi-blue h-100">
            <div class="kpi-label"><i class="bi bi-receipt me-1"></i>Transactions</div>
            <div class="kpi-value">{{ $payments->count() }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="kpi-card kpi-yellow h-100">
            <div class="kpi-label"><i class="bi bi-people me-1"></i>Customers</div>
            <div class="kpi-value">{{ $customerRows->count() }}</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<form method="get" action="{{ route('reports.collections') }}" class="mb-4" id="collectionsFilterForm">
    <div class="row g-2 align-items-end">
        <div class="col-sm-6 col-md-2">
            <label class="form-label form-label-sm fw-semibold">Period</label>
            <select name="period" id="periodSelect" class="form-select form-select-sm" onchange="handlePeriodChange(this.value)">
                <option value="today"      {{ $period === 'today'      ? 'selected' : '' }}>Today</option>
                <option value="this_week"  {{ $period === 'this_week'  ? 'selected' : '' }}>This Week</option>
                <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="last_month" {{ $period === 'last_month' ? 'selected' : '' }}>Last Month</option>
                <option value="this_year"  {{ $period === 'this_year'  ? 'selected' : '' }}>This Year</option>
                <option value="all_time"   {{ $period === 'all_time'   ? 'selected' : '' }}>All Time</option>
                <option value="custom"     {{ $period === 'custom'     ? 'selected' : '' }}>Custom…</option>
            </select>
        </div>
        <div id="customDateRange" class="col-sm-12 col-md-4 {{ $period === 'custom' ? '' : 'd-none' }}">
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label form-label-sm fw-semibold">From</label>
                    <input type="date" class="form-control form-control-sm" name="date_from"
                        value="{{ $period === 'custom' ? $dateFrom : '' }}"
                        onchange="document.getElementById('collectionsFilterForm').submit()" />
                </div>
                <div class="col-6">
                    <label class="form-label form-label-sm fw-semibold">To</label>
                    <input type="date" class="form-control form-control-sm" name="date_to"
                        value="{{ $period === 'custom' ? $dateTo : '' }}"
                        onchange="document.getElementById('collectionsFilterForm').submit()" />
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <label class="form-label form-label-sm fw-semibold">Customer</label>
            <div class="cs-wrapper cs-sm" id="colClientWrapper">
                <div class="cs-trigger">
                    <span class="cs-value {{ $clientId ? '' : 'cs-placeholder' }}">
                        {{ $clientId ? ($clients[$clientId] ?? 'All customers') : 'All customers' }}
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
                <input type="hidden" name="client_id" id="colClientInput" value="{{ $clientId ?? '' }}">
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <label class="form-label form-label-sm fw-semibold">Project</label>
            <div class="cs-wrapper cs-sm" id="colProjectWrapper">
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
                <input type="hidden" name="project_id" id="colProjectInput" value="{{ $projectId ?? '' }}">
            </div>
        </div>
        <div class="col-sm-6 col-md-2">
            <label class="form-label form-label-sm fw-semibold">Method</label>
            <div class="cs-wrapper cs-sm" id="colMethodWrapper">
                <div class="cs-trigger">
                    <span class="cs-value {{ $methodFilter ? '' : 'cs-placeholder' }}">{{ $methodFilter ?: 'All methods' }}</span>
                    <i class="bi bi-chevron-down cs-arrow"></i>
                </div>
                <div class="cs-panel">
                    <div class="cs-list">
                        <div class="cs-item" data-value="">All methods</div>
                        @foreach($allMethods as $m)
                            <div class="cs-item" data-value="{{ $m }}">{{ $m }}</div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" name="payment_method" id="colMethodInput" value="{{ $methodFilter ?? '' }}">
            </div>
        </div>
        @if($projectId || $clientId || $methodFilter || $period !== 'all_time')
        <div class="col-auto align-self-end">
            <a href="{{ route('reports.collections') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
        @endif
    </div>
</form>

@php $totalCols = $methods->count() + 2; @endphp

@if($customerRows->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-cash-coin" title="No collections found" message="No payment data matches the selected filters." />
    </div>
@else
<div class="card border" style="box-shadow:0 1px 4px rgba(0,0,0,.06)">
    <div class="table-responsive">
        <table class="table mb-0" id="collectionsTable" style="border-collapse:separate;border-spacing:0">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0">
                    <th style="padding:.75rem 1rem;color:#374151;font-size:.8rem;letter-spacing:.04em;text-transform:uppercase;font-weight:600;white-space:nowrap;text-align:left">
                        Customer / Project
                    </th>
                    @foreach($methods as $method)
                        <th style="padding:.75rem 1rem;color:#374151;font-size:.8rem;letter-spacing:.04em;text-transform:uppercase;font-weight:600;white-space:nowrap;text-align:left">
                            {{ $method }}
                        </th>
                    @endforeach
                    <th style="padding:.75rem 1rem;color:#374151;font-size:.8rem;letter-spacing:.04em;text-transform:uppercase;font-weight:600;white-space:nowrap;text-align:left">
                        Total
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($customerRows as $customer)
                    {{-- Level 1: Customer row --}}
                    <tr class="customer-row"
                        style="cursor:pointer;border-bottom:1px solid #e5e7eb;background:#fff"
                        onclick="toggleCustomer({{ $customer['client_id'] }}, this)">
                        <td style="padding:.875rem 1rem;font-weight:600;color:#111827">
                            <button type="button" class="btn btn-link btn-sm p-0 me-2 toggle-btn"
                                style="color:#6b7280;width:18px;text-align:center" data-open="0" aria-label="Expand">
                                <i class="bi bi-chevron-right" style="font-size:.75rem"></i>
                            </button>
                            {{ $customer['client_name'] }}
                            <span style="font-size:.78rem;color:#9ca3af;font-weight:400;margin-left:.5rem">
                                {{ $customer['projects']->count() }} {{ Str::plural('project', $customer['projects']->count()) }}
                            </span>
                        </td>
                        @foreach($methods as $method)
                            <td style="padding:.875rem 1rem;color:#374151;font-weight:500;white-space:nowrap">
                                @if($customer['methods'][$method] > 0)
                                    ₹{{ number_format($customer['methods'][$method], 2) }}
                                @else
                                    <span style="color:#d1d5db">—</span>
                                @endif
                            </td>
                        @endforeach
                        <td style="padding:.875rem 1rem;font-weight:700;color:#111827;white-space:nowrap">
                            ₹{{ number_format($customer['total'], 2) }}
                        </td>
                    </tr>

                    @foreach($customer['projects'] as $project)
                        {{-- Level 2: Project row --}}
                        <tr class="project-row project-of-{{ $customer['client_id'] }} d-none"
                            style="cursor:pointer;border-bottom:1px solid #f3f4f6;background:#f9fafb"
                            onclick="toggleProject({{ $project['project_id'] }}, this)">
                            <td style="padding:.75rem 1rem .75rem 2.5rem;color:#374151;font-weight:500">
                                <button type="button" class="btn btn-link btn-sm p-0 me-2 toggle-btn"
                                    style="color:#9ca3af;width:18px;text-align:center" data-open="0" aria-label="Expand">
                                    <i class="bi bi-chevron-right" style="font-size:.75rem"></i>
                                </button>
                                {{ $project['project_name'] }}
                                @if($project['project_number'])
                                    <span style="color:#9ca3af;font-size:.78rem;margin-left:.35rem">#{{ $project['project_number'] }}</span>
                                @endif
                                <span style="font-size:.75rem;color:#9ca3af;font-weight:400;margin-left:.4rem">
                                    · {{ $project['transactions']->count() }} {{ Str::plural('payment', $project['transactions']->count()) }}
                                </span>
                            </td>
                            @foreach($methods as $method)
                                <td style="padding:.75rem 1rem;color:#6b7280;white-space:nowrap">
                                    @if($project['methods'][$method] > 0)
                                        ₹{{ number_format($project['methods'][$method], 2) }}
                                    @else
                                        <span style="color:#e5e7eb">—</span>
                                    @endif
                                </td>
                            @endforeach
                            <td style="padding:.75rem 1rem;font-weight:600;color:#374151;white-space:nowrap">
                                ₹{{ number_format($project['total'], 2) }}
                            </td>
                        </tr>

                        {{-- Level 3: Transaction rows --}}
                        <tr class="txn-container txn-of-{{ $project['project_id'] }} project-of-{{ $customer['client_id'] }} d-none"
                            style="background:#f8fafc">
                            <td colspan="{{ $totalCols }}" style="padding:.5rem 1.25rem .75rem 3.5rem;background:#f8fafc">
                                <div style="border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;background:#fff">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr style="background:#f1f5f9;border-bottom:1px solid #e5e7eb">
                                                <th style="padding:.5rem .75rem;font-size:.75rem;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;text-align:left">
                                                    Date
                                                </th>
                                                @foreach($methods as $method)
                                                    <th style="padding:.5rem .75rem;font-size:.75rem;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;text-align:left">
                                                        {{ $method }}
                                                    </th>
                                                @endforeach
                                                <th style="padding:.5rem .75rem;font-size:.75rem;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.04em;text-align:left">
                                                    Total
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($project['transactions'] as $txn)
                                            @php $txnMethod = $txn->payment_method ?? 'Other'; @endphp
                                            <tr style="border-bottom:1px solid #f3f4f6">
                                                <td style="padding:.5rem .75rem;color:#6b7280;font-size:.85rem;white-space:nowrap">
                                                    {{ $txn->payment_date?->format('d M Y') }}
                                                </td>
                                                @foreach($methods as $method)
                                                    <td style="padding:.5rem .75rem;font-size:.85rem;white-space:nowrap">
                                                        @if($txnMethod === $method)
                                                            <span style="color:#111827;font-weight:500">₹{{ number_format((float)$txn->amount_received, 2) }}</span>
                                                        @else
                                                            <span style="color:#e5e7eb">—</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                                <td style="padding:.5rem .75rem;font-size:.85rem;font-weight:600;color:#111827;white-space:nowrap">
                                                    ₹{{ number_format((float)$txn->amount_received, 2) }}
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f1f5f9;border-top:2px solid #e2e8f0">
                    <td style="padding:.875rem 1rem;font-weight:700;color:#111827;font-size:.9rem">Grand Total</td>
                    @foreach($methods as $method)
                        <td style="padding:.875rem 1rem;font-weight:600;color:#374151;white-space:nowrap">
                            ₹{{ number_format($methodGrandTotals[$method], 2) }}
                        </td>
                    @endforeach
                    <td style="padding:.875rem 1rem;font-weight:700;color:#111827;font-size:.95rem;white-space:nowrap">
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
function toggleCustomer(clientId, row) {
    var btn = row.querySelector('.toggle-btn');
    var isOpen = btn.getAttribute('data-open') === '1';
    var rows = document.querySelectorAll('.project-of-' + clientId);
    if (isOpen) {
        rows.forEach(function(r) {
            r.classList.add('d-none');
            if (r.classList.contains('project-row')) {
                var pb = r.querySelector('.toggle-btn');
                if (pb) { pb.setAttribute('data-open', '0'); pb.querySelector('i').className = 'bi bi-chevron-right'; pb.style.fontSize = '.75rem'; }
            }
        });
        btn.setAttribute('data-open', '0');
        btn.querySelector('i').className = 'bi bi-chevron-right';
    } else {
        rows.forEach(function(r) { if (r.classList.contains('project-row')) r.classList.remove('d-none'); });
        btn.setAttribute('data-open', '1');
        btn.querySelector('i').className = 'bi bi-chevron-down';
    }
}

function toggleProject(projectId, row) {
    event.stopPropagation();
    var btn = row.querySelector('.toggle-btn');
    var isOpen = btn.getAttribute('data-open') === '1';
    var rows = document.querySelectorAll('.txn-of-' + projectId);
    if (isOpen) {
        rows.forEach(function(r) { r.classList.add('d-none'); });
        btn.setAttribute('data-open', '0');
        btn.querySelector('i').className = 'bi bi-chevron-right';
    } else {
        rows.forEach(function(r) { r.classList.remove('d-none'); });
        btn.setAttribute('data-open', '1');
        btn.querySelector('i').className = 'bi bi-chevron-down';
    }
}

function handlePeriodChange(val) {
    var el = document.getElementById('customDateRange');
    if (val === 'custom') {
        el.classList.remove('d-none');
    } else {
        el.classList.add('d-none');
        el.querySelectorAll('input[type=date]').forEach(function(i) { i.value = ''; });
        document.getElementById('collectionsFilterForm').submit();
    }
}

(function () {
    var form = document.getElementById('collectionsFilterForm');
    function bindCs(wrapperId, inputId, autoSubmit) {
        var cs = new CustomSelect(wrapperId);
        var input = document.getElementById(inputId);
        var orig = cs._select.bind(cs);
        cs._select = function(v, l) { orig(v, l); input.value = v; if (autoSubmit) form.submit(); };
    }
    bindCs('colClientWrapper', 'colClientInput', true);
    bindCs('colProjectWrapper', 'colProjectInput', true);
    bindCs('colMethodWrapper', 'colMethodInput', true);
})();
</script>
@endpush
@endsection
