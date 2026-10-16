@extends('layouts.app')

@section('title', 'Add Location')

@section('content')
<x-page-header title="Add location" :subtitle="'For project: '.$project->name">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
        <li class="breadcrumb-item"><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">New location</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('projects.locations.store', $project) }}">
    @csrf
    @include('locations.form')
    <x-form.actions :cancel="route('projects.show', $project).'#locations'" submit="Add Location" />
</form>
@endsection
