@extends('layouts.app')

@section('title', 'Edit Project')

@section('content')
<x-page-header title="Edit project" :subtitle="$project->name">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
        <li class="breadcrumb-item"><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('projects.update', $project) }}">
    @csrf
    @method('PUT')
    @include('projects.form')
    <x-form.actions :cancel="route('projects.show', $project)" submit="Save Changes" />
</form>
@endsection
