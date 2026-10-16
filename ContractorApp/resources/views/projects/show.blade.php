@extends('layouts.app')

@section('title', $project->name)

@use('Illuminate\Support\Number')

@section('content')
<x-page-header :title="$project->name">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $project->project_number }}</li>
    </x-slot:breadcrumbs>
    <x-slot:actions>
        @can('delete', $project)
            <x-delete-button :action="route('projects.destroy', $project)" title="Delete this project?" :confirm="'Delete &quot;'.$project->name.'&quot;? This cannot be undone.'" />
        @endcan
        @can('update', $project)
            <a href="{{ route('projects.edit', $project) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
        @endcan
    </x-slot:actions>
</x-page-header>

{{-- Meta row --}}
<div class="d-flex flex-wrap align-items-center gap-2 mb-3 small">
    <x-status-badge :status="$project->status" />
    <span class="text-muted">{{ $project->project_number }}</span>
    @if ($project->type_of_work) <span class="text-muted">· {{ $project->type_of_work }}</span> @endif
    <span class="text-muted">· <a href="{{ route('clients.show', $project->client) }}">{{ $project->client->name }}</a></span>
    @if ($project->isOverdue()) <span class="badge badge-soft-danger">Overdue</span> @endif
</div>

<div class="row g-3">
    {{-- Left column --}}
    <div class="col-12 col-lg-8 d-flex flex-column gap-3">

        {{-- Next Meeting --}}
        <x-card id="nextMeetingCard">
            <x-slot:title><i class="bi bi-calendar-event me-1 text-info"></i>Next Meeting / Site Visit</x-slot:title>
            @can('update', $project)
                <x-slot:actions>
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="toggleMeetingForm" aria-expanded="false">
                        <i class="bi bi-pencil"></i> {{ $project->next_meeting_at ? 'Update' : 'Schedule' }}
                    </button>
                </x-slot:actions>
            @endcan

            @if ($project->next_meeting_at)
                @php
                    $meetingDays = (int) today()->diffInDays($project->next_meeting_at->toDateString(), false);
                    $isToday = $meetingDays === 0;
                    $isPast  = $meetingDays < 0;
                @endphp
                <div id="meetingDisplay">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="fw-semibold fs-6">{{ $project->next_meeting_at->format('d M Y · h:i A') }}</span>
                        @if ($isToday)
                            <span class="badge badge-soft-warning">Today</span>
                        @elseif ($isPast)
                            <span class="badge badge-soft-danger">Past</span>
                        @else
                            <span class="badge badge-soft-info">In {{ $meetingDays }} {{ $meetingDays === 1 ? 'day' : 'days' }}</span>
                        @endif
                    </div>
                    @if ($project->next_meeting_notes)
                        <div class="small text-muted">{{ $project->next_meeting_notes }}</div>
                    @endif
                </div>
            @else
                <div id="meetingDisplay" class="text-muted small">No meeting scheduled yet.</div>
            @endif

            @can('update', $project)
                <form method="post" action="{{ route('projects.meeting', $project) }}" id="meetingForm" class="collapse mt-3">
                    @csrf @method('PATCH')
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <label for="next_meeting_at" class="form-label form-label-sm">Date &amp; Time</label>
                            <input type="datetime-local" class="form-control form-control-sm" name="next_meeting_at" id="next_meeting_at"
                                value="{{ $project->next_meeting_at?->format('Y-m-d\TH:i') }}" />
                        </div>
                        <div class="col-sm-6">
                            <label for="next_meeting_notes" class="form-label form-label-sm">Notes</label>
                            <input type="text" class="form-control form-control-sm" name="next_meeting_notes" id="next_meeting_notes"
                                value="{{ $project->next_meeting_notes }}" maxlength="250" placeholder="e.g. Site inspection" />
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                        <button type="button" class="btn btn-secondary btn-sm" id="cancelMeetingForm">Cancel</button>
                        @if ($project->next_meeting_at)
                            <button type="submit" name="next_meeting_at" value="" class="btn btn-link btn-sm text-danger p-0 ms-auto">Clear</button>
                        @endif
                    </div>
                </form>
            @endcan
        </x-card>

        {{-- Project Details --}}
        <x-card title="Project Details">
            <dl class="detail-list">
                <dt>Client</dt><dd><a href="{{ route('clients.show', $project->client) }}">{{ $project->client->name }}</a></dd>
                @if ($project->client->company) <dt>Company</dt><dd>{{ $project->client->company }}</dd> @endif
                @if ($project->client->contact_person) <dt>Contact</dt><dd>{{ $project->client->contact_person }}</dd> @endif
                @if ($project->client->phone) <dt>Phone</dt><dd><a href="tel:{{ $project->client->phone }}">{{ $project->client->phone }}</a></dd> @endif
                @if ($project->client->email) <dt>Email</dt><dd><a href="mailto:{{ $project->client->email }}">{{ $project->client->email }}</a></dd> @endif
                @if ($project->start_date) <dt>Start</dt><dd>{{ $project->start_date->format('d M Y') }}</dd> @endif
                @if ($project->expected_end_date) <dt>Expected end</dt><dd>{{ $project->expected_end_date->format('d M Y') }}</dd> @endif
                @if ($project->budget !== null) <dt>Budget</dt><dd>{{ Number::currency((float) $project->budget, 'INR', 'en_IN', 0) }}</dd> @endif
                @if ($project->description)
                    <dt>Description</dt><dd style="white-space: pre-line">{{ $project->description }}</dd>
                @endif
                @if ($project->notes)
                    <dt>Notes</dt><dd style="white-space: pre-line">{{ $project->notes }}</dd>
                @endif
            </dl>
        </x-card>

        {{-- Works --}}
        <x-table-card :title="'Works ('.$works->count().')'" id="works">
            <x-slot:headerActions>
                @can('create', App\Models\Work::class)
                    <a href="{{ route('works.create', ['project_id' => $project->id]) }}" class="card-link"><i class="bi bi-plus-lg"></i> Add work</a>
                @endcan
            </x-slot:headerActions>
            @if ($works->isEmpty())
                <x-empty-state icon="bi-list-check" title="No works yet" message="Break the project into tasks to track what's pending." class="py-4" />
            @else
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Work</th>
                            <th class="d-none d-md-table-cell">Assigned to</th>
                            <th class="d-none d-sm-table-cell">Due</th>
                            <th>Priority</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($works as $work)
                            <tr>
                                <td><a href="{{ route('works.show', $work) }}" class="row-title">{{ $work->title }}</a></td>
                                <td class="d-none d-md-table-cell">{{ $work->assignee?->name ?? '—' }}</td>
                                <td class="d-none d-sm-table-cell text-nowrap">
                                    {{ $work->due_date?->format('d M Y') ?? '—' }}
                                    @if ($work->isOverdue()) <span class="badge badge-soft-danger">Overdue</span> @endif
                                </td>
                                <td><x-status-badge :status="$work->priority" /></td>
                                <td><x-status-badge :status="$work->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-table-card>

        {{-- Meeting History --}}
        <x-card id="meetingHistory">
            <x-slot:title>
                <i class="bi bi-journal-text me-1 text-secondary"></i>Meeting History
                @if ($meetings->isNotEmpty()) <span class="badge badge-soft-secondary ms-1">{{ $meetings->count() }}</span> @endif
            </x-slot:title>
            @can('update', $project)
                <x-slot:actions>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="toggleLogMeeting">
                        <i class="bi bi-plus-lg me-1"></i>Log Meeting
                    </button>
                </x-slot:actions>
            @endcan

            @can('update', $project)
                <div id="logMeetingForm" class="collapse border rounded p-3 mb-3 bg-light">
                    <form method="post" action="{{ route('projects.meetings.store', $project) }}">
                        @csrf
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <label for="log_meeting_at" class="form-label form-label-sm fw-semibold">Date &amp; Time <span class="req">*</span></label>
                                <input type="datetime-local" class="form-control form-control-sm @error('meeting_at') is-invalid @enderror"
                                    name="meeting_at" id="log_meeting_at"
                                    value="{{ old('meeting_at', now()->format('Y-m-d\TH:i')) }}" required />
                                @error('meeting_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label for="log_purpose" class="form-label form-label-sm fw-semibold">Purpose <span class="req">*</span></label>
                                <input type="text" class="form-control form-control-sm @error('purpose') is-invalid @enderror"
                                    name="purpose" id="log_purpose"
                                    value="{{ old('purpose') }}" maxlength="500" placeholder="e.g. Site inspection…" required autocomplete="off" />
                                @error('purpose')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button type="submit" class="btn btn-primary btn-sm">Save</button>
                            <button type="button" class="btn btn-secondary btn-sm" id="cancelLogMeeting">Cancel</button>
                        </div>
                    </form>
                </div>
            @endcan

            @if ($meetings->isEmpty())
                <div class="empty-inline"><i class="bi bi-journal-text d-block fs-4 mb-1"></i>No meetings logged yet.</div>
            @else
                <ul class="feed">
                    @foreach ($meetings as $meeting)
                        <li class="feed-item align-items-start">
                            <div class="deadline-date flex-shrink-0">
                                <span class="day">{{ $meeting->meeting_at->day }}</span>
                                <span class="month">{{ $meeting->meeting_at->format('M Y') }}</span>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold">{{ $meeting->purpose }}</div>
                                <div class="feed-meta">
                                    {{ $meeting->meeting_at->format('h:i A') }}
                                    @if ($meeting->createdBy) · {{ $meeting->createdBy->name }} @endif
                                    · {{ $meeting->created_at->diffForHumans() }}
                                </div>
                            </div>
                            @can('update', $project)
                                <form method="post" action="{{ route('projects.meetings.destroy', [$project, $meeting]) }}" class="flex-shrink-0 ms-2">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-icon btn-sm text-danger" aria-label="Delete" onclick="return confirm('Remove this meeting entry?')">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>

    {{-- Right column --}}
    <div class="col-12 col-lg-4 d-flex flex-column gap-3">

        {{-- Locations --}}
        <x-card :title="'Locations ('.$project->locations->count().')'">
            <x-slot:actions>
                @can('create', App\Models\ProjectLocation::class)
                    <a href="{{ route('projects.locations.create', $project) }}" class="card-link"><i class="bi bi-plus-lg"></i> Add</a>
                @endcan
            </x-slot:actions>
            @if ($project->locations->isEmpty())
                <div class="empty-inline">No locations added yet.</div>
            @else
                <ul class="list-unstyled mb-0">
                    @foreach ($project->locations as $loc)
                        <li class="{{ !$loop->first ? 'border-top pt-2 mt-2' : '' }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <a href="{{ route('locations.show', $loc) }}" class="fw-semibold text-decoration-none">{{ $loc->name }}</a>
                                    <div class="small text-muted">{{ $loc->shortAddress() }}</div>
                                    @if ($loc->contact_person)
                                        <div class="small text-muted">{{ $loc->contact_person }}@if ($loc->contact_phone) · <a href="tel:{{ $loc->contact_phone }}">{{ $loc->contact_phone }}</a> @endif</div>
                                    @endif
                                </div>
                                @can('update', $loc)
                                    <a href="{{ route('locations.edit', $loc) }}" class="btn btn-icon btn-sm ms-2" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                                @endcan
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        {{-- Contacts (Dealers) --}}
        <x-card :title="'Contacts ('.$project->dealers->count().')'">
            @can('update', $project)
                <x-slot:actions>
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="toggleAddContact">
                        <i class="bi bi-plus-lg"></i> Add
                    </button>
                </x-slot:actions>
            @endcan

            @can('update', $project)
                <div id="addContactForm" class="collapse mb-3">
                    <form method="post" action="{{ route('projects.contacts.add', $project) }}" class="d-flex gap-2">
                        @csrf
                        <select name="dealer_id" class="form-select form-select-sm">
                            <option value="">Select contact…</option>
                            @foreach ($dealerOptions as $d)
                                @unless ($project->dealers->contains($d->id))
                                    <option value="{{ $d->id }}">{{ $d->name }}{{ $d->city ? ' — '.$d->city : '' }}</option>
                                @endunless
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm text-nowrap">Add</button>
                    </form>
                </div>
            @endcan

            @if ($project->dealers->isEmpty())
                <div class="empty-inline">No contacts assigned.</div>
            @else
                <ul class="list-unstyled mb-0">
                    @foreach ($project->dealers as $dealer)
                        <li class="{{ !$loop->first ? 'border-top pt-2 mt-2' : '' }} d-flex align-items-center gap-2">
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $dealer->name }}</div>
                                @if ($dealer->city) <div class="small text-muted">{{ $dealer->city }}</div> @endif
                                @if ($dealer->phone) <div class="small"><a href="tel:{{ $dealer->phone }}">{{ $dealer->phone }}</a></div> @endif
                            </div>
                            @can('update', $project)
                                <form method="post" action="{{ route('projects.contacts.remove', [$project, $dealer]) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-icon btn-sm text-danger" onclick="return confirm('Remove contact?')">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        {{-- Activity --}}
        @can('activity.view')
            <x-card title="Activity" :flush="true">
                @include('partials.activity-feed', ['activity' => $activity])
            </x-card>
        @endcan
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    /* Next meeting form toggle */
    var toggleMeeting = document.getElementById('toggleMeetingForm');
    if (toggleMeeting) {
        var meetingForm = document.getElementById('meetingForm');
        var meetingDisplay = document.getElementById('meetingDisplay');
        var cancelMeeting = document.getElementById('cancelMeetingForm');
        toggleMeeting.addEventListener('click', function () {
            var open = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', String(!open));
            meetingForm.classList.toggle('collapse', open);
            if (meetingDisplay) meetingDisplay.classList.toggle('d-none', !open);
        });
        if (cancelMeeting) cancelMeeting.addEventListener('click', function () {
            toggleMeeting.setAttribute('aria-expanded', 'false');
            meetingForm.classList.add('collapse');
            if (meetingDisplay) meetingDisplay.classList.remove('d-none');
        });
    }

    /* Log meeting toggle */
    var toggleLog = document.getElementById('toggleLogMeeting');
    if (toggleLog) {
        var logForm = document.getElementById('logMeetingForm');
        var cancelLog = document.getElementById('cancelLogMeeting');
        @if ($errors->has('meeting_at') || $errors->has('purpose'))
        logForm.classList.remove('collapse');
        @endif
        toggleLog.addEventListener('click', function () { logForm.classList.toggle('collapse'); });
        if (cancelLog) cancelLog.addEventListener('click', function () { logForm.classList.add('collapse'); });
    }

    /* Add contact toggle */
    var toggleContact = document.getElementById('toggleAddContact');
    if (toggleContact) {
        var contactForm = document.getElementById('addContactForm');
        toggleContact.addEventListener('click', function () { contactForm.classList.toggle('collapse'); });
    }
})();
</script>
@endpush
