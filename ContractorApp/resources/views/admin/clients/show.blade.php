@extends('layouts.app')

@section('title', $client->name)

@section('content')
<x-page-header :title="$client->name" :subtitle="'Client of '.$client->contractor->company_name">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">All Clients</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $client->name }}</li>
    </x-slot:breadcrumbs>
    <x-slot:actions>
        <x-status-badge :status="$client->status" />
        @can('view', $client->contractor)
            <a href="{{ route('admin.contractors.show', $client->contractor) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-briefcase me-1"></i>View contractor</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="row g-3">
    <div class="col-12 col-xl-4">
        <x-card title="Client Information">
            <dl class="detail-list">
                @if ($client->company)
                    <dt>Company</dt><dd>{{ $client->company }}</dd>
                @endif
                @if ($client->contact_person)
                    <dt>Contact</dt><dd>{{ $client->contact_person }}</dd>
                @endif
                @if ($client->email)
                    <dt>Email</dt><dd><a href="mailto:{{ $client->email }}">{{ $client->email }}</a></dd>
                @endif
                @if ($client->phone)
                    <dt>Phone</dt><dd><a href="tel:{{ $client->phone }}">{{ $client->phone }}</a></dd>
                @endif
                @php $address = collect([$client->address, $client->city, $client->state, $client->postal_code, $client->country])->filter()->implode(', '); @endphp
                @if ($address)
                    <dt>Address</dt><dd>{{ $address }}</dd>
                @endif
                <dt>Created</dt><dd>{{ $client->created_at->format('d M Y') }}</dd>
            </dl>
        </x-card>
    </div>
    <div class="col-12 col-xl-8">
        <x-table-card :title="'Projects ('.$projects->count().')'">
            @if ($projects->isEmpty())
                <div class="empty-inline">No projects for this client.</div>
            @else
                <table class="table table-hover align-middle">
                    <thead><tr><th>Project</th><th class="d-none d-md-table-cell">Location</th><th class="d-none d-sm-table-cell">Start</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($projects as $project)
                            <tr>
                                <td><a href="{{ route('admin.projects.show', $project) }}" class="row-title">{{ $project->name }}</a><div class="cell-sub">{{ $project->project_number }}</div></td>
                                <td class="d-none d-md-table-cell">{{ $project->primaryLocation?->shortAddress() ?? '—' }}</td>
                                <td class="d-none d-sm-table-cell text-nowrap">{{ $project->start_date?->format('d M Y') ?? '—' }}</td>
                                <td><x-status-badge :status="$project->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-table-card>
    </div>
</div>
@endsection
