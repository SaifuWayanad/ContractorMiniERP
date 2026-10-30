@extends('layouts.app')

@section('title', 'Customer: ' . ($customer['customerName'] ?? ''))

@section('content')
@php
    $projectItems = is_array($projects) && isset($projects[0]) ? $projects : ($projects['items'] ?? $projects['data'] ?? []);
    $customerId = $customer['id'] ?? 0;
@endphp

@if($error)
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>{{ $error }}</div>
@endif

<!-- Header -->
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div class="d-flex gap-2 align-items-center">
        <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h6 class="mb-0 fw-semibold" style="color:#1a3a5c;">{{ $customer['customerName'] ?? '—' }}</h6>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('customers.edit', $customerId) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <form action="{{ route('customers.destroy', $customerId) }}" method="POST" class="d-inline delete-form" data-confirm-message="Are you sure you want to delete this customer?">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button>
        </form>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-0" id="customerTabs">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#overview">Overview</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#projects">
            Projects <span class="badge bg-secondary">{{ count($projectItems) }}</span>
        </a>
    </li>
</ul>

<div class="tab-content card border-top-0 rounded-0 rounded-bottom">

    <!-- Overview Tab -->
    <div class="tab-pane fade show active p-3" id="overview">

        @php
            $totalBudget = 0;
            foreach ($projectItems as $p) {
                $totalBudget += (float)($p['budget'] ?? 0);
            }
            $projectCount = count($projectItems);
        @endphp

        @if($projectCount > 0)
        <div class="row g-3 mb-4">
            <div class="col-12"><p class="text-muted small fw-semibold text-uppercase mb-0" style="letter-spacing:.05em;">Summary</p></div>
            <div class="col-6 col-md-4">
                <div class="card kpi-card h-100" style="border-left-color:#3b82f6;background:#eff6ff;">
                    <div class="card-body d-flex align-items-center gap-3 py-3 px-3">
                        <div class="kpi-icon" style="background:#dbeafe;color:#2563eb;"><i class="bi bi-kanban"></i></div>
                        <div>
                            <div class="kpi-label">Total Projects</div>
                            <div class="kpi-value">{{ $projectCount }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card kpi-card h-100" style="border-left-color:#16a34a;background:#f0fdf4;">
                    <div class="card-body d-flex align-items-center gap-3 py-3 px-3">
                        <div class="kpi-icon" style="background:#dcfce7;color:#15803d;"><i class="bi bi-wallet2"></i></div>
                        <div>
                            <div class="kpi-label">Total Budget</div>
                            <div class="kpi-value">{{ number_format($totalBudget, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card kpi-card h-100" style="border-left-color:#0891b2;background:#ecfeff;">
                    <div class="card-body d-flex align-items-center gap-3 py-3 px-3">
                        <div class="kpi-icon" style="background:#cffafe;color:#0e7490;"><i class="bi bi-geo-alt"></i></div>
                        <div>
                            <div class="kpi-label">Location</div>
                            <div class="kpi-value" style="font-size:.95rem;">{{ $customer['locationName'] ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr class="my-3">
        @endif

        <h6 class="text-muted fw-semibold mb-3">Customer Details</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Name</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $customer['customerName'] ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Phone</dt>
                    <dd class="col-sm-8">
                        @if(!empty($customer['phoneNumber']))
                            <a href="tel:{{ $customer['phoneNumber'] }}" class="text-body text-decoration-none">{{ $customer['phoneNumber'] }}</a>
                        @else
                            —
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Location</dt>
                    <dd class="col-sm-8">{{ $customer['locationName'] ?? '—' }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Address</dt>
                    <dd class="col-sm-8">{{ $customer['address'] ?? '—' }}</dd>

                    @if(!empty($customer['notes']))
                    <dt class="col-sm-4 text-muted">Notes</dt>
                    <dd class="col-sm-8">{{ $customer['notes'] }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <!-- Projects Tab -->
    <div class="tab-pane fade" id="projects">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Projects</h6>
            <a href="{{ route('projects.create', ['customerId' => $customerId]) }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i>New Project
            </a>
        </div>
        @if(empty($projectItems))
            <div class="empty-state">
                <i class="bi bi-kanban d-block"></i>
                <p>No projects linked to this customer.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Start Date</th>
                            <th class="text-end">Budget</th>
                            <th class="text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($projectItems as $project)
                        <tr>
                            <td class="fw-semibold">
                                <a href="{{ route('projects.show', $project['id']) }}" class="text-decoration-none" style="color:#1a3a5c;">{{ $project['projectName'] ?? '—' }}</a>
                            </td>
                            <td class="text-secondary">{{ $project['locationName'] ?? '—' }}</td>
                            <td class="text-secondary">{{ isset($project['startDate']) ? date('d M Y', strtotime($project['startDate'])) : '—' }}</td>
                            <td class="text-end" style="font-variant-numeric:tabular-nums;">{{ isset($project['budget']) ? number_format((float)$project['budget'], 2) : '—' }}</td>
                            <td class="text-center">
                                <a href="{{ route('projects.show', $project['id']) }}" class="btn btn-sm btn-icon-ghost" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

@endsection

@section('scripts')
<style>
.btn-icon-ghost {
    padding: 0.25rem 0.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: transparent;
    color: #64748b;
    font-size: 0.85rem;
    transition: background 0.15s, border-color 0.15s, color 0.15s;
}
.btn-icon-ghost:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #1a3a5c;
}
</style>
@endsection
