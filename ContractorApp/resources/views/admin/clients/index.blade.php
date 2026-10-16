@extends('layouts.app')

@section('title', 'All Clients')

@section('content')
<x-page-header title="All Clients" subtitle="Customers across every contractor.">
    <x-slot:actions>
        @can('create', App\Models\Client::class)
            <a href="{{ route('admin.clients.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Customer</a>
        @endcan
    </x-slot:actions>
</x-page-header>

@if ($clients->isEmpty())
    <div class="card"><x-empty-state icon="bi-person-vcard" title="No clients yet" message="Clients appear here once contractors add them." /></div>
@else
    <x-table-card :paginator="$clients">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Contractor</th>
                    <th class="d-none d-lg-table-cell">Contact</th>
                    <th class="d-none d-md-table-cell text-center">Projects</th>
                    <th>Status</th>
                    <th class="d-none d-lg-table-cell">Created</th>
                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($clients as $client)
                    <tr>
                        <td><a href="{{ route('admin.clients.show', $client) }}" class="row-title">{{ $client->name }}</a>@if ($client->company)<div class="cell-sub">{{ $client->company }}</div>@endif</td>
                        <td>{{ $client->contractor?->company_name }}</td>
                        <td class="d-none d-lg-table-cell">{{ $client->contact_person ?? '—' }}@if ($client->phone)<div class="cell-sub">{{ $client->phone }}</div>@endif</td>
                        <td class="d-none d-md-table-cell text-center">{{ $client->projects_count }}</td>
                        <td><x-status-badge :status="$client->status" /></td>
                        <td class="d-none d-lg-table-cell text-nowrap">{{ $client->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-icon btn-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions for {{ $client->name }}"><i class="bi bi-three-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('admin.clients.show', $client) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.clients.edit', $client) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    <li><hr class="dropdown-divider" /></li>
                                    <li>
                                        <x-delete-button :action="route('admin.clients.destroy', $client)" title="Delete client?" :confirm="$client->name.' will be permanently deleted.'" class="dropdown-item text-danger" />
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table-card>
@endif
@endsection
