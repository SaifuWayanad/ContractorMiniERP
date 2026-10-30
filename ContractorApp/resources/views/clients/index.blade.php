@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<x-page-header title="Customers" subtitle="People and companies you work for.">
    <x-slot:actions>
        @can('create', App\Models\Client::class)
            <a href="{{ route('clients.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Customer</a>
        @endcan
    </x-slot:actions>
</x-page-header>

@if ($clients->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-person-vcard" title="No customers found" message="You haven't added any customers yet.">
            @can('create', App\Models\Client::class)
                <a href="{{ route('clients.create') }}" class="btn btn-primary btn-sm">Add Customer</a>
            @endcan
        </x-empty-state>
    </div>
@else
    <x-table-card :paginator="$clients">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th class="d-none d-sm-table-cell">Phone</th>
                    <th class="text-center">Projects</th>
                    <th>Status</th>
                    <th class="d-none d-lg-table-cell">Created</th>
                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($clients as $client)
                    <tr>
                        <td>
                            <a href="{{ route('clients.show', $client) }}" class="row-title">{{ $client->name }}</a>
                        </td>
                        <td class="d-none d-sm-table-cell text-nowrap">
                            @if ($client->phone) <a href="tel:{{ $client->phone }}" class="text-decoration-none">{{ $client->phone }}</a> @else — @endif
                        </td>
                        <td class="text-center">{{ $client->projects_count }}</td>
                        <td><x-status-badge :status="$client->status" /></td>
                        <td class="d-none d-lg-table-cell text-nowrap">{{ $client->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-icon btn-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions for {{ $client->name }}"><i class="bi bi-three-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('clients.show', $client) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                    @can('update', $client)
                                        <li><a class="dropdown-item" href="{{ route('clients.edit', $client) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    @endcan
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
