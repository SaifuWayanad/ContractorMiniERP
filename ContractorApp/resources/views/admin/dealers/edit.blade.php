@extends('layouts.app')

@section('title', 'Edit Contact')

@section('content')
<x-page-header :title="'Edit: '.$dealer->name">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('admin.dealers.index') }}">Contacts</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('admin.dealers.update', $dealer) }}">
    @csrf
    @method('PUT')
    <x-card class="mb-3">
        <x-form.section title="Contact Details" :first="true">
            <x-form.select name="contractor_id" label="Contractor" :options="$contractors" :value="$dealer->contractor_id" placeholder="Select contractor" :required="true" />
            <x-form.input name="name" label="Contact / Dealer Name" :value="$dealer->name" :required="true" maxlength="120" />
            <x-form.input name="city" label="City" :value="$dealer->city" maxlength="80" />
            <x-form.input name="phone" label="Phone" type="tel" :value="$dealer->phone" maxlength="20" inputmode="tel" />
        </x-form.section>
    </x-card>
    <x-form.actions :cancel="route('admin.dealers.index')" submit="Save Changes" />
</form>
@endsection
