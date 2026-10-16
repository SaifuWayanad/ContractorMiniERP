@extends('layouts.app')

@section('title', 'Activity')

@section('content')
<x-page-header title="Activity" subtitle="Sign-ins, changes and completions across the platform." />

<x-filter-bar :action="route('admin.activity.index')" :has-filters="$hasFilters">
    <x-search-input :value="$filters['search']" placeholder="Search descriptions" />
    <select name="contractor_id" class="form-select" aria-label="Contractor" data-autosubmit>
        <option value="">All contractors</option>
        <option value="none" @selected($filters['contractor_id'] === 'none')>Admin activity only</option>
        @foreach ($contractors as $id => $name)
            <option value="{{ $id }}" @selected($filters['contractor_id'] === $id)>{{ $name }}</option>
        @endforeach
    </select>
    <select name="action" class="form-select" aria-label="Action" data-autosubmit>
        <option value="">All actions</option>
        @foreach (App\Http\Controllers\Admin\ActivityController::ACTIONS as $value => $label)
            <option value="{{ $value }}" @selected($filters['action'] === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <div class="toolbar-dates">
        <input type="date" name="from" value="{{ $filters['from'] }}" class="form-control" aria-label="From" />
        <span class="small text-muted">to</span>
        <input type="date" name="to" value="{{ $filters['to'] }}" class="form-control" aria-label="To" />
    </div>
</x-filter-bar>

@if ($activity->isEmpty())
    <div class="card"><x-empty-state icon="bi-clock-history" title="No activity found" :message="$hasFilters ? 'Try different filters.' : 'Activity will appear here as people use the app.'" /></div>
@else
    <x-table-card :paginator="$activity">
        <table class="table align-middle">
            <thead><tr><th>When</th><th>Activity</th><th class="d-none d-md-table-cell">Contractor</th><th class="d-none d-lg-table-cell">IP address</th></tr></thead>
            <tbody>
                @foreach ($activity as $entry)
                    <tr>
                        <td class="text-nowrap small" title="{{ $entry->created_at->format('d M Y, h:i:s A') }}">{{ $entry->created_at->format('d M Y') }}<div class="cell-sub">{{ $entry->created_at->format('h:i A') }}</div></td>
                        <td><i class="bi {{ $entry->icon() }} text-muted me-1"></i>{{ $entry->description }}</td>
                        <td class="d-none d-md-table-cell">{{ $entry->contractor?->company_name ?? 'Admin' }}</td>
                        <td class="d-none d-lg-table-cell"><code>{{ $entry->ip_address ?? '—' }}</code></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table-card>
@endif
@endsection
