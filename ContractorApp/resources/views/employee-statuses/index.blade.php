@extends('layouts.app')

@section('title', 'Employee Statuses')

@section('content')
<x-page-header title="Employee Statuses" subtitle="The statuses shown in the Status dropdown when adding employees." />

@can('create', App\Models\EmployeeStatusOption::class)
    <form method="post" action="{{ route('employee-statuses.store') }}" class="card mb-3" style="max-width: 900px">
        @csrf
        <div class="card-body d-flex gap-2 align-items-end">
            <div class="flex-grow-1">
                <label for="status-name" class="form-label">New status</label>
                <input id="status-name" name="name" class="form-control" value="{{ old('name') }}" maxlength="100" required placeholder="e.g. Active, On Leave, Terminated">
                @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary">Add</button>
        </div>
    </form>
@endcan

@if ($statuses->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-person-badge" title="No statuses yet" message="Add the employment statuses your company uses." />
    </div>
@else
    <x-table-card :paginator="$statuses">
        <table class="table table-hover align-middle">
            <thead><tr><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @foreach ($statuses as $status)
                    <tr>
                        <td>
                            @can('update', $status)
                                <form method="post" action="{{ route('employee-statuses.update', $status) }}" class="d-flex gap-2">
                                    @csrf @method('PUT')
                                    <input name="name" class="form-control form-control-sm" value="{{ $status->name }}" maxlength="100" required aria-label="Rename {{ $status->name }}">
                                    <button class="btn btn-sm btn-outline-secondary">Save</button>
                                </form>
                            @else
                                <span class="fw-semibold">{{ $status->name }}</span>
                            @endcan
                        </td>
                        <td class="text-end text-nowrap">
                            @can('delete', $status)
                                <x-delete-button :action="route('employee-statuses.destroy', $status)" label="" class="btn btn-icon btn-sm text-danger" title="Delete this status?" :confirm="'Delete &quot;'.$status->name.'&quot;? Existing employees keep their value.'" button="Delete" />
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table-card>
@endif
@endsection
