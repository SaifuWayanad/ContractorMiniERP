@extends('layouts.app')

@section('title', $project->name)

@use('Illuminate\Support\Number')

@section('content')
<x-page-header :title="$project->name" :subtitle="$project->project_number.' · '.$project->contractor->company_name">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('admin.projects.index') }}">All Projects</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $project->project_number }}</li>
    </x-slot:breadcrumbs>
    <x-slot:actions>
        <x-status-badge :status="$project->status" />
        @can('view', $project->contractor)
            <a href="{{ route('admin.contractors.show', $project->contractor) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-briefcase me-1"></i>View contractor</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="row g-3">
    {{-- Left column --}}
    <div class="col-12 col-lg-8 d-flex flex-column gap-3">

        {{-- Project Details --}}
        <x-card title="Project Details">
            <dl class="detail-list">
                <dt>Client</dt><dd><a href="{{ route('admin.clients.show', $project->client) }}">{{ $project->client->name }}</a></dd>
                @if ($project->client->company) <dt>Company</dt><dd>{{ $project->client->company }}</dd> @endif
                @if ($project->client->contact_person) <dt>Contact</dt><dd>{{ $project->client->contact_person }}</dd> @endif
                @if ($project->client->phone) <dt>Phone</dt><dd><a href="tel:{{ $project->client->phone }}">{{ $project->client->phone }}</a></dd> @endif
                @if ($project->client->email) <dt>Email</dt><dd><a href="mailto:{{ $project->client->email }}">{{ $project->client->email }}</a></dd> @endif
                @if ($project->type_of_work) <dt>Type of work</dt><dd>{{ $project->type_of_work }}</dd> @endif
                @if ($project->start_date) <dt>Start</dt><dd>{{ $project->start_date->format('d M Y') }}</dd> @endif
                @if ($project->expected_end_date) <dt>Expected end</dt><dd>{{ $project->expected_end_date->format('d M Y') }}</dd> @endif
                @if ($project->actual_end_date) <dt>Actual end</dt><dd>{{ $project->actual_end_date->format('d M Y') }}</dd> @endif
                @if ($project->budget !== null) <dt>Budget</dt><dd>{{ Number::currency((float) $project->budget, 'INR', 'en_IN', 0) }}</dd> @endif
                @if ($project->description) <dt>Description</dt><dd style="white-space: pre-line">{{ $project->description }}</dd> @endif
                @if ($project->notes) <dt>Notes</dt><dd style="white-space: pre-line">{{ $project->notes }}</dd> @endif
            </dl>
        </x-card>

        {{-- Works --}}
        <x-table-card :title="'Works ('.$project->works->count().')'">
            @if ($project->works->isEmpty())
                <x-empty-state icon="bi-list-check" title="No works" message="No tasks have been added to this project." class="py-4" />
            @else
                <table class="table align-middle">
                    <thead><tr><th>Work</th><th class="d-none d-md-table-cell">Assigned to</th><th class="d-none d-sm-table-cell">Due</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($project->works as $work)
                            <tr>
                                <td class="fw-semibold">{{ $work->title }}</td>
                                <td class="d-none d-md-table-cell">{{ $work->assignee?->name ?? '—' }}</td>
                                <td class="d-none d-sm-table-cell text-nowrap">{{ $work->due_date?->format('d M Y') ?? '—' }}</td>
                                <td><x-status-badge :status="$work->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-table-card>

        {{-- Meeting History --}}
        <x-card>
            <x-slot:title>
                <i class="bi bi-journal-text me-1 text-secondary"></i>Meeting History
                @if ($project->meetings->isNotEmpty()) <span class="badge badge-soft-secondary ms-1">{{ $project->meetings->count() }}</span> @endif
            </x-slot:title>
            @if ($project->meetings->isEmpty())
                <div class="empty-inline">No meetings logged yet.</div>
            @else
                <ul class="feed">
                    @foreach ($project->meetings as $meeting)
                        <li class="feed-item align-items-start">
                            <div class="deadline-date flex-shrink-0">
                                <span class="day">{{ $meeting->meeting_at->day }}</span>
                                <span class="month">{{ $meeting->meeting_at->format('M Y') }}</span>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold">{{ $meeting->purpose }}</div>
                                <div class="feed-meta">{{ $meeting->meeting_at->format('h:i A') }}@if ($meeting->createdBy) · {{ $meeting->createdBy->name }} @endif</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        @can('activity.view')
            <x-card title="Activity" :flush="true">
                @include('partials.activity-feed', ['activity' => $activity])
            </x-card>
        @endcan
    </div>

    {{-- Right column --}}
    <div class="col-12 col-lg-4 d-flex flex-column gap-3">

        {{-- Next Meeting --}}
        <x-card>
            <x-slot:title><i class="bi bi-calendar-event me-1 text-info"></i>Next Meeting</x-slot:title>
            @if ($project->next_meeting_at)
                @php $days = (int) today()->diffInDays($project->next_meeting_at->toDateString(), false); @endphp
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="fw-semibold">{{ $project->next_meeting_at->format('d M Y · h:i A') }}</span>
                    @if ($days === 0) <span class="badge badge-soft-warning">Today</span>
                    @elseif ($days < 0) <span class="badge badge-soft-danger">Past</span>
                    @else <span class="badge badge-soft-info">In {{ $days }} {{ $days === 1 ? 'day' : 'days' }}</span>
                    @endif
                </div>
                @if ($project->next_meeting_notes) <div class="small text-muted">{{ $project->next_meeting_notes }}</div> @endif
            @else
                <div class="text-muted small">No meeting scheduled.</div>
            @endif
        </x-card>

        {{-- Locations --}}
        @if ($project->locations->isNotEmpty())
            <x-card :title="'Locations ('.$project->locations->count().')'">
                <ul class="list-unstyled mb-0">
                    @foreach ($project->locations as $loc)
                        <li class="{{ !$loop->first ? 'border-top pt-2 mt-2' : '' }}">
                            <div class="fw-semibold">{{ $loc->name }}</div>
                            <div class="small text-muted">{{ $loc->shortAddress() }}</div>
                            @if ($loc->contact_person) <div class="small text-muted">{{ $loc->contact_person }}</div> @endif
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif

    </div>
</div>
@endsection
