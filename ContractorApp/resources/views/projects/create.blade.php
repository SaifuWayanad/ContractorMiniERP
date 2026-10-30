@extends('layouts.app')

@section('title', 'Create Project')

@section('content')
<x-page-header title="Create project" subtitle="Fields marked * are required.">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
        <li class="breadcrumb-item active" aria-current="page">New</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('projects.store') }}">
    @csrf
    @include('projects.form')
    <x-form.actions :cancel="route('projects.index')" submit="Create Project" />
</form>
@endsection
