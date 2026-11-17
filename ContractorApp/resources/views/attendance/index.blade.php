@extends('layouts.app')

@section('title', 'Attendance')

@section('content')
<x-page-header title="Attendance" subtitle="View and manage daily attendance records.">
    <x-slot:actions>
        @can('create', App\Models\Attendance::class)
            <a href="{{ route('attendance.mark', ['date' => today()->format('Y-m-d')]) }}" class="btn btn-primary btn-sm"><i class="bi bi-calendar-check me-1"></i>Mark Attendance</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<form method="get" action="{{ route('attendance.index') }}" class="mb-4" data-auto-filter>
    <div class="row g-2 align-items-end">
        <div class="col-sm-6 col-md-3">
            <label class="form-label text-muted small mb-1">Date</label>
            <input type="date" class="form-control form-control-sm" name="date" value="{{ $filters['date'] }}" style="min-height:calc(1.5em + 0.5rem + 2px)" />
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="cs-wrapper cs-sm" id="attEmpWrapper">
                <div class="cs-trigger">
                    <span class="cs-value {{ $filters['employee_id'] ? '' : 'cs-placeholder' }}">{{ $filters['employee_id'] ? ($employees[$filters['employee_id']] ?? 'All Employees') : 'All Employees' }}</span>
                    <i class="bi bi-chevron-down cs-arrow"></i>
                </div>
                <div class="cs-panel">
                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                    <div class="cs-list">
                        <div class="cs-item" data-value="">All Employees</div>
                        @foreach($employees as $id => $name)
                            <div class="cs-item" data-value="{{ $id }}">{{ $name }}</div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" name="employee_id" id="attEmpInput" value="{{ $filters['employee_id'] ?? '' }}">
            </div>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-secondary btn-sm">View</button>
        </div>
        @if($filters['employee_id'])
            <div class="col-auto">
                <a href="{{ route('attendance.index', ['date' => $filters['date']]) }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        @endif
    </div>
</form>

<x-card>
    <x-slot:title>
        <i class="bi bi-calendar-week me-1"></i>
        {{ $date->format('d M Y') }}
        <a href="{{ route('attendance.mark', ['date' => $filters['date']]) }}" class="btn btn-sm btn-outline-primary ms-3" @cannot('create', App\Models\Attendance::class) style="display:none" @endcannot>
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
    </x-slot:title>

    @if($attendances->isEmpty())
        <div class="empty-inline">
            <i class="bi bi-calendar-x d-block fs-4 mb-1"></i>
            No attendance recorded for this date.
            @can('create', App\Models\Attendance::class)
                <a href="{{ route('attendance.mark', ['date' => $filters['date']]) }}" class="btn btn-primary btn-sm mt-2">Mark Attendance</a>
            @endcan
        </div>
    @else
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Morning</th>
                    <th>Evening</th>
                    <th class="d-none d-md-table-cell">Notes</th>
                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendances as $att)
                <tr>
                    <td>
                        <a href="{{ route('employees.show', $att->employee_id) }}" class="row-title">{{ $att->employee->name }}</a>
                    </td>
                    <td><span class="badge {{ $att->morning_status->badgeClass() }}">{{ $att->morning_status->label() }}</span></td>
                    <td>
                        @if($att->evening_status)
                            <span class="badge {{ $att->evening_status->badgeClass() }}">{{ $att->evening_status->label() }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="d-none d-md-table-cell text-muted small">{{ $att->notes ?? '—' }}</td>
                    <td class="text-end">
                        @can('delete', $att)
                            <x-delete-button :action="route('attendance.destroy', $att)" class="btn btn-icon btn-sm text-danger" title="Delete record?" confirm="Delete this attendance record?" icon="bi-trash" />
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-card>
@push('scripts')
<script>
(function () {
    var form = document.querySelector('form[data-auto-filter]');
    var cs = new CustomSelect('attEmpWrapper');
    var input = document.getElementById('attEmpInput');
    var orig = cs._select.bind(cs);
    cs._select = function(v, l) { orig(v, l); input.value = v; };
})();
</script>
@endpush
@endsection
