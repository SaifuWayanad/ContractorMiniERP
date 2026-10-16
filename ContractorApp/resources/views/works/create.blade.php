@extends('layouts.app')

@section('title', 'Add Work')

@section('content')
<x-page-header title="Add work" subtitle="Fields marked * are required.">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('works.index') }}">Works</a></li>
        <li class="breadcrumb-item active" aria-current="page">New</li>
    </x-slot:breadcrumbs>
</x-page-header>

@if ($projects->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-building" title="Create a project first" message="Works belong to a project. Create an open project, then add its works.">
            @can('create', App\Models\Project::class)
                <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm">Create Project</a>
            @endcan
        </x-empty-state>
    </div>
@else
    <form method="post" action="{{ route('works.store') }}">
        @csrf
        @include('works.form')
        <x-form.actions :cancel="route('works.index')" submit="Create Work" />
    </form>
@endif
@endsection
