@extends('layouts.app')

@section('title', 'Add Contractor')

@section('content')
<x-page-header title="Add contractor" subtitle="Fields marked * are required.">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('admin.contractors.index') }}">Contractors</a></li>
        <li class="breadcrumb-item active" aria-current="page">New</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('admin.contractors.store') }}">
    @csrf
    @include('admin.contractors.form')
    <x-form.actions :cancel="route('admin.contractors.index')" submit="Create Contractor" />
</form>
@endsection
