@extends('layouts.app')

@section('title', 'Log Visit')

@section('content')
<x-page-header title="Log visit" subtitle="Fields marked * are required.">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('visits.index') }}">Visits</a></li>
        <li class="breadcrumb-item active" aria-current="page">New</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('visits.store') }}">
    @csrf
    @include('visits.form')
    <x-form.actions :cancel="route('visits.index')" submit="Log Visit" />
</form>
@endsection
