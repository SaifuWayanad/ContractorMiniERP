@extends('layouts.app')

@section('title', 'Contacts')

@section('content')
<x-page-header title="Contacts" subtitle="Material suppliers and dealers. Pick them on a project's material requirement.">
    <x-slot:actions>
        @can('create', App\Models\Dealer::class)
            <a href="{{ route('dealers.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Contact</a>
        @endcan
    </x-slot:actions>
</x-page-header>

@if ($dealers->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-shop" title="No contacts yet" message="Add the dealers and suppliers you buy materials from.">
            @can('create', App\Models\Dealer::class)
                <a href="{{ route('dealers.create') }}" class="btn btn-primary btn-sm">Add Contact</a>
            @endcan
        </x-empty-state>
    </div>
@else
    <x-table-card :paginator="$dealers">
        <table class="table table-hover align-middle">
            <thead><tr><th>Dealer</th><th>City</th><th class="d-none d-sm-table-cell">Phone</th><th class="text-center">Projects</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @foreach ($dealers as $dealer)
                    <tr>
                        <td class="fw-semibold">{{ $dealer->name }}</td>
                        <td>{{ $dealer->city ?? '—' }}</td>
                        <td class="d-none d-sm-table-cell">@if ($dealer->phone)<a href="tel:{{ $dealer->phone }}" class="text-decoration-none">{{ $dealer->phone }}</a>@else — @endif</td>
                        <td class="text-center">{{ $dealer->projects_count }}</td>
                        <td class="text-end text-nowrap">
                            @can('update', $dealer)
                                <a href="{{ route('dealers.edit', $dealer) }}" class="btn btn-icon btn-sm" aria-label="Edit {{ $dealer->name }}"><i class="bi bi-pencil"></i></a>
                            @endcan
                            @can('delete', $dealer)
                                <x-delete-button :action="route('dealers.destroy', $dealer)" label="" class="btn btn-icon btn-sm text-danger" title="Delete this dealer?" :confirm="'Delete “'.$dealer->name.'”? Projects that use it will keep showing its name.'" button="Delete" />
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table-card>
@endif
@endsection
