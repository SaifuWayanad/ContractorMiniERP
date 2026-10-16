@extends('layouts.app')

@section('title', 'Add User')

@section('content')
<x-page-header title="Add user" subtitle="Passwords are stored hashed and never shown again.">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
        <li class="breadcrumb-item active" aria-current="page">New</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('admin.users.store') }}">
    @csrf
    @include('admin.users.form')
    <x-form.actions :cancel="route('admin.users.index')" submit="Create User" />
</form>
@endsection
