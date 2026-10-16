@extends('layouts.app')

@section('title', 'Add Customer')

@section('content')
<x-page-header title="Add customer">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Customers</a></li>
        <li class="breadcrumb-item active" aria-current="page">New</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('clients.store') }}">
    @csrf
    @include('clients.form', ['client' => $client])
    <x-form.actions :cancel="route('clients.index')" submit="Add Customer" />
</form>
@endsection
