@extends('layouts.app')

@section('title', 'Salary Types')

@section('content')
<x-page-header title="Salary Types" subtitle="The salary types shown in the Salary Type dropdown when adding employees." />

@can('create', App\Models\EmployeeSalaryType::class)
    <form method="post" action="{{ route('employee-salary-types.store') }}" class="card mb-3" style="max-width: 900px">
        @csrf
        <div class="card-body d-flex gap-2 align-items-end">
            <div class="flex-grow-1">
                <label for="salary-type-name" class="form-label">New salary type</label>
                <input id="salary-type-name" name="name" class="form-control" value="{{ old('name') }}" maxlength="100" required placeholder="e.g. Daily, Monthly, Weekly">
                @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary">Add</button>
        </div>
    </form>
@endcan

@if ($salaryTypes->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-cash-stack" title="No salary types yet" message="Add the salary types used in your company." />
    </div>
@else
    <x-table-card :paginator="$salaryTypes">
        <table class="table table-hover align-middle">
            <thead><tr><th>Salary Type</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @foreach ($salaryTypes as $type)
                    <tr>
                        <td>
                            @can('update', $type)
                                <form method="post" action="{{ route('employee-salary-types.update', $type) }}" class="d-flex gap-2">
                                    @csrf @method('PUT')
                                    <input name="name" class="form-control form-control-sm" value="{{ $type->name }}" maxlength="100" required aria-label="Rename {{ $type->name }}">
                                    <button class="btn btn-sm btn-outline-secondary">Save</button>
                                </form>
                            @else
                                <span class="fw-semibold">{{ $type->name }}</span>
                            @endcan
                        </td>
                        <td class="text-end text-nowrap">
                            @can('delete', $type)
                                <x-delete-button :action="route('employee-salary-types.destroy', $type)" label="" class="btn btn-icon btn-sm text-danger" title="Delete this salary type?" :confirm="'Delete &quot;'.$type->name.'&quot;? Existing employees keep their value.'" button="Delete" />
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table-card>
@endif
@endsection
