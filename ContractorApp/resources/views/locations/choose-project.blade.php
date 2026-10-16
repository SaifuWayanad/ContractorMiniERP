@extends('layouts.app')

@section('title', 'Add Location')

@section('content')
<x-page-header title="Add location" subtitle="Locations belong to a project. Choose the project first.">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('locations.index') }}">Locations</a></li>
        <li class="breadcrumb-item active" aria-current="page">New</li>
    </x-slot:breadcrumbs>
</x-page-header>

<div class="card form-card" style="max-width: 560px">
    <div class="card-body">
        @if ($projects->isEmpty())
            <x-empty-state icon="bi-building" title="No open projects" message="Create a project first, then add its site locations." class="py-2">
                @can('create', App\Models\Project::class)
                    <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm">Create Project</a>
                @endcan
            </x-empty-state>
        @else
            <form method="get" action="{{ route('locations.create') }}">
                <div class="row g-3">
                    <x-form.select name="project_id" label="Project" :options="$projects" placeholder="Select project" :required="true" col="col-12" />
                </div>
                <div class="form-actions">
                    <a href="{{ route('locations.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Continue <i class="bi bi-arrow-right ms-1"></i></button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
