@extends('layouts.app')

@section('title', 'Add Employee')

@section('content')
<x-page-header title="Add Employee" subtitle="Add a new employee to your team." :back="route('employees.index')" />

<form method="post" action="{{ route('employees.store') }}">
    @csrf
    @include('employees._form')
    <x-form.actions :back="route('employees.index')" submit="Add Employee" />
</form>
@endsection
