@extends('layouts.app')

@section('title', $contractor->company_name)

@section('content')
<x-page-header :title="$contractor->company_name">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('admin.contractors.index') }}">Contractors</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $contractor->company_name }}</li>
    </x-slot:breadcrumbs>
    <x-slot:actions>
        @can('update', $contractor)
            <a href="{{ route('admin.contractors.edit', $contractor) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
        @endcan
    </x-slot:actions>
</x-page-header>

{{-- Statistics --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><x-stat-card label="Total Clients" :value="$summary['clients']" icon="bi-person-vcard" accent="primary" /></div>
    <div class="col-6 col-lg-3"><x-stat-card label="Total Projects" :value="$summary['projects']" icon="bi-building" accent="info" :note="$summary['completed_projects'].' completed'" /></div>
    <div class="col-6 col-lg-3"><x-stat-card label="Active Projects" :value="$summary['active_projects']" icon="bi-activity" accent="success" /></div>
    <div class="col-6 col-lg-3"><x-stat-card label="Pending Works" :value="$summary['pending_works']" icon="bi-list-check" accent="warning" :note="$summary['locations'].' project locations'" /></div>
</div>

{{-- Collapsible cards --}}
<div class="d-flex flex-column gap-3">

    {{-- Contractor Information --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between" role="button" data-bs-toggle="collapse" data-bs-target="#collapseInfo" aria-expanded="true" aria-controls="collapseInfo" style="cursor:pointer">
            <h2 class="card-title mb-0">Contractor Information</h2>
            <i class="bi bi-chevron-up text-muted collapse-chevron"></i>
        </div>
        <div class="collapse show" id="collapseInfo">
            <div class="card-body">
                <dl class="detail-list">
                    @if ($contractor->contact_person)
                        <dt>Contact person</dt><dd>{{ $contractor->contact_person }}</dd>
                    @endif
                    @if ($contractor->email)
                        <dt>Email</dt><dd><a href="mailto:{{ $contractor->email }}">{{ $contractor->email }}</a></dd>
                    @endif
                    @if ($contractor->phone)
                        <dt>Phone</dt><dd>{{ $contractor->phone }}{{ $contractor->alternate_phone ? ' / '.$contractor->alternate_phone : '' }}</dd>
                    @endif
                    @php $address = collect([$contractor->address, $contractor->city, $contractor->state, $contractor->postal_code, $contractor->country])->filter()->implode(', '); @endphp
                    @if ($address)
                        <dt>Address</dt><dd>{{ $address }}</dd>
                    @endif
                    @if ($contractor->website)
                        <dt>Website</dt><dd><a href="{{ $contractor->website }}" target="_blank" rel="noopener noreferrer">{{ $contractor->website }}</a></dd>
                    @endif
                    @if ($contractor->tax_number)
                        <dt>Tax / GST</dt><dd>{{ $contractor->tax_number }}</dd>
                    @endif
                    @if ($contractor->license_number)
                        <dt>License</dt><dd>{{ $contractor->license_number }}</dd>
                    @endif
                    <dt>Status</dt><dd><x-status-badge :status="$contractor->status" /></dd>
                    <dt>Created</dt><dd>{{ $contractor->created_at->format('d M Y') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Users --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between" role="button" data-bs-toggle="collapse" data-bs-target="#collapseUsers" aria-expanded="true" aria-controls="collapseUsers" style="cursor:pointer">
            <h2 class="card-title mb-0">Users</h2>
            <div class="d-flex align-items-center gap-3">
                @can('create', App\Models\User::class)
                    <a href="{{ route('admin.users.create', ['contractor_id' => $contractor->id]) }}" class="card-link" onclick="event.stopPropagation()"><i class="bi bi-plus-lg"></i> Add user</a>
                @endcan
                <i class="bi bi-chevron-down text-muted collapse-chevron"></i>
            </div>
        </div>
        <div class="collapse" id="collapseUsers">
            @if ($users->isEmpty())
                <div class="card-body"><p class="text-muted mb-0">No users yet. Add one so the contractor can sign in.</p></div>
            @else
                <ul class="list-group list-group-flush">
                    @foreach ($users as $member)
                        <li class="list-group-item d-flex align-items-center gap-2">
                            <span class="avatar-sm">{{ $member->initials() }}</span>
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold text-truncate">{{ $member->name }}</div>
                                <div class="cell-sub text-truncate">{{ $member->email }} · {{ $member->role?->name }}</div>
                            </div>
                            <x-status-badge :status="$member->status" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Clients --}}
    @can('clients.view')
    <div class="card table-card">
        <div class="card-header d-flex align-items-center justify-content-between" role="button" data-bs-toggle="collapse" data-bs-target="#collapseClients" aria-expanded="false" aria-controls="collapseClients" style="cursor:pointer">
            <h2 class="card-title mb-0">Clients <span class="badge badge-soft-secondary ms-1">{{ $clients->total() }}</span></h2>
            <i class="bi bi-chevron-down text-muted collapse-chevron"></i>
        </div>
        <div class="collapse" id="collapseClients">
            @if ($clients->isEmpty())
                <div class="card-body"><p class="text-muted mb-0">No clients yet.</p></div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Client</th><th class="d-none d-md-table-cell">Contact</th><th class="text-center">Projects</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($clients as $client)
                                <tr>
                                    <td><a href="{{ route('admin.clients.show', $client) }}" class="row-title">{{ $client->name }}</a></td>
                                    <td class="d-none d-md-table-cell">{{ $client->contact_person ?? '—' }}</td>
                                    <td class="text-center">{{ $client->projects_count }}</td>
                                    <td><x-status-badge :status="$client->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($clients->hasPages())
                    <div class="table-card-footer">
                        <span class="text-muted small">Showing {{ $clients->firstItem() }}–{{ $clients->lastItem() }} of {{ $clients->total() }}</span>
                        {{ $clients->onEachSide(1)->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
    @endcan

    {{-- Projects --}}
    @can('projects.view')
    <div class="card table-card">
        <div class="card-header d-flex align-items-center justify-content-between" role="button" data-bs-toggle="collapse" data-bs-target="#collapseProjects" aria-expanded="false" aria-controls="collapseProjects" style="cursor:pointer">
            <h2 class="card-title mb-0">Projects <span class="badge badge-soft-secondary ms-1">{{ $projects->total() }}</span></h2>
            <i class="bi bi-chevron-down text-muted collapse-chevron"></i>
        </div>
        <div class="collapse" id="collapseProjects">
            @if ($projects->isEmpty())
                <div class="card-body"><p class="text-muted mb-0">No projects yet.</p></div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Project</th><th class="d-none d-md-table-cell">Client</th><th class="d-none d-sm-table-cell">Start</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($projects as $project)
                                <tr>
                                    <td><a href="{{ route('admin.projects.show', $project) }}" class="row-title">{{ $project->name }}</a><div class="cell-sub">{{ $project->project_number }}</div></td>
                                    <td class="d-none d-md-table-cell">{{ $project->client?->name }}</td>
                                    <td class="d-none d-sm-table-cell text-nowrap">{{ $project->start_date?->format('d M Y') ?? '—' }}</td>
                                    <td><x-status-badge :status="$project->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($projects->hasPages())
                    <div class="table-card-footer">
                        <span class="text-muted small">Showing {{ $projects->firstItem() }}–{{ $projects->lastItem() }} of {{ $projects->total() }}</span>
                        {{ $projects->onEachSide(1)->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
    @endcan

    {{-- Locations --}}
    @can('locations.view')
    <div class="card table-card">
        <div class="card-header d-flex align-items-center justify-content-between" role="button" data-bs-toggle="collapse" data-bs-target="#collapseLocations" aria-expanded="false" aria-controls="collapseLocations" style="cursor:pointer">
            <h2 class="card-title mb-0">Locations <span class="badge badge-soft-secondary ms-1">{{ $locations->total() }}</span></h2>
            <i class="bi bi-chevron-down text-muted collapse-chevron"></i>
        </div>
        <div class="collapse" id="collapseLocations">
            @if ($locations->isEmpty())
                <div class="card-body"><p class="text-muted mb-0">No locations yet.</p></div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Location</th><th>Project</th><th class="d-none d-md-table-cell">Area</th></tr></thead>
                        <tbody>
                            @foreach ($locations as $location)
                                <tr>
                                    <td class="fw-semibold">{{ $location->name }}</td>
                                    <td>{{ $location->project?->name }}</td>
                                    <td class="d-none d-md-table-cell">{{ $location->shortAddress() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($locations->hasPages())
                    <div class="table-card-footer">
                        <span class="text-muted small">Showing {{ $locations->firstItem() }}–{{ $locations->lastItem() }} of {{ $locations->total() }}</span>
                        {{ $locations->onEachSide(1)->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
    @endcan

    {{-- Open Works --}}
    @can('works.view')
    <div class="card table-card">
        <div class="card-header d-flex align-items-center justify-content-between" role="button" data-bs-toggle="collapse" data-bs-target="#collapseWorks" aria-expanded="false" aria-controls="collapseWorks" style="cursor:pointer">
            <h2 class="card-title mb-0">Open Works <span class="badge badge-soft-secondary ms-1">{{ $works->total() }}</span></h2>
            <i class="bi bi-chevron-down text-muted collapse-chevron"></i>
        </div>
        <div class="collapse" id="collapseWorks">
            @if ($works->isEmpty())
                <div class="card-body"><p class="text-muted mb-0">No open works.</p></div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Work</th><th class="d-none d-md-table-cell">Project</th><th class="d-none d-sm-table-cell">Due</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($works as $work)
                                <tr>
                                    <td class="fw-semibold">{{ $work->title }}</td>
                                    <td class="d-none d-md-table-cell">{{ $work->project?->name }}</td>
                                    <td class="d-none d-sm-table-cell text-nowrap">{{ $work->due_date?->format('d M Y') ?? '—' }} @if ($work->isOverdue()) <span class="badge badge-soft-danger">Overdue</span> @endif</td>
                                    <td><x-status-badge :status="$work->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($works->hasPages())
                    <div class="table-card-footer">
                        <span class="text-muted small">Showing {{ $works->firstItem() }}–{{ $works->lastItem() }} of {{ $works->total() }}</span>
                        {{ $works->onEachSide(1)->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
    @endcan

    {{-- Recent Activity (always last) --}}
    @can('activity.view')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between" role="button" data-bs-toggle="collapse" data-bs-target="#collapseActivity" aria-expanded="false" aria-controls="collapseActivity" style="cursor:pointer">
            <h2 class="card-title mb-0">Recent Activity</h2>
            <i class="bi bi-chevron-down text-muted collapse-chevron"></i>
        </div>
        <div class="collapse" id="collapseActivity">
            @include('partials.activity-feed', ['activity' => $activity])
        </div>
    </div>
    @endcan

</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function (trigger) {
    var target = document.querySelector(trigger.dataset.bsTarget);
    var chevron = trigger.querySelector('.collapse-chevron');
    if (!target || !chevron) return;
    target.addEventListener('hide.bs.collapse', function () { chevron.classList.replace('bi-chevron-up', 'bi-chevron-down'); });
    target.addEventListener('show.bs.collapse', function () { chevron.classList.replace('bi-chevron-down', 'bi-chevron-up'); });
});
</script>
@endpush
