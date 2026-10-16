@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<x-page-header :title="'Welcome, '.$user->firstName()" subtitle="Platform-wide overview across all contractors." />

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Total Contractors" :value="number_format($cards['contractors'])" icon="bi-briefcase" accent="primary" :note="($cards['contractors'] - $cards['active_contractors']).' inactive'" :href="$user->can('contractors.view') ? route('admin.contractors.index') : null" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Active Contractors" :value="number_format($cards['active_contractors'])" icon="bi-patch-check" accent="success" :href="$user->can('contractors.view') ? route('admin.contractors.index', ['status' => 'active']) : null" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Total Clients" :value="number_format($cards['clients'])" icon="bi-person-vcard" accent="info" :href="$user->can('clients.view') ? route('admin.clients.index') : null" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Total Projects" :value="number_format($cards['projects'])" icon="bi-building" accent="secondary" :href="$user->can('projects.view') ? route('admin.projects.index') : null" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Active Projects" :value="number_format($cards['active_projects'])" icon="bi-activity" accent="success" :href="$user->can('projects.view') ? route('admin.projects.index', ['status' => 'active']) : null" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Completed Projects" :value="number_format($cards['completed_projects'])" icon="bi-check2-circle" accent="secondary" :href="$user->can('projects.view') ? route('admin.projects.index', ['status' => 'completed']) : null" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Pending Works" :value="number_format($cards['pending_works'])" icon="bi-list-check" accent="warning" :href="$user->can('works.view') ? route('admin.works.index', ['status' => 'open']) : null" /></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-5 col-xl-4">
        <x-card title="Projects by Status" class="h-100">
            @if ($cards['projects'] === 0)
                <div class="empty-inline">No projects yet.</div>
            @else
                <div class="chart-box chart-box-sm"><canvas id="statusChart" role="img" aria-label="Projects by status"></canvas></div>
                <ul class="list-unstyled small mt-3 mb-0 status-legend">
                    @foreach ($statusDistribution as $label => $count)
                        <li class="d-flex justify-content-between"><span><i class="legend-dot" data-index="{{ $loop->index }}"></i>{{ $label }}</span><strong>{{ $count }}</strong></li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
    <div class="col-12 col-lg-7 col-xl-4">
        <x-card title="Projects by Contractor" class="h-100">
            @if ($projectsByContractor === [])
                <div class="empty-inline">No projects yet.</div>
            @else
                <div class="chart-box"><canvas id="contractorChart" role="img" aria-label="Projects by contractor"></canvas></div>
            @endif
        </x-card>
    </div>
    <div class="col-12 col-xl-4">
        <x-card title="Contractor Growth" class="h-100">
            <p class="small text-muted mb-2">Total contractors, last 12 months.</p>
            <div class="chart-box"><canvas id="growthChart" role="img" aria-label="Contractor growth"></canvas></div>
        </x-card>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-7">
        <x-table-card title="Newest Contractors">
            <x-slot:headerActions>
                @can('contractors.view') <a href="{{ route('admin.contractors.index') }}" class="card-link">View all</a> @endcan
            </x-slot:headerActions>
            @if ($recentContractors->isEmpty())
                <x-empty-state icon="bi-briefcase" title="No contractors yet" message="Add your first contractor to get started." />
            @else
                <table class="table table-hover align-middle">
                    <thead><tr><th>Contractor</th><th class="d-none d-md-table-cell">Contact</th><th class="text-center">Projects</th><th>Status</th><th class="d-none d-sm-table-cell">Joined</th></tr></thead>
                    <tbody>
                        @foreach ($recentContractors as $contractor)
                            <tr>
                                <td>@can('contractors.view')<a href="{{ route('admin.contractors.show', $contractor) }}" class="row-title">{{ $contractor->company_name }}</a>@else{{ $contractor->company_name }}@endcan</td>
                                <td class="d-none d-md-table-cell">{{ $contractor->contact_person }}</td>
                                <td class="text-center">{{ $contractor->projects_count }}</td>
                                <td><x-status-badge :status="$contractor->status" /></td>
                                <td class="d-none d-sm-table-cell text-nowrap">{{ $contractor->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-table-card>
    </div>
    <div class="col-12 col-xl-5">
        @can('activity.view')
            <x-card title="Recent Activity" :flush="true" class="h-100">
                <x-slot:actions><a href="{{ route('admin.activity.index') }}" class="card-link">View all</a></x-slot:actions>
                @include('partials.activity-feed', ['activity' => $activity, 'showContractor' => true])
            </x-card>
        @endcan
    </div>
</div>

<script id="dashboardData" type="application/json">@json(['status' => $statusDistribution, 'byContractor' => $projectsByContractor, 'growth' => $growth])</script>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/dashboard.js') }}?v={{ filemtime(public_path('js/dashboard.js')) }}"></script>
@endpush
