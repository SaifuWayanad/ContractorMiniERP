@extends('layouts.app')

@section('title', 'Edit Project')

@section('content')
<x-page-header title="Edit Project" :subtitle="$project->name">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('admin.projects.index') }}">All Projects</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.projects.show', $project) }}">{{ $project->name }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('admin.projects.update', $project) }}">
    @csrf
    @method('PUT')
    @include('projects.form', ['project' => $project, 'clients' => $clients, 'dealers' => $dealers])
    <x-form.actions :cancel="route('admin.projects.show', $project)" submit="Save Changes" />
</form>
@endsection
