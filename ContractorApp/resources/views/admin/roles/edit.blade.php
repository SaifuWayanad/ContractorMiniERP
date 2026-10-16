@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')
<x-page-header title="Edit role" :subtitle="$role->name">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Roles</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('admin.roles.update', $role) }}">
    @csrf
    @method('PUT')
    @include('admin.roles.form')
    <x-form.actions :cancel="route('admin.roles.index')" submit="Save Changes" />
</form>
@endsection
