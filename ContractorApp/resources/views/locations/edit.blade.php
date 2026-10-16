@extends('layouts.app')

@section('title', 'Edit Location')

@section('content')
<x-page-header title="Edit location" :subtitle="$location->name.' · '.$project->name">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('locations.show', $location) }}">{{ $location->name }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('locations.update', $location) }}">
    @csrf
    @method('PUT')
    @include('locations.form')
    <x-form.actions :cancel="route('locations.show', $location)" submit="Save Changes" />
</form>
@endsection
