@extends('layouts.app')

@section('title', 'Products')

@section('content')
@php
    $items = $products['data'] ?? $products['items'] ?? (is_array($products) && !isset($products['id']) ? array_values($products) : []);
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-box-seam me-2"></i>Products</span>
        <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Add Product
        </a>
    </div>

    <div class="search-bar">
        <form method="GET" action="{{ route('products.index') }}" id="searchForm" class="d-flex gap-2 align-items-center">
            <div class="input-group input-group-sm" style="max-width:380px;">
                <span class="input-group-text"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search products…" value="{{ request('search') }}">
            </div>
            @if(request('search'))
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
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
                <i class="bi bi-box-seam d-block"></i>
                <p>No products found.</p>
                <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">Add First Product</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $product)
                        @if(empty($product['productName'])) @continue @endif
                        <tr>
                            <td class="fw-semibold" style="color:#1e293b;">{{ $product['productName'] }}</td>
                            <td class="text-center" style="white-space:nowrap;">
                                <a href="{{ route('products.edit', $product['id']) }}" class="btn-action me-1" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('products.destroy', $product['id']) }}" method="POST" class="d-inline delete-form" data-confirm-message="Are you sure you want to delete this product?">
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
            @include('_partials.pagination', ['pagination' => ['total' => count($items)]])
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
