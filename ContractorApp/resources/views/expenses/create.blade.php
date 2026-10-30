@extends('layouts.app')

@section('title', 'Add Expense')

@section('content')
<x-page-header title="Add expense" subtitle="Fields marked * are required.">
    <x-slot:breadcrumbs>
        @if(request('from') === 'project' && request('project_id'))
            <li class="breadcrumb-item"><a href="{{ route('projects.show', request('project_id')) }}">Project</a></li>
        @else
            <li class="breadcrumb-item"><a href="{{ route('expenses.index') }}">Expenses</a></li>
        @endif
        <li class="breadcrumb-item active" aria-current="page">New</li>
    </x-slot:breadcrumbs>
</x-page-header>

@if ($projects->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-building" title="Create a project first" message="Expenses are linked to a project. Create an open project first.">
            @can('create', App\Models\Project::class)
                <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm">Create Project</a>
            @endcan
        </x-empty-state>
    </div>
@else
    <form method="post" action="{{ route('expenses.store') }}">
        @csrf
        @php($from = request('from'))
        @include('expenses.form')
        <x-form.actions :cancel="request('from') === 'project' && request('project_id') ? route('projects.show', request('project_id')) : route('expenses.index')" submit="Add Expense" />
    </form>
@endif
@endsection
