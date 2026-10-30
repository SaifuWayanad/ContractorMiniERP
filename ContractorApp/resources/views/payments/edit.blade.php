@extends('layouts.app')

@section('title', 'Edit Payment')

@section('content')
<x-page-header title="Edit payment" subtitle="Fields marked * are required.">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('projects.show', $payment->project_id) }}">Project</a></li>
        <li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Payments</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('payments.update', $payment) }}">
    @csrf
    @method('PUT')
    @php($from = 'project')
    @include('payments.form')
    <x-form.actions :cancel="route('projects.show', $payment->project_id)" submit="Save Changes" />
</form>
@endsection
