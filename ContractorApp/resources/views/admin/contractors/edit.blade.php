@extends('layouts.app')

@section('title', 'Edit Contractor')

@section('content')
<x-page-header title="Edit contractor" :subtitle="$contractor->company_name">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('admin.contractors.index') }}">Contractors</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.contractors.show', $contractor) }}">{{ $contractor->company_name }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('admin.contractors.update', $contractor) }}">
    @csrf
    @method('PUT')
    @include('admin.contractors.form')
    <x-form.actions :cancel="route('admin.contractors.show', $contractor)" submit="Save Changes" />
</form>
@endsection
