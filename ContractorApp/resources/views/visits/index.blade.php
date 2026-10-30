@extends('layouts.app')

@section('title', 'Visits')

@section('content')
<x-page-header title="Visits" subtitle="Customer site visits and meetings.">
    <x-slot:actions>
        @can('create', App\Models\Visit::class)
            <a href="{{ route('visits.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Log Visit
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

{{-- Filters --}}
<form method="get" action="{{ route('visits.index') }}" class="mb-4" data-auto-filter>
    <div class="row g-2 align-items-end">
        <div class="col-sm-4 col-md-4">
            <input type="text" class="form-control form-control-sm" name="search" value="{{ $search }}" placeholder="Search purpose or notes…" />
        </div>
        <div class="col-sm-4 col-md-3">
            <div class="cs-wrapper cs-sm" id="visitClientFilterWrapper">
                <div class="cs-trigger">
                    <span class="cs-value {{ $clientId ? '' : 'cs-placeholder' }}">
                        {{ $clientId ? ($clients[$clientId] ?? 'All customers') : 'All customers' }}
                    </span>
                    <i class="bi bi-chevron-down cs-arrow"></i>
                </div>
                <div class="cs-panel">
                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                    <div class="cs-list">
                        <div class="cs-item" data-value="">All customers</div>
                        @foreach($clients as $id => $name)
                            <div class="cs-item" data-value="{{ $id }}">{{ $name }}</div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" name="client_id" id="visitClientFilterInput" value="{{ $clientId ?? '' }}">
            </div>
        </div>
        @if($search || $clientId)
        <div class="col-auto">
            <a href="{{ route('visits.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
        @endif
    </div>
</form>

@if ($visits->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-calendar-check" title="No visits yet" message="Log a site visit or customer meeting to get started.">
            @can('create', App\Models\Visit::class)
                <a href="{{ route('visits.create') }}" class="btn btn-primary btn-sm">Log Visit</a>
            @endcan
        </x-empty-state>
    </div>
@else
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Customer</th>
                        <th class="d-none d-md-table-cell">Purpose</th>
                        <th class="d-none d-md-table-cell">Location</th>
                        <th class="d-none d-lg-table-cell">Notes</th>
                        <th class="d-none d-sm-table-cell">Next Visit</th>
                        <th class="text-center"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($visits as $visit)
                    <tr>
                        <td class="text-nowrap">{{ $visit->meeting_date->format('d M Y') }}</td>
                        <td>
                            @if ($visit->client)
                                <a href="{{ route('clients.show', $visit->client) }}">{{ $visit->client->name }}</a>
                            @else —
                            @endif
                        </td>
                        <td class="d-none d-md-table-cell">{{ $visit->purpose ?? '—' }}</td>
                        <td class="d-none d-md-table-cell">{{ $visit->location ?? '—' }}</td>
                        <td class="d-none d-lg-table-cell">
                            @if ($visit->description)
                                <span class="text-muted" style="white-space:pre-line;">{{ \Illuminate\Support\Str::limit($visit->description, 80) }}</span>
                            @else —
                            @endif
                        </td>
                        <td class="d-none d-sm-table-cell text-nowrap">
                            {{ $visit->next_meeting_date?->format('d M Y') ?? '—' }}
                            @if ($visit->next_meeting_date && $visit->next_meeting_date->isPast())
                                <span class="badge badge-soft-danger ms-1">Past</span>
                            @elseif ($visit->next_meeting_date && $visit->next_meeting_date->isToday())
                                <span class="badge badge-soft-warning ms-1">Today</span>
                            @endif
                        </td>
                        <td class="text-center text-nowrap">
                            @can('update', $visit)
                                <a href="{{ route('visits.edit', $visit) }}" class="btn-action" title="Edit"><i class="bi bi-pencil"></i></a>
                            @endcan
                            @can('delete', $visit)
                                <x-delete-button :action="route('visits.destroy', $visit)" title="Delete visit?" confirm="Delete this visit record?" class="btn-action btn-action-danger" label="" />
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($visits->hasPages())
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Showing {{ $visits->firstItem() }}–{{ $visits->lastItem() }} of {{ $visits->total() }} visits
                </div>
                {{ $visits->links() }}
            </div>
        @else
            <div class="card-footer text-muted small">
                {{ $visits->total() }} {{ \Illuminate\Support\Str::plural('visit', $visits->total()) }}
            </div>
        @endif
    </div>
@endif
@push('scripts')
<script>
(function () {
    var form  = document.querySelector('form[data-auto-filter]');
    var cs    = new CustomSelect('visitClientFilterWrapper');
    var input = document.getElementById('visitClientFilterInput');
    var orig  = cs._select.bind(cs);
    cs._select = function(value, label) { orig(value, label); input.value = value; form.submit(); };
})();
</script>
@endpush
@endsection
