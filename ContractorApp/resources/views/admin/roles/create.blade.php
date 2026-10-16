@extends('layouts.app')

@section('title', 'Add Role')

@section('content')
<x-page-header title="Add role">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Roles</a></li>
        <li class="breadcrumb-item active" aria-current="page">New</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('admin.roles.store') }}">
    @csrf
    @include('admin.roles.form')
    <x-form.actions :cancel="route('admin.roles.index')" submit="Create Role" />
</form>
@endsection
