@extends('layouts.app')

@section('title', 'Payments')

@section('content')
@php
    $items = $payments['data'] ?? $payments['items'] ?? (is_array($payments) && !isset($payments['id']) ? array_values($payments) : []);
    $pagination = $payments['pagination'] ?? $payments['meta'] ?? null;
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-credit-card me-2"></i>Payments</span>
        <a href="{{ route('payments.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Add Payment
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
                <i class="bi bi-credit-card d-block"></i>
                <p>No payments found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    @php
                    $methodLabels = ['cash'=>'Cash','bank_transfer'=>'Bank Transfer','cheque'=>'Cheque','card'=>'Card','other'=>'Other'];
                    @endphp
                    <thead>
                        <tr>
                            <th style="white-space:nowrap">Date</th>
                            <th>Customer</th>
                            <th>Project</th>
                            <th class="text-end" style="white-space:nowrap">Amount</th>
                            <th style="white-space:nowrap">Method</th>
                            <th>Reference</th>
                            <th class="text-center" style="white-space:nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $payment)
                        <tr>
                            <td style="white-space:nowrap">{{ isset($payment['paymentDate']) ? date('d M Y', strtotime($payment['paymentDate'])) : '—' }}</td>
                            <td>{{ $payment['customerName'] ?? '—' }}</td>
                            <td>{{ $payment['projectName'] ?? '—' }}</td>
                            <td class="text-end fw-semibold" style="font-variant-numeric:tabular-nums;color:#16a34a;white-space:nowrap">{{ isset($payment['amountReceived']) ? number_format((float)$payment['amountReceived'], 2) : '—' }}</td>
                            <td style="white-space:nowrap">{{ $methodLabels[$payment['paymentMethod'] ?? ''] ?? ($payment['paymentMethod'] ?? '—') }}</td>
                            <td class="text-muted small">{{ $payment['receiptReference'] ?? '—' }}</td>
                            <td class="text-center" style="white-space:nowrap;">
                                <a href="{{ route('payments.edit', $payment['id']) }}" class="btn-action me-1" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('payments.destroy', $payment['id']) }}" method="POST" class="d-inline delete-form" data-confirm-message="Are you sure you want to delete this payment?">
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
