@extends('layouts.app')

@section('title', 'Purchase #' . $purchase->id)

@section('content')

@php
    $isBatch      = $purchase->items->isNotEmpty();
    $visibleItems = $isBatch
        ? ($filterProjectId ? $purchase->items->where('project_id', $filterProjectId) : $purchase->items)
        : collect();
    $displayTotal = $isBatch
        ? (float) $visibleItems->sum('line_total')
        : (float) $purchase->line_total;
    $isMultiProject = $isBatch && $purchase->items->pluck('project_id')->unique()->count() > 1;
    $backProject    = $filterProjectId
        ? $purchase->items->firstWhere('project_id', $filterProjectId)?->project
        : $purchase->project;
@endphp

<x-page-header title="Purchase Details" subtitle="Purchase #{{ $purchase->id }}">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
        @if($backProject)
            <li class="breadcrumb-item"><a href="{{ route('projects.show', $backProject) }}">{{ $backProject->name }}</a></li>
        @endif
        <li class="breadcrumb-item active">#{{ $purchase->id }}</li>
    </x-slot:breadcrumbs>
    <x-slot:actions>
        @if($filterProjectId && $backProject)
            <a href="{{ route('projects.show', $backProject) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Project
            </a>
        @else
            <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Purchases
            </a>
        @endif
        @can('update', $purchase)
            <a href="{{ route('purchases.editBatch', $purchase) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

{{-- KPI cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Purchase Date</div>
                <div class="fw-semibold">{{ $purchase->purchase_date?->format('d M Y') ?? '—' }}</div>
            </div>
        </div>
    </div>

    @if(!$isMultiProject)
    <div class="col-sm-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Project</div>
                <div class="fw-semibold">
                    @php $proj = $backProject ?? $purchase->project; @endphp
                    @if($proj)
                        <a href="{{ route('projects.show', $proj) }}" class="text-decoration-none">{{ $proj->name }}</a>
                        <div class="text-muted small">{{ $proj->project_number }}</div>
                    @else
                        —
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($purchase->supplier)
    <div class="col-sm-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Supplier</div>
                <div class="fw-semibold">{{ $purchase->supplier }}</div>
            </div>
        </div>
    </div>
    @endif

    @if($purchase->invoice_reference)
    <div class="col-sm-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Invoice Ref.</div>
                <div class="fw-semibold">{{ $purchase->invoice_reference }}</div>
            </div>
        </div>
    </div>
    @endif

    <div class="col-sm-6 col-md-3">
        <div class="card h-100 border-success">
            <div class="card-body">
                <div class="text-muted small mb-1">Total Amount</div>
                <div class="fw-bold fs-5 text-success">₹{{ number_format($displayTotal, 2) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Items table --}}
<div class="card">
    <div class="card-header">
        <span class="fw-semibold">{{ $isBatch ? 'Product Items' : 'Purchase Details' }}</span>
        @if($filterProjectId && $isMultiProject)
            <span class="text-muted small ms-2">(filtered to {{ $backProject?->name }})</span>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    @if($isMultiProject && !$filterProjectId)
                        <th>Project</th>
                    @endif
                    <th>Product</th>
                    <th class="text-end" style="width:130px">Qty</th>
                    <th class="text-end" style="width:150px">Unit Price</th>
                    <th class="text-end fw-semibold" style="width:170px">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @if($isBatch)
                    @foreach($visibleItems as $item)
                    <tr>
                        @if($isMultiProject && !$filterProjectId)
                            <td class="text-muted small">{{ $item->project?->name ?? '—' }}</td>
                        @endif
                        <td>
                            <span class="fw-semibold">{{ $item->product?->name ?? '—' }}</span>
                            @if($item->product?->unit)
                                <span class="text-muted small ms-1">({{ $item->product->unit }})</span>
                            @endif
                        </td>
                        <td class="text-end text-muted">
                            {{ $item->quantity !== null ? rtrim(rtrim(number_format((float)$item->quantity, 4), '0'), '.') : '—' }}
                        </td>
                        <td class="text-end text-muted">
                            {{ $item->unit_amount !== null ? '₹'.number_format((float)$item->unit_amount, 2) : '—' }}
                        </td>
                        <td class="text-end fw-semibold">₹{{ number_format((float)$item->line_total, 2) }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td>
                            <span class="fw-semibold">{{ $purchase->product?->name ?? '—' }}</span>
                            @if($purchase->product?->unit)
                                <span class="text-muted small ms-1">({{ $purchase->product->unit }})</span>
                            @endif
                        </td>
                        <td class="text-end text-muted">
                            {{ $purchase->quantity !== null ? rtrim(rtrim(number_format((float)$purchase->quantity, 4), '0'), '.') : '—' }}
                        </td>
                        <td class="text-end text-muted">
                            {{ $purchase->unit_amount !== null ? '₹'.number_format((float)$purchase->unit_amount, 2) : '—' }}
                        </td>
                        <td class="text-end fw-semibold">₹{{ number_format((float)$purchase->line_total, 2) }}</td>
                    </tr>
                @endif
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="{{ ($isMultiProject && !$filterProjectId) ? 4 : 3 }}" class="text-end fw-bold">Grand Total</td>
                    <td class="text-end fw-bold fs-6 text-success">₹{{ number_format($displayTotal, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($purchase->notes)
    <div class="card-footer text-muted small">
        <strong>Notes:</strong> {{ $purchase->notes }}
    </div>
    @endif
</div>

@endsection
