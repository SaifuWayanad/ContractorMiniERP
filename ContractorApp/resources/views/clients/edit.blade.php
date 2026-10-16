@extends('layouts.app')

@section('title', 'Edit Customer')

@section('content')
<x-page-header title="Edit customer" :subtitle="$client->name">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Customers</a></li>
        <li class="breadcrumb-item"><a href="{{ route('clients.show', $client) }}">{{ $client->name }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('clients.update', $client) }}">
    @csrf
    @method('PUT')
    @include('clients.form', ['client' => $client])
    <x-form.actions :cancel="route('clients.show', $client)" submit="Save Changes" />
</form>
@endsection
