@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $stats          = $dashboard['stats'] ?? $dashboard;
    $recentProjects = $dashboard['recentProjects'] ?? [];

    $kpiCards = [
        [
            'label'      => 'Total Projects',
            'value'      => $stats['totalProjects'] ?? 0,
            'format'     => 'int',
            'icon'       => 'bi-kanban',
            'accent'     => '#3b82f6',
            'card_bg'    => '#eff6ff',
            'icon_bg'    => '#dbeafe',
            'icon_color' => '#2563eb',
            'route'      => route('projects.index'),
        ],
        [
            'label'      => 'Total Customers',
            'value'      => $stats['totalCustomers'] ?? 0,
            'format'     => 'int',
            'icon'       => 'bi-people',
            'accent'     => '#0891b2',
            'card_bg'    => '#ecfeff',
            'icon_bg'    => '#cffafe',
            'icon_color' => '#0e7490',
            'route'      => route('customers.index'),
        ],
        [
            'label'      => 'Purchase Value',
            'value'      => $stats['totalPurchaseValue'] ?? 0,
            'format'     => 'currency',
            'icon'       => 'bi-cart3',
            'accent'     => '#d97706',
            'card_bg'    => '#fffbeb',
            'icon_bg'    => '#fef3c7',
            'icon_color' => '#b45309',
            'route'      => route('purchases.index'),
        ],
        [
            'label'      => 'Total Expenses',
            'value'      => $stats['totalExpenses'] ?? 0,
            'format'     => 'currency',
            'icon'       => 'bi-receipt',
            'accent'     => '#dc2626',
            'card_bg'    => '#fef2f2',
            'icon_bg'    => '#fee2e2',
            'icon_color' => '#b91c1c',
            'route'      => route('expenses.index'),
        ],
        [
            'label'      => 'Total Receipts',
            'value'      => $stats['totalReceipts'] ?? $stats['totalPayments'] ?? 0,
            'format'     => 'currency',
            'icon'       => 'bi-credit-card',
            'accent'     => '#16a34a',
            'card_bg'    => '#f0fdf4',
            'icon_bg'    => '#dcfce7',
            'icon_color' => '#15803d',
            'route'      => route('payments.index'),
        ],
        [
            'label'      => 'Outstanding',
            'value'      => $stats['totalOutstanding'] ?? 0,
            'format'     => 'currency',
            'icon'       => 'bi-hourglass-split',
            'accent'     => '#64748b',
            'card_bg'    => '#f8fafc',
            'icon_bg'    => '#e2e8f0',
            'icon_color' => '#475569',
            'route'      => route('reports.outstanding'),
        ],
    ];
@endphp

@if($error)
    <div class="alert alert-warning mb-3">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ $error }}
    </div>
@endif

{{-- ─── KPI Cards ─────────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    @foreach($kpiCards as $card)
    <div class="col-12 col-sm-6 col-md-4">
        <a href="{{ $card['route'] }}" class="text-decoration-none d-block h-100">
            <div class="card kpi-card h-100" style="border-left-color:{{ $card['accent'] }};background:{{ $card['card_bg'] }};">
                <div class="card-body d-flex align-items-center gap-3 py-3 px-3">
                    <div class="kpi-icon" style="background:{{ $card['icon_bg'] }};color:{{ $card['icon_color'] }};">
                        <i class="bi {{ $card['icon'] }}"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="kpi-label">{{ $card['label'] }}</div>
                        <div class="kpi-value">
                            @if($card['format'] === 'int')
                                {{ number_format((int)$card['value']) }}
                            @else
                                {{ number_format((float)$card['value'], 2) }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>

{{-- ─── Recent Projects ────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-kanban me-2"></i>Recent Projects</span>
        <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
    </div>
    <div class="card-body p-0">
        @if(empty($recentProjects))
            <div class="empty-state">
                <i class="bi bi-kanban d-block"></i>
                <p>No recent projects.</p>
                <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm">Create First Project</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Project Name</th>
                            <th>Customer</th>
                            <th class="text-end">Budget</th>
                            <th>Added</th>
                            <th class="text-center">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentProjects as $project)
                        <tr>
                            <td class="fw-semibold">
                                <a href="{{ route('projects.show', $project['id']) }}">
                                    {{ $project['projectName'] ?? $project['name'] ?? '—' }}
                                </a>
                            </td>
                            <td>{{ $project['customerName'] ?? '—' }}</td>
                            <td class="text-end" style="font-variant-numeric:tabular-nums;">
                                {{ isset($project['budget']) && $project['budget'] ? number_format((float)$project['budget'], 2) : '—' }}
                            </td>
                            <td class="text-muted small">
                                {{ isset($project['createdAt']) ? date('d M Y', strtotime($project['createdAt'])) : '—' }}
                            </td>
                            <td class="text-center">
                                <a href="{{ route('projects.show', $project['id']) }}" class="btn-action" title="View Project">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
