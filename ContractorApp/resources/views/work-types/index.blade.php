@extends('layouts.app')

@section('title', 'Work Types')

@section('content')
<x-page-header title="Work Types" subtitle="The types of work shown in the Type of Work dropdown on projects." />

@can('create', App\Models\WorkType::class)
    <form method="post" action="{{ route('work-types.store') }}" class="card mb-3" style="max-width: 900px">
        @csrf
        <div class="card-body d-flex gap-2 align-items-end">
            <div class="flex-grow-1">
                <label for="work-type-name" class="form-label">New type of work</label>
                <input id="work-type-name" name="name" class="form-control" value="{{ old('name') }}" maxlength="80" required placeholder="e.g. Interior, Civil">
                @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary">Add</button>
        </div>
    </form>
@endcan

@if ($workTypes->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-tools" title="No types of work yet" message="Add the types of work your projects use." />
    </div>
@else
    <x-table-card :paginator="$workTypes">
        <table class="table table-hover align-middle">
            <thead><tr><th>Type of work</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @foreach ($workTypes as $workType)
                    <tr>
                        <td>
                            @can('update', $workType)
                                <form method="post" action="{{ route('work-types.update', $workType) }}" class="d-flex gap-2">
                                    @csrf @method('PUT')
                                    <input name="name" class="form-control form-control-sm" value="{{ $workType->name }}" maxlength="80" required aria-label="Rename {{ $workType->name }}">
                                    <button class="btn btn-sm btn-outline-secondary">Save</button>
                                </form>
                            @else
                                <span class="fw-semibold">{{ $workType->name }}</span>
                            @endcan
                        </td>
                        <td class="text-end text-nowrap">
                            @can('delete', $workType)
                                <x-delete-button :action="route('work-types.destroy', $workType)" label="" class="btn btn-icon btn-sm text-danger" title="Delete this type of work?" :confirm="'Delete “'.$workType->name.'”? Existing projects keep their value.'" button="Delete" />
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table-card>
@endif
@endsection
