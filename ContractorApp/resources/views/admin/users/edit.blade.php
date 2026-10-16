@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<x-page-header title="Edit user" :subtitle="$user->name">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('admin.users.update', $user) }}">
    @csrf
    @method('PUT')
    @include('admin.users.form')
    <x-form.actions :cancel="route('admin.users.index')" submit="Save Changes" />
</form>
@endsection
