@extends('layouts.app')

@section('title', 'Edit Contact')

@section('content')
<x-page-header title="Edit contact" :subtitle="$dealer->name">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('dealers.index') }}">Contacts</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('dealers.update', $dealer) }}" style="max-width: 900px">
    @csrf
    @method('PUT')
    @include('dealers.form')
    <x-form.actions :cancel="route('dealers.index')" submit="Save Changes" />
</form>
@endsection
