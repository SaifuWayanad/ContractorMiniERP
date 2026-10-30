@extends('layouts.app')

@section('title', 'Edit Product')

@section('content')
<x-page-header title="Edit product" subtitle="Fields marked * are required.">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('products.update', $product) }}">
    @csrf
    @method('PUT')
    @include('products.form')
    <x-form.actions :cancel="route('products.index')" submit="Save Changes" />
</form>
@endsection
