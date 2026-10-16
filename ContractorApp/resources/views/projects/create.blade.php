@extends('layouts.app')

@section('title', 'Create Project')

@section('content')
<x-page-header title="Create project" subtitle="Only the project name and customer are required. More options are available below the main fields.">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
        <li class="breadcrumb-item active" aria-current="page">New</li>
    </x-slot:breadcrumbs>
</x-page-header>

@if ($clients->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-person-vcard" title="Add a customer first" message="Every project belongs to a customer. Create a customer, then come back to create the project.">
            @can('create', App\Models\Client::class)
                <a href="{{ route("clients.create") }}" class="btn btn-primary btn-sm">Add Customer</a>
            @endcan
        </x-empty-state>
    </div>
@else
    <form method="post" action="{{ route('projects.store') }}">
        @csrf
        @include('projects.form')
        <x-form.actions :cancel="route('projects.index')" submit="Create Project" />
    </form>
@endif
@endsection
