@extends('layouts.app')

@section('title', 'All Contacts')

@section('content')
<x-page-header title="All Contacts" subtitle="Contacts across every contractor.">
    <x-slot:actions>
        @can('create', App\Models\Dealer::class)
            <a href="{{ route('admin.dealers.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Contact</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<x-filter-bar :action="route('admin.dealers.index')" :has-filters="$hasFilters">
    <input type="search" name="search" class="form-control" placeholder="Name, city, phone…" value="{{ $filters['search'] }}" aria-label="Search" data-autosubmit />
    <select name="contractor_id" class="form-select" aria-label="Contractor" data-autosubmit>
        <option value="">All contractors</option>
        @foreach ($contractors as $id => $name)
            <option value="{{ $id }}" @selected($filters['contractor_id'] === $id)>{{ $name }}</option>
        @endforeach
    </select>
</x-filter-bar>

@if ($dealers->isEmpty())
    <div class="card"><x-empty-state icon="bi-shop" title="No contacts yet" message="Contacts appear here once contractors add them." /></div>
@else
    <x-table-card :paginator="$dealers">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Contact</th>
                    <th>Contractor</th>
                    <th class="d-none d-md-table-cell">City</th>
                    <th class="d-none d-lg-table-cell">Phone</th>
                    <th class="d-none d-md-table-cell text-center">Projects</th>
                    <th class="d-none d-lg-table-cell">Added</th>
                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dealers as $dealer)
                    <tr>
                        <td class="fw-semibold">{{ $dealer->name }}</td>
                        <td>{{ $dealer->contractor?->company_name }}</td>
                        <td class="d-none d-md-table-cell">{{ $dealer->city ?? '—' }}</td>
                        <td class="d-none d-lg-table-cell">
                            @if ($dealer->phone)
                                <a href="tel:{{ $dealer->phone }}">{{ $dealer->phone }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="d-none d-md-table-cell text-center">{{ $dealer->projects_count }}</td>
                        <td class="d-none d-lg-table-cell text-nowrap">{{ $dealer->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-icon btn-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions for {{ $dealer->name }}">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @can('update', $dealer)
                                        <li><a class="dropdown-item" href="{{ route('admin.dealers.edit', $dealer) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    @endcan
                                    @can('delete', $dealer)
                                        <li><hr class="dropdown-divider" /></li>
                                        <li>
                                            <x-delete-button :action="route('admin.dealers.destroy', $dealer)" :confirm="$dealer->name.' will be permanently deleted.'" class="dropdown-item text-danger" />
                                        </li>
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
