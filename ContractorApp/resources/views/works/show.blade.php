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
                @if ($work->payments->isNotEmpty())
                <dt>Payments</dt>
                <dd>
                    <table class="table table-sm mb-0" style="width:auto;min-width:200px">
                        <tbody>
                            @foreach ($work->payments as $wp)
                            <tr>
                                <td class="ps-0 text-muted" style="font-size:.85rem">{{ $wp->payment_method ?? '—' }}</td>
                                <td class="pe-0 text-end fw-semibold" style="font-size:.85rem;padding-left:1.5rem">₹{{ number_format((float)$wp->amount, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="border-top:2px solid #dee2e6">
                                <td class="ps-0 fw-semibold" style="font-size:.85rem">Total</td>
                                <td class="pe-0 text-end fw-bold" style="font-size:.85rem;padding-left:1.5rem">₹{{ number_format((float)$work->amount, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </dd>
                @endif
            </dl>
        </x-card>
        @if ($work->description)
            <x-card title="Description"><p class="mb-0" style="white-space: pre-line">{{ $work->description }}</p></x-card>
        @endif
    </div>
    <div class="col-12 col-lg-5 d-flex flex-column gap-3">
        <x-card title="Schedule">
            <dl class="detail-list">
                <dt>Start date</dt><dd>{{ $work->start_date?->format('d M Y') ?? '—' }}</dd>
                <dt>Due date</dt><dd>{{ $work->due_date?->format('d M Y') ?? '—' }}</dd>
                <dt>Completed</dt><dd>{{ $work->completed_at?->format('d M Y, h:i A') ?? '—' }}</dd>
                <dt>Created</dt><dd>{{ $work->created_at->format('d M Y') }}</dd>
            </dl>
        </x-card>

        @if($work->employees->isNotEmpty())
        <x-card title="Assigned Employees">
            <div class="d-flex flex-wrap gap-2">
                @foreach($work->employees as $emp)
                <a href="{{ route('employees.show', $emp) }}" class="d-inline-flex align-items-center gap-2 border rounded px-3 py-2 text-decoration-none text-body" style="font-size:.88rem">
                    <i class="bi bi-person-fill text-muted"></i>
                    <span>{{ $emp->name }}</span>
                </a>
                @endforeach
            </div>
        </x-card>
        @endif
    </div>
</div>
@endsection
