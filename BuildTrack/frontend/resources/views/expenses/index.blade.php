@extends('layouts.app')

@section('title', 'Expenses')

@section('content')
@php
    $items = $expenses['data'] ?? $expenses['items'] ?? (is_array($expenses) && !isset($expenses['id']) ? array_values($expenses) : []);
    $pagination = $expenses['pagination'] ?? $expenses['meta'] ?? null;
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-receipt me-2"></i>Expenses</span>
        <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Add Expense
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
                <i class="bi bi-receipt d-block"></i>
                <p>No expenses found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Project</th>
                            <th>Customer</th>
                            <th>Heading</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $expense)
                        <tr>
                            <td>{{ isset($expense['expenseDate']) ? date('d M Y', strtotime($expense['expenseDate'])) : '—' }}</td>
                            <td>{{ $expense['projectName'] ?? '—' }}</td>
                            <td>{{ $expense['customerName'] ?? '—' }}</td>
                            <td>{{ $expense['expenseHeading'] ?? '—' }}</td>
                            <td class="text-end fw-semibold" style="font-variant-numeric:tabular-nums;color:#1a3a5c;">{{ isset($expense['amount']) ? number_format((float)$expense['amount'], 2) : '—' }}</td>
                            <td class="text-center" style="white-space:nowrap;">
                                <a href="{{ route('expenses.edit', $expense['id']) }}" class="btn-action me-1" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('expenses.destroy', $expense['id']) }}" method="POST" class="d-inline delete-form" data-confirm-message="Are you sure you want to delete this expense?">
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
