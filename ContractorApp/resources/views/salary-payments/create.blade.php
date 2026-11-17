@extends('layouts.app')

@section('title', 'Record Salary Payment')

@section('content')
<x-page-header title="Record Salary Payment" subtitle="Log an employee salary payment." :back="route('salary-payments.index')" />

<form method="post" action="{{ route('salary-payments.store') }}">
    @csrf
    @include('salary-payments._form')
    <x-form.actions :back="route('salary-payments.index')" submit="Record Payment" />
</form>
@endsection
