@extends('layouts.app')

@section('title', 'All Locations')

@section('content')
<x-page-header title="All Locations" subtitle="Project sites across every contractor. Read-only." />

<x-filter-bar :action="route('admin.locations.index')" :has-filters="$hasFilters">
    <select name="state" class="form-select" aria-label="State" data-autosubmit>
        <option value="">All states</option>
        @foreach ($states as $state)
            <option value="{{ $state }}" @selected($filters['state'] === $state)>{{ $state }}</option>
        @endforeach
    </select>
</x-filter-bar>

@if ($locations->isEmpty())
    <div class="card"><x-empty-state icon="bi-geo-alt" title="No locations yet" message="Locations appear here once contractors add them." /></div>
@else
    <x-table-card :paginator="$locations">
        <table class="table table-hover align-middle">
            <thead><tr><th>Location</th><th>Contractor</th><th class="d-none d-md-table-cell">Project</th><th class="d-none d-lg-table-cell">Address</th><th class="d-none d-xl-table-cell">Contact</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @foreach ($locations as $location)
                    <tr>
                        <td><span class="fw-semibold">{{ $location->name }}</span><div class="cell-sub">{{ $location->shortAddress() }}</div></td>
                        <td>{{ $location->contractor?->company_name }}</td>
                        <td class="d-none d-md-table-cell">@if ($location->project)<a href="{{ route('admin.projects.show', $location->project) }}" class="text-decoration-none">{{ $location->project->name }}</a>@endif</td>
                        <td class="d-none d-lg-table-cell">{{ $location->address }}</td>
                        <td class="d-none d-xl-table-cell">{{ $location->contact_person ?? '—' }}</td>
                        <td class="text-end">
                            @if ($location->project)
                                <a href="{{ route('admin.projects.show', $location->project) }}" class="btn btn-icon btn-sm" title="View project"><i class="bi bi-eye"></i></a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table-card>
@endif
@endsection
