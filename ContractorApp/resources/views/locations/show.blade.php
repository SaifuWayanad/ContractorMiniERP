@extends('layouts.app')

@section('title', $location->name)

@section('content')
<x-page-header :title="$location->name" :subtitle="$location->shortAddress()">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('locations.index') }}">Locations</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $location->name }}</li>
    </x-slot:breadcrumbs>
    <x-slot:actions>
        @can('delete', $location)
            <x-delete-button :action="route('locations.destroy', $location)" title="Delete this location?" :confirm="'Delete “'.$location->name.'” from '.$location->project->name.'? This cannot be undone.'" />
        @endcan
        @can('update', $location)
            <a href="{{ route('locations.edit', $location) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <x-card title="Location">
            <dl class="detail-list">
                <dt>Project</dt><dd><a href="{{ route('projects.show', $location->project) }}">{{ $location->project->name }}</a></dd>
                <dt>Client</dt><dd>{{ $location->project->client?->name ?? '—' }}</dd>
                <dt>Address</dt><dd>{{ $location->address }}</dd>
                <dt>City</dt><dd>{{ $location->city }}</dd>
                <dt>District</dt><dd>{{ $location->district ?? '—' }}</dd>
                <dt>State</dt><dd>{{ $location->state }}</dd>
                <dt>Country</dt><dd>{{ $location->country }}</dd>
                <dt>Postal code</dt><dd>{{ $location->postal_code ?? '—' }}</dd>
            </dl>
        </x-card>
    </div>
    <div class="col-12 col-lg-6 d-flex flex-column gap-3">
        <x-card title="Site Contact">
            <dl class="detail-list">
                <dt>Contact person</dt><dd>{{ $location->contact_person ?? '—' }}</dd>
                <dt>Phone</dt><dd>@if ($location->contact_phone)<a href="tel:{{ $location->contact_phone }}">{{ $location->contact_phone }}</a>@else — @endif</dd>
            </dl>
        </x-card>
        <x-card title="Map">
            @if ($location->latitude !== null && $location->longitude !== null)
                <p class="mb-2">{{ $location->latitude }}, {{ $location->longitude }}</p>
                <a href="https://www.google.com/maps/search/?api=1&query={{ $location->latitude }},{{ $location->longitude }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm"><i class="bi bi-map me-1"></i>Open in Google Maps</a>
            @else
                <p class="text-muted mb-0">No coordinates saved. Add latitude and longitude to link to the map.</p>
            @endif
        </x-card>
        @if ($location->notes)
            <x-card title="Notes"><p class="mb-0" style="white-space: pre-line">{{ $location->notes }}</p></x-card>
        @endif
    </div>
</div>
@endsection
