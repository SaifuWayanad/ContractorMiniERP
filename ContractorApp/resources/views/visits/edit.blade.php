@extends('layouts.app')

@section('title', 'Edit Visit')

@section('content')
<x-page-header title="Edit visit" subtitle="Fields marked * are required.">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('visits.index') }}">Visits</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('visits.update', $visit) }}">
    @csrf @method('PUT')
    @include('visits.form')
    <x-form.actions :cancel="route('visits.index')" submit="Update Visit" />
</form>
@endsection
