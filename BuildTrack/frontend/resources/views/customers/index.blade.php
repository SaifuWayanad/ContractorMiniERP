@extends('layouts.app')

@section('title', 'Customers')

@section('content')
@php
    $items = $customers['data'] ?? $customers['items'] ?? (is_array($customers) && !isset($customers['id']) ? array_values($customers) : []);
    $pagination = $customers['pagination'] ?? $customers['meta'] ?? null;
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-people me-2"></i>Customers</span>
        <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Add Customer
        </a>
    </div>

    <div class="search-bar">
        <form method="GET" action="{{ route('customers.index') }}" id="searchForm" class="d-flex gap-2 align-items-center">
            <div class="input-group input-group-sm" style="max-width:380px;">
                <span class="input-group-text"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search by name, phone…" value="{{ request('search') }}">
            </div>
            @if(request('search'))
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle me-1"></i>Reset
                </a>
            @endif
        </form>
    </div>

    @if($error)
        <div class="px-3 pt-3">
            <div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-2"></i>{{ $error }}</div>
        </div>
    @endif

    <div class="card-body p-0">
        @if(empty($items))
            <div class="empty-state">
                <i class="bi bi-people d-block"></i>
                <p>No customers found.</p>
                <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">Add First Customer</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Location</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $customer)
                        @if(empty($customer['customerName'])) @continue @endif
                        <tr>
                            <td class="fw-semibold">
                                <a href="{{ route('customers.show', $customer['id']) }}">{{ $customer['customerName'] }}</a>
                            </td>
                            <td>{{ $customer['phoneNumber'] ?? '—' }}</td>
                            <td>{{ $customer['address'] ?? '—' }}</td>
                            <td>{{ $customer['locationName'] ?? '—' }}</td>
                            <td class="text-center" style="white-space:nowrap;">
                                <a href="{{ route('customers.show', $customer['id']) }}" class="btn-action me-1" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('customers.edit', $customer['id']) }}" class="btn-action me-1" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('customers.destroy', $customer['id']) }}" method="POST" class="d-inline delete-form" data-confirm-message="Are you sure you want to delete this customer?">
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

@section('scripts')
<script>
let _st;
const _sf = document.getElementById('searchForm');
_sf.querySelector('input[name="search"]').addEventListener('input', function() {
    clearTimeout(_st);
    _st = setTimeout(() => _sf.submit(), 500);
});
</script>
@endsection
