@extends('layouts.app')

@section('title', 'Products')

@section('content')
<x-page-header title="Products" subtitle="Materials and items used in your projects.">
    <x-slot:actions>
        @can('create', App\Models\Product::class)
            <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Product</a>
        @endcan
    </x-slot:actions>
</x-page-header>

@if ($products->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-box-seam" title="No products yet" message="Add products to quickly pick them when recording purchases.">
            @can('create', App\Models\Product::class)
                <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">Add Product</a>
            @endcan
        </x-empty-state>
    </div>
@else
    <x-table-card :paginator="$products">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Name</th>
                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $product)
                    <tr>
                        <td>
                            <span class="row-title">{{ $product->name }}</span>
                            @if ($product->description)
                                <div class="cell-sub">{{ Str::limit($product->description, 60) }}</div>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-icon btn-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions for {{ $product->name }}"><i class="bi bi-three-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @can('update', $product)
                                        <li><a class="dropdown-item" href="{{ route('products.edit', $product) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    @endcan
                                    @can('delete', $product)
                                        <li><hr class="dropdown-divider" /></li>
                                        <li><x-delete-button :action="route('products.destroy', $product)" class="dropdown-item text-danger" title="Delete product?" :confirm="'Delete &quot;'.$product->name.'&quot;? This cannot be undone.'" /></li>
                                    @endcan
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table-card>
@endif
@endsection
