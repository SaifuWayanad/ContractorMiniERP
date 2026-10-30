@extends('layouts.app')

@section('title', 'Add Product')

@section('content')
<x-page-header title="Add product" subtitle="Fields marked * are required.">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
        <li class="breadcrumb-item active" aria-current="page">New</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('products.store') }}">
    @csrf
    @include('products.form')
    <x-form.actions :cancel="route('products.index')" submit="Create Product" />
</form>
@endsection
