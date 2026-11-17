@extends('layouts.app')

@section('title', 'Edit Employee')

@section('content')
<x-page-header title="Edit Employee" :subtitle="$employee->name" :back="route('employees.show', $employee)" />

<form method="post" action="{{ route('employees.update', $employee) }}">
    @csrf
    @method('PUT')
    @include('employees._form')
    <x-form.actions :back="route('employees.show', $employee)" submit="Save Changes" />
</form>
@endsection
