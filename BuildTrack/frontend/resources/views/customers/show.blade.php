@extends('layouts.app')

@section('title', 'Customer: ' . ($customer['customerName'] ?? ''))

@section('content')
@php
    $projectItems = is_array($projects) && isset($projects[0]) ? $projects : ($projects['items'] ?? $projects['data'] ?? []);
@endphp

@if($error)
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>{{ $error }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
                <span><i class="bi bi-person me-2"></i>Customer Details</span>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Name</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $customer['customerName'] ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Phone</dt>
                    <dd class="col-sm-8">{{ $customer['phoneNumber'] ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Location</dt>
                    <dd class="col-sm-8">{{ $customer['locationName'] ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Address</dt>
                    <dd class="col-sm-8">{{ $customer['address'] ?? '—' }}</dd>

                    @if(!empty($customer['notes']))
                    <dt class="col-sm-4 text-muted">Notes</dt>
                    <dd class="col-sm-8">{{ $customer['notes'] }}</dd>
                    @endif
                </dl>
            </div>
            <div class="card-footer d-flex gap-2">
                <a href="{{ route('customers.edit', $customer['id'] ?? 0) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
                <form action="{{ route('customers.destroy', $customer['id'] ?? 0) }}" method="POST" class="d-inline delete-form" data-confirm-message="Are you sure you want to delete this customer?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-kanban me-2"></i>Projects</span>
                <a href="{{ route('projects.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>New Project
                </a>
            </div>
            <div class="card-body p-0">
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
                                    <th>Budget</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($projectItems as $project)
                                <tr>
                                    <td class="fw-semibold">
                                        <a href="{{ route('projects.show', $project['id']) }}">{{ $project['projectName'] ?? '—' }}</a>
                                    </td>
                                    <td>{{ $project['locationName'] ?? '—' }}</td>
                                    <td>{{ isset($project['startDate']) ? date('d M Y', strtotime($project['startDate'])) : '—' }}</td>
                                    <td class="text-end" style="font-variant-numeric:tabular-nums;">{{ isset($project['budget']) ? number_format((float)$project['budget'], 2) : '—' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('projects.show', $project['id']) }}" class="btn-action" title="View">
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
    </div>
</div>
@endsection
