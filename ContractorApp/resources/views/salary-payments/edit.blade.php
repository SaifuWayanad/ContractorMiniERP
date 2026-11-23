@extends('layouts.app')

@section('title', 'Edit Salary Payment')

@section('content')
<x-page-header title="Edit Salary Payment" :subtitle="$payment->employee->name ?? 'Payment'" :back="route('salary-payments.index')" />

<form method="post" action="{{ route('salary-payments.update', $payment) }}">
    @csrf
    @method('PUT')
    @include('salary-payments._form')
    <x-form.actions :cancel="route('salary-payments.index')" submit="Save Changes" />
</form>
@endsection
