@extends('layouts.app')

@section('title', 'Add Contact')

@section('content')
<x-page-header title="Add Contact" subtitle="Fields marked * are required.">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('admin.dealers.index') }}">Contacts</a></li>
        <li class="breadcrumb-item active" aria-current="page">New</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('admin.dealers.store') }}">
    @csrf
    <x-card class="mb-3">
        <x-form.section title="Contact Details" :first="true">
            <x-form.select name="contractor_id" label="Contractor" :options="$contractors" placeholder="Select contractor" :required="true" />
            <x-form.input name="name" label="Contact / Dealer Name" :required="true" maxlength="120" autocomplete="off" />
            <x-form.input name="city" label="City" maxlength="80" />
            <x-form.input name="phone" label="Phone" type="tel" maxlength="20" inputmode="tel" />
        </x-form.section>
    </x-card>
    <x-form.actions :cancel="route('admin.dealers.index')" submit="Add Contact" />
</form>
@endsection
