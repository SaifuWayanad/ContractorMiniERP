@extends('layouts.app')

@section('title', 'Locations')

@section('content')
<x-page-header title="Project Locations" subtitle="Every site across your projects.">
    <x-slot:actions>
        @can('create', App\Models\ProjectLocation::class)
            <a href="{{ route('locations.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Location</a>
        @endcan
    </x-slot:actions>
</x-page-header>

@if ($locations->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-geo-alt" title="No locations found" message="You haven't added any project locations yet.">
            @can('create', App\Models\ProjectLocation::class)
                <a href="{{ route('locations.create') }}" class="btn btn-primary btn-sm">Add Location</a>
            @endcan
        </x-empty-state>
    </div>
@else
    <x-table-card :paginator="$locations">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Project</th>
                    <th class="d-none d-lg-table-cell">Address</th>
                    <th class="d-none d-md-table-cell">Contact</th>
                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($locations as $location)
                    <tr>
                        <td>
                            <a href="{{ route('locations.show', $location) }}" class="row-title">{{ $location->name }}</a>
                            <div class="cell-sub">{{ $location->shortAddress() }}{{ $location->postal_code ? ' – '.$location->postal_code : '' }}</div>
                        </td>
                        <td>
                            <a href="{{ route('projects.show', $location->project) }}" class="text-decoration-none">{{ $location->project->name }}</a>
                            <div class="cell-sub">{{ $location->project->client?->name }}</div>
                        </td>
                        <td class="d-none d-lg-table-cell">{{ $location->address }}</td>
                        <td class="d-none d-md-table-cell">
                            {{ $location->contact_person ?? '—' }}
                            @if ($location->contact_phone) <div class="cell-sub"><a href="tel:{{ $location->contact_phone }}">{{ $location->contact_phone }}</a></div> @endif
                        </td>
                        <td class="text-end text-nowrap">
                            @can('update', $location)
                                <a href="{{ route('locations.edit', $location) }}" class="btn btn-icon btn-sm" aria-label="Edit {{ $location->name }}"><i class="bi bi-pencil"></i></a>
                            @endcan
                            @can('delete', $location)
                                <x-delete-button :action="route('locations.destroy', $location)" label="" class="btn btn-icon btn-sm text-danger" title="Delete this location?" :confirm="'Delete “'.$location->name.'”? This cannot be undone.'" button="Delete" />
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table-card>
@endif
@endsection
