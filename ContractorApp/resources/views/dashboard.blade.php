@extends('layouts.app')

@section('title', 'Dashboard')

@php use App\Services\DashboardService; @endphp

@section('content')
<x-page-header :title="'Welcome, '.$user->firstName()" subtitle="Here's an overview of your projects and business activity." />

{{-- 6 KPI stat cards (BuildTrack layout: 3 per row) --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-4">
        <x-stat-card label="Total Projects" :value="number_format($summary['projects'])" icon="bi-building" accent="primary" :href="$user->can('projects.view') ? route('projects.index') : null" />
    </div>
    <div class="col-6 col-lg-4">
        <x-stat-card label="Total Customers" :value="number_format($summary['clients'])" icon="bi-people" accent="info" :href="$user->can('clients.view') ? route('clients.index') : null" />
    </div>
    <div class="col-6 col-lg-4">
        <x-stat-card label="Purchase Value" :value="'₹'.number_format($summary['purchase_value'], 2)" icon="bi-cart3" accent="warning" :href="$user->can('purchases.view') ? route('purchases.index') : null" />
    </div>
    <div class="col-6 col-lg-4">
        <x-stat-card label="Total Expenses" :value="'₹'.number_format($summary['total_expenses'], 2)" icon="bi-receipt" accent="danger" :href="$user->can('expenses.view') ? route('expenses.index') : null" />
    </div>
    <div class="col-6 col-lg-4">
        <x-stat-card label="Total Receipts" :value="'₹'.number_format($summary['total_receipts'], 2)" icon="bi-cash-coin" accent="success" :href="$user->can('payments.view') ? route('payments.index') : null" />
    </div>
    <div class="col-6 col-lg-4">
        <x-stat-card label="Outstanding" :value="'₹'.number_format($summary['outstanding'], 2)" icon="bi-hourglass-split" accent="secondary" :href="$user->can('reports.view') ? route('reports.outstanding') : null" />
    </div>
</div>

{{-- Meetings --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-xl-6">
        <x-card :flush="true" class="h-100 border-warning">
            <x-slot:title><i class="bi bi-calendar-event me-1 text-warning"></i>Today's Meetings</x-slot:title>
            <x-slot:actions><span class="badge badge-soft-warning">{{ today()->format('d M Y') }}</span></x-slot:actions>
            @if ($todaysMeetings->isEmpty())
                <div class="empty-inline"><i class="bi bi-calendar-check d-block fs-4 mb-1"></i>No meetings today.</div>
            @else
                <ul class="feed">
                    @foreach ($todaysMeetings as $visit)
                        <li class="feed-item align-items-center">
                            <div class="deadline-date">
                                <span class="day">{{ $visit->next_meeting_date->format('d') }}</span>
                                <span class="month">{{ $visit->next_meeting_date->format('M') }}</span>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <span class="row-title d-block text-truncate">{{ $visit->purpose ?? 'Visit' }}</span>
                                <div class="feed-meta text-truncate">{{ $visit->client?->name }}@if($visit->location) · {{ $visit->location }}@endif</div>
                            </div>
                            <span class="badge badge-soft-warning">Today</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
    <div class="col-12 col-xl-6">
        <x-card :flush="true" class="h-100">
            <x-slot:title><i class="bi bi-calendar-week me-1 text-info"></i>Upcoming Meetings</x-slot:title>
            <x-slot:actions><span class="small text-muted">Next 30 days</span></x-slot:actions>
            @if ($upcomingMeetings->isEmpty())
                <div class="empty-inline"><i class="bi bi-calendar d-block fs-4 mb-1"></i>No upcoming meetings.</div>
            @else
                <ul class="feed">
                    @foreach ($upcomingMeetings as $visit)
                        @php $daysAway = (int) today()->diffInDays($visit->next_meeting_date, false); @endphp
                        <li class="feed-item align-items-center">
                            <div class="deadline-date">
                                <span class="day">{{ $visit->next_meeting_date->day }}</span>
                                <span class="month">{{ $visit->next_meeting_date->format('M') }}</span>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <span class="row-title d-block text-truncate">{{ $visit->purpose ?? 'Visit' }}</span>
                                <div class="feed-meta text-truncate">{{ $visit->client?->name }}@if($visit->location) · {{ $visit->location }}@endif</div>
                            </div>
                            <span @class(['badge text-nowrap', 'badge-soft-info' => $daysAway > 2, 'badge-soft-warning' => $daysAway <= 2])>
                                {{ $daysAway === 1 ? 'Tomorrow' : "In {$daysAway} days" }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</div>

{{-- Recent projects + Upcoming deadlines --}}
<div class="row g-3">
    <div class="col-12 col-xl-8">
        <x-table-card title="Recent Projects" class="h-100">
            <x-slot:headerActions>
                @can('projects.view') <a href="{{ route('projects.index') }}" class="card-link">View All</a> @endcan
            </x-slot:headerActions>
            @if ($recentProjects->isEmpty())
                <x-empty-state icon="bi-building" title="No projects yet" message="You haven't created any projects yet." />
            @else
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Project Name</th>
                            <th class="d-none d-md-table-cell">Customer</th>
                            <th class="d-none d-sm-table-cell text-end">Budget</th>
                            <th class="d-none d-sm-table-cell">Added</th>
                            <th class="text-center">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentProjects as $project)
                            <tr>
                                <td>
                                    <a href="{{ route('projects.show', $project) }}" class="row-title">{{ $project->name }}</a>
                                    <div class="cell-sub d-md-none">{{ $project->client?->name }}</div>
                                </td>
                                <td class="d-none d-md-table-cell">{{ $project->client?->name ?? '—' }}</td>
                                <td class="d-none d-sm-table-cell text-end">
                                    {{ $project->budget ? '₹'.number_format((float)$project->budget, 2) : '—' }}
                                </td>
                                <td class="d-none d-sm-table-cell text-nowrap">{{ $project->created_at->format('d M Y') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('projects.show', $project) }}" class="btn btn-icon btn-sm text-primary" aria-label="View {{ $project->name }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-table-card>
    </div>
    <div class="col-12 col-xl-4">
        <x-card title="Upcoming Deadlines" :flush="true" class="h-100">
            <x-slot:actions><span class="small text-muted">Next {{ DashboardService::DEADLINE_WINDOW_DAYS }} days</span></x-slot:actions>
            @if ($deadlines === [])
                <div class="empty-inline"><i class="bi bi-calendar-check d-block fs-4 mb-1"></i>Nothing due in the next {{ DashboardService::DEADLINE_WINDOW_DAYS }} days.</div>
            @else
                <ul class="feed">
                    @foreach ($deadlines as $deadline)
                        <li class="feed-item align-items-center">
                            <div class="deadline-date">
                                <span class="day">{{ $deadline['date']->day }}</span>
                                <span class="month">{{ $deadline['date']->format('M') }}</span>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <a href="{{ $deadline['url'] }}" class="row-title d-block text-truncate">{{ $deadline['title'] }}</a>
                                <div class="feed-meta text-truncate">{{ $deadline['type'] }}{{ $deadline['subtitle'] ? ' · '.$deadline['subtitle'] : '' }}</div>
                            </div>
                            <span @class(['badge text-nowrap', 'badge-soft-danger' => $deadline['days'] < 0, 'badge-soft-warning' => $deadline['days'] >= 0 && $deadline['days'] <= 2, 'badge-soft-info' => $deadline['days'] > 2])>
                                {{ DashboardService::dueLabel($deadline['days']) }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</div>
@endsection
