@extends('layouts.app')

@section('title', 'Purchases')

@section('content')
@php
    $items = $purchases['data'] ?? $purchases['items'] ?? (is_array($purchases) && !isset($purchases['id']) ? array_values($purchases) : []);
    $pagination = $purchases['pagination'] ?? $purchases['meta'] ?? null;
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-cart3 me-2"></i>Purchases</span>
        <a href="{{ route('purchases.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Add Purchase
        </a>
    </div>

    @if($error)
        <div class="px-3 pt-3">
            <div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-2"></i>{{ $error }}</div>
        </div>
    @endif

    <div class="card-body p-0">
        @if(empty($items))
            <div class="empty-state">
                <i class="bi bi-cart3 d-block"></i>
                <p>No purchases found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Project</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Rate</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $purchase)
                        <tr>
                            <td>{{ isset($purchase['purchaseDate']) ? date('d M Y', strtotime($purchase['purchaseDate'])) : '—' }}</td>
                            <td>{{ $purchase['projectName'] ?? '—' }}</td>
                            <td>{{ $purchase['customerName'] ?? '—' }}</td>
                            <td>{{ $purchase['productName'] ?? '—' }}</td>
                            <td class="text-end" style="font-variant-numeric:tabular-nums;">{{ $purchase['quantity'] ?? '—' }}</td>
                            <td class="text-end" style="font-variant-numeric:tabular-nums;">{{ isset($purchase['unitAmount']) ? number_format((float)$purchase['unitAmount'], 2) : '—' }}</td>
                            <td class="text-end fw-semibold" style="font-variant-numeric:tabular-nums;color:#1a3a5c;">{{ isset($purchase['lineTotal']) ? number_format((float)$purchase['lineTotal'], 2) : '—' }}</td>
                            <td class="text-center" style="white-space:nowrap;">
                                <a href="{{ route('purchases.edit', $purchase['id']) }}" class="btn-action me-1" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('purchases.destroy', $purchase['id']) }}" method="POST" class="d-inline delete-form" data-confirm-message="Are you sure you want to delete this purchase?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action btn-action-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('_partials.pagination', ['pagination' => $pagination ?? ['total' => count($items)]])
        @endif
    </div>
</div>
@endsection
