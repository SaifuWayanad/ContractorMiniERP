@extends('layouts.app')

@section('title', 'Edit Work')

@section('content')
<x-page-header title="Edit work" :subtitle="$work->title">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('works.index') }}">Works</a></li>
        <li class="breadcrumb-item"><a href="{{ route('works.show', $work) }}">{{ $work->title }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('works.update', $work) }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="from" value="{{ request('from') }}">
    <input type="hidden" name="from_project_id" value="{{ request('from_project_id', $work->project_id) }}">
    @include('works.form')
    <x-form.actions :cancel="route('works.show', $work)" submit="Save Changes" />
</form>
@endsection
