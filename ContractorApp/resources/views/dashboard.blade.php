@extends('layouts.app')

@section('title', 'Dashboard')

@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    use App\Services\DashboardService;
@endphp

@section('content')
<x-page-header :title="$greeting.', '.$user->firstName()" subtitle="Here's an overview of your projects and business activity." />

{{-- 4 stat cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <x-stat-card label="Total Projects" :value="number_format($summary['projects'])" icon="bi-building" accent="info" :href="$user->can('projects.view') ? route('projects.index') : null" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Active Projects" :value="number_format($summary['active_projects'])" icon="bi-activity" accent="success" :href="$user->can('projects.view') ? route('projects.index', ['status' => 'active']) : null" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Pending Works" :value="number_format($summary['pending_works'])" icon="bi-list-check" accent="warning" :href="$user->can('works.view') ? route('works.index', ['status' => 'open']) : null" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Total Clients" :value="number_format($summary['clients'])" icon="bi-person-vcard" accent="primary" :href="$user->can('clients.view') ? route('clients.index') : null" />
    </div>
</div>

{{-- Meetings --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-xl-6">
        <x-card :flush="true" class="h-100 border-warning">
            <x-slot:title><span class="text-warning"><i class="bi bi-calendar-event me-1"></i>Today's Meetings</span></x-slot:title>
            <x-slot:actions><span class="badge badge-soft-warning">{{ today()->format('d M Y') }}</span></x-slot:actions>
            @if ($todaysMeetings->isEmpty())
                <div class="empty-inline"><i class="bi bi-calendar-check d-block fs-4 mb-1"></i>No meetings today.</div>
            @else
                <ul class="feed">
                    @foreach ($todaysMeetings as $meeting)
                        <li class="feed-item align-items-center">
                            <div class="deadline-date">
                                <span class="day">{{ $meeting->next_meeting_at->format('h:i') }}</span>
                                <span class="month">{{ $meeting->next_meeting_at->format('A') }}</span>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <a href="{{ route('projects.show', $meeting) }}" class="row-title d-block text-truncate">{{ $meeting->name }}</a>
                                <div class="feed-meta text-truncate">
                                    {{ $meeting->client?->name }}
                                    @if ($meeting->next_meeting_notes) · {{ $meeting->next_meeting_notes }} @endif
                                </div>
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
                    @foreach ($upcomingMeetings as $meeting)
                        @php $daysAway = (int) today()->diffInDays($meeting->next_meeting_at->toDateString(), false); @endphp
                        <li class="feed-item align-items-center">
                            <div class="deadline-date">
                                <span class="day">{{ $meeting->next_meeting_at->day }}</span>
                                <span class="month">{{ $meeting->next_meeting_at->format('M') }}</span>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <a href="{{ route('projects.show', $meeting) }}" class="row-title d-block text-truncate">{{ $meeting->name }}</a>
                                <div class="feed-meta text-truncate">
                                    {{ $meeting->client?->name }} · {{ $meeting->next_meeting_at->format('h:i A') }}
                                    @if ($meeting->next_meeting_notes) · {{ $meeting->next_meeting_notes }} @endif
                                </div>
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
                            <th>Project</th>
                            <th class="d-none d-md-table-cell">Client</th>
                            <th class="d-none d-sm-table-cell">Start Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentProjects as $project)
                            <tr>
                                <td>
                                    <a href="{{ route('projects.show', $project) }}" class="row-title">{{ $project->name }}</a>
                                    <div class="cell-sub">{{ $project->project_number }}<span class="d-md-none"> · {{ $project->client?->name }}</span></div>
                                </td>
                                <td class="d-none d-md-table-cell">{{ $project->client?->name }}</td>
                                <td class="d-none d-sm-table-cell text-nowrap">{{ $project->start_date?->format('d M Y') ?? '—' }}</td>
                                <td><x-status-badge :status="$project->status" /></td>
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
