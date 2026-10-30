@extends('layouts.app')

@section('title', $client->name)

@section('content')
<x-page-header :title="$client->name" :subtitle="$client->company">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Clients</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $client->name }}</li>
    </x-slot:breadcrumbs>
    <x-slot:actions>
        @can('update', $client)
            <a href="{{ route('clients.edit', $client) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="row g-3">
    <div class="col-12 col-xl-4 d-flex flex-column gap-3">
        <x-card title="Client Information">
            <dl class="detail-list">
                <dt>Client name</dt><dd>{{ $client->name }}</dd>
                @if ($client->company)
                    <dt>Company</dt><dd>{{ $client->company }}</dd>
                @endif
                <dt>Created</dt><dd>{{ $client->created_at->format('d M Y') }}</dd>
            </dl>
        </x-card>
        @if ($client->contact_person || $client->email || $client->phone)
            <x-card title="Contact Information">
                <dl class="detail-list">
                    @if ($client->contact_person)
                        <dt>Contact person</dt><dd>{{ $client->contact_person }}</dd>
                    @endif
                    @if ($client->email)
                        <dt>Email</dt><dd><a href="mailto:{{ $client->email }}">{{ $client->email }}</a></dd>
                    @endif
                    @if ($client->phone)
                        <dt>Phone</dt><dd><a href="tel:{{ $client->phone }}">{{ $client->phone }}</a></dd>
                    @endif
                </dl>
            </x-card>
        @endif
        @if ($client->address || $client->city || $client->state || $client->country || $client->postal_code)
            <x-card title="Address">
                <dl class="detail-list">
                    @if ($client->address)
                        <dt>Address</dt><dd>{{ $client->address }}</dd>
                    @endif
                    @if ($client->city)
                        <dt>City</dt><dd>{{ $client->city }}</dd>
                    @endif
                    @if ($client->state)
                        <dt>State</dt><dd>{{ $client->state }}</dd>
                    @endif
                    @if ($client->country)
                        <dt>Country</dt><dd>{{ $client->country }}</dd>
                    @endif
                    @if ($client->postal_code)
                        <dt>Postal code</dt><dd>{{ $client->postal_code }}</dd>
                    @endif
                </dl>
            </x-card>
        @endif
        @if ($client->notes)
            <x-card title="Notes"><p class="mb-0" style="white-space: pre-line">{{ $client->notes }}</p></x-card>
        @endif
    </div>

    <div class="col-12 col-xl-8 d-flex flex-column gap-3">
        <x-table-card :title="'Projects ('.$projects->count().')'">
            <x-slot:headerActions>
                @can('create', App\Models\Project::class)
                    <a href="{{ route('projects.create', ['client_id' => $client->id]) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New project</a>
                @endcan
            </x-slot:headerActions>
            @if ($projects->isEmpty())
                <x-empty-state icon="bi-building" title="No projects yet" message="This client doesn't have any projects yet." class="py-4" />
            @else
                <table class="table table-hover align-middle">
                    <thead><tr><th>Project</th><th class="d-none d-md-table-cell">Location</th><th class="d-none d-sm-table-cell">Start</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($projects as $project)
                            <tr>
                                <td><a href="{{ route('projects.show', $project) }}" class="row-title">{{ $project->name }}</a><div class="cell-sub">{{ $project->project_number }}</div></td>
                                <td class="d-none d-md-table-cell">{{ $project->primaryLocation?->shortAddress() ?? '—' }}</td>
                                <td class="d-none d-sm-table-cell text-nowrap">{{ $project->start_date?->format('d M Y') ?? '—' }}</td>
                                <td><x-status-badge :status="$project->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-table-card>

        @can('activity.view')
            <x-card title="Recent Activity" :flush="true">
                @include('partials.activity-feed', ['activity' => $activity])
            </x-card>
        @endcan
    </div>
</div>
@endsection
