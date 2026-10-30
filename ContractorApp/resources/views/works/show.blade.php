@extends('layouts.app')

@section('title', $work->title)

@section('content')
<x-page-header :title="$work->title">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('works.index') }}">Works</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($work->title, 40) }}</li>
    </x-slot:breadcrumbs>
    <x-slot:actions>
        @can('update', $work)
            @if ($work->status !== App\Enums\WorkStatus::Completed)
                <form method="post" action="{{ route('works.status', $work) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="completed" />
                    <button type="submit" class="btn btn-success btn-sm" data-loading-text="Updating…"><i class="bi bi-check2 me-1"></i>Mark completed</button>
                </form>
            @endif
            @include('works.status-menu', ['work' => $work])
        @endcan
        @can('delete', $work)
            <x-delete-button :action="route('works.destroy', $work)" title="Delete this work?" :confirm="'Delete “'.$work->title.'”? This cannot be undone.'" />
        @endcan
        @can('update', $work)
            <a href="{{ route('works.edit', $work) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="row g-3">
    <div class="col-12 col-lg-7 d-flex flex-column gap-3">
        <x-card title="Details">
            <dl class="detail-list">
                <dt>Status</dt><dd><x-status-badge :status="$work->status" /> @if ($work->isOverdue()) <span class="badge badge-soft-danger">Overdue</span> @endif</dd>
                <dt>Priority</dt><dd><x-status-badge :status="$work->priority" /></dd>
                <dt>Project</dt><dd><a href="{{ route('projects.show', $work->project) }}">{{ $work->project->name }}</a></dd>
                <dt>Client</dt><dd>@if ($work->client)<a href="{{ route('clients.show', $work->client) }}">{{ $work->client->name }}</a>@else — @endif</dd>
                @if ($work->amount !== null)
                <dt>Amount</dt><dd>₹{{ number_format((float)$work->amount, 2) }}</dd>
                @endif
            </dl>
        </x-card>
        @if ($work->description)
            <x-card title="Description"><p class="mb-0" style="white-space: pre-line">{{ $work->description }}</p></x-card>
        @endif
    </div>
    <div class="col-12 col-lg-5">
        <x-card title="Schedule">
            <dl class="detail-list">
                <dt>Start date</dt><dd>{{ $work->start_date?->format('d M Y') ?? '—' }}</dd>
                <dt>Due date</dt><dd>{{ $work->due_date?->format('d M Y') ?? '—' }}</dd>
                <dt>Completed</dt><dd>{{ $work->completed_at?->format('d M Y, h:i A') ?? '—' }}</dd>
                <dt>Created</dt><dd>{{ $work->created_at->format('d M Y') }}</dd>
            </dl>
        </x-card>
    </div>
</div>
@endsection
