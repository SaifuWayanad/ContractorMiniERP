@extends('layouts.app')

@section('title', 'Employee Designations')

@section('content')
<x-page-header title="Employee Designations" subtitle="The designations shown in the Designation dropdown when adding employees." />

@can('create', App\Models\EmployeeDesignation::class)
    <form method="post" action="{{ route('employee-designations.store') }}" class="card mb-3" style="max-width: 900px">
        @csrf
        <div class="card-body d-flex gap-2 align-items-end">
            <div class="flex-grow-1">
                <label for="designation-name" class="form-label">New designation</label>
                <input id="designation-name" name="name" class="form-control" value="{{ old('name') }}" maxlength="100" required placeholder="e.g. Mason, Supervisor, Electrician">
                @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary">Add</button>
        </div>
    </form>
@endcan

@if ($designations->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-person-badge" title="No designations yet" message="Add the designations your employees hold." />
    </div>
@else
    <x-table-card :paginator="$designations">
        <table class="table table-hover align-middle">
            <thead><tr><th>Designation</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @foreach ($designations as $designation)
                    <tr>
                        <td>
                            @can('update', $designation)
                                <form method="post" action="{{ route('employee-designations.update', $designation) }}" class="d-flex gap-2">
                                    @csrf @method('PUT')
                                    <input name="name" class="form-control form-control-sm" value="{{ $designation->name }}" maxlength="100" required aria-label="Rename {{ $designation->name }}">
                                    <button class="btn btn-sm btn-outline-secondary">Save</button>
                                </form>
                            @else
                                <span class="fw-semibold">{{ $designation->name }}</span>
                            @endcan
                        </td>
                        <td class="text-end text-nowrap">
                            @can('delete', $designation)
                                <x-delete-button :action="route('employee-designations.destroy', $designation)" label="" class="btn btn-icon btn-sm text-danger" title="Delete this designation?" :confirm="'Delete &quot;'.$designation->name.'&quot;? Existing employees keep their value.'" button="Delete" />
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table-card>
@endif
@endsection
