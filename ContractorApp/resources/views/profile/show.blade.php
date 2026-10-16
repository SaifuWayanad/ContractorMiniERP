@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<x-page-header title="Company profile" subtitle="Your contractor account details.">
    <x-slot:actions>
        @can('updateProfile', $contractor)
            <a href="{{ route('profile.edit') }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit Profile</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="card mb-3">
    <div class="card-body entity-header">
        <span class="entity-mark">
            @if ($contractor->logoUrl())
                <img src="{{ $contractor->logoUrl() }}" alt="{{ $contractor->company_name }} logo" />
            @else
                {{ $contractor->initials() }}
            @endif
        </span>
        <div class="min-w-0">
            <h2 class="h5 mb-1">{{ $contractor->company_name }}</h2>
            <div class="small text-muted">
                <x-status-badge :status="$contractor->status" />
                · Member since {{ $contractor->created_at->format('d M Y') }}
                · {{ $contractor->users_count }} {{ Str::plural('user', $contractor->users_count) }}
                · {{ $contractor->clients_count }} {{ Str::plural('client', $contractor->clients_count) }}
                · {{ $contractor->projects_count }} {{ Str::plural('project', $contractor->projects_count) }}
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <x-card title="Company Information" class="h-100">
            <dl class="detail-list">
                <dt>Company name</dt><dd>{{ $contractor->company_name }}</dd>
                <dt>Website</dt><dd>@if ($contractor->website)<a href="{{ $contractor->website }}" target="_blank" rel="noopener noreferrer">{{ $contractor->website }}</a>@else — @endif</dd>
                <dt>Status</dt><dd><x-status-badge :status="$contractor->status" /></dd>
                <dt>Created</dt><dd>{{ $contractor->created_at->format('d M Y') }}</dd>
            </dl>
        </x-card>
    </div>
    <div class="col-12 col-lg-6">
        <x-card title="Contact Information" class="h-100">
            <dl class="detail-list">
                <dt>Contact person</dt><dd>{{ $contractor->contact_person }}</dd>
                <dt>Email</dt><dd><a href="mailto:{{ $contractor->email }}">{{ $contractor->email }}</a></dd>
                <dt>Phone</dt><dd>{{ $contractor->phone }}</dd>
                <dt>Alternate phone</dt><dd>{{ $contractor->alternate_phone ?? '—' }}</dd>
            </dl>
        </x-card>
    </div>
    <div class="col-12 col-lg-6">
        <x-card title="Address Information" class="h-100">
            <dl class="detail-list">
                <dt>Address</dt><dd>{{ $contractor->address ?? '—' }}</dd>
                <dt>City</dt><dd>{{ $contractor->city ?? '—' }}</dd>
                <dt>State</dt><dd>{{ $contractor->state ?? '—' }}</dd>
                <dt>Country</dt><dd>{{ $contractor->country }}</dd>
                <dt>Postal code</dt><dd>{{ $contractor->postal_code ?? '—' }}</dd>
            </dl>
        </x-card>
    </div>
    <div class="col-12 col-lg-6">
        <x-card title="Registration Information" class="h-100">
            <dl class="detail-list">
                <dt>Tax / GST number</dt><dd>{{ $contractor->tax_number ?? '—' }}</dd>
                <dt>License number</dt><dd>{{ $contractor->license_number ?? '—' }}</dd>
            </dl>
        </x-card>
    </div>
</div>
@endsection
