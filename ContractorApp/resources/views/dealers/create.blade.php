@extends('layouts.app')

@section('title', 'Add Contact')

@section('content')
<x-page-header title="Add contact">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('dealers.index') }}">Contacts</a></li>
        <li class="breadcrumb-item active" aria-current="page">New</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('dealers.store') }}" style="max-width: 900px">
    @csrf
    @include('dealers.form')
    <x-form.actions :cancel="route('dealers.index')" submit="Add Contact" />
</form>
@endsection
