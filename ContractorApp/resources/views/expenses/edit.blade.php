@extends('layouts.app')

@section('title', 'Edit Expense')

@section('content')
<x-page-header title="Edit expense" subtitle="Fields marked * are required.">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('projects.show', $expense->project_id) }}">Project</a></li>
        <li class="breadcrumb-item"><a href="{{ route('expenses.index') }}">Expenses</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('expenses.update', $expense) }}">
    @csrf
    @method('PUT')
    @php($from = 'project')
    @include('expenses.form')
    <x-form.actions :cancel="route('projects.show', $expense->project_id)" submit="Save Changes" />
</form>
@endsection
