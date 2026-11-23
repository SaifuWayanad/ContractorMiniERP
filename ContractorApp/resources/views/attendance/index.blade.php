@extends('layouts.app')

@section('title', 'Attendance')

@section('content')
<x-page-header title="Attendance" subtitle="View and manage daily attendance records.">
    <x-slot:actions>
        <a href="{{ route('attendance.calendar-view') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-calendar3 me-1"></i>Calendar
        </a>
        @can('create', App\Models\Attendance::class)
            <a href="{{ route('attendance.mark', ['date' => today()->format('Y-m-d')]) }}" class="btn btn-primary btn-sm"><i class="bi bi-calendar-check me-1"></i>Mark Attendance</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<form method="get" action="{{ route('attendance.index') }}" class="mb-4" id="attFilterForm">
    @if ($isEmployeePortal)
        <input type="hidden" name="employee_id" value="{{ $linkedEmployeeId }}">
    @endif
    <div class="row g-2 align-items-end">
        <div class="col-sm-6 col-md-3">
            <label class="form-label text-muted small mb-1">Date</label>
            <input type="date" class="form-control form-control-sm" name="date" id="attDateInput" value="{{ $filters['date'] }}" />
        </div>
        @unless ($isEmployeePortal)
        <div class="col-sm-6 col-md-3">
            <label class="form-label text-muted small mb-1">Employee</label>
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
        @endunless
        <div class="col-sm-6 col-md-3">
            <label class="form-label text-muted small mb-1">Status</label>
            <select class="form-select form-select-sm" name="status" id="attStatusSelect">
                <option value="">All Status</option>
                <option value="present"  {{ $filters['status'] === 'present'  ? 'selected' : '' }}>Present</option>
                <option value="half_day" {{ $filters['status'] === 'half_day' ? 'selected' : '' }}>Half Day</option>
                <option value="leave"    {{ $filters['status'] === 'leave'    ? 'selected' : '' }}>Leave</option>
            </select>
        </div>
        @if(! $isEmployeePortal && ($filters['employee_id'] || $filters['status']))
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
                    <th>Status</th>
                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendances as $att)
                @php
                    $m = $att->morning_status;
                    $e = $att->evening_status;
                    $leaveLike = [\App\Enums\AttendanceStatus::Leave, \App\Enums\AttendanceStatus::Absent];
                    if ($m === \App\Enums\AttendanceStatus::HalfDay || $e === \App\Enums\AttendanceStatus::HalfDay) {
                        $derived = \App\Enums\AttendanceStatus::HalfDay;
                    } elseif (
                        ($m === \App\Enums\AttendanceStatus::Present && $e !== null && in_array($e, $leaveLike)) ||
                        (in_array($m, $leaveLike) && $e === \App\Enums\AttendanceStatus::Present)
                    ) {
                        $derived = \App\Enums\AttendanceStatus::HalfDay;
                    } elseif ($m === \App\Enums\AttendanceStatus::Present) {
                        $derived = \App\Enums\AttendanceStatus::Present;
                    } else {
                        $derived = \App\Enums\AttendanceStatus::Leave;
                    }
                @endphp
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
                    <td><span class="badge {{ $derived->badgeClass() }}">{{ $derived->label() }}</span></td>
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
    var form = document.getElementById('attFilterForm');
    @unless ($isEmployeePortal)
    var empCs    = new CustomSelect('attEmpWrapper');
    var empInput = document.getElementById('attEmpInput');
    var origSel  = empCs._select.bind(empCs);
    empCs._select = function (v, l) { origSel(v, l); empInput.value = v; form.submit(); };
    @endunless
    document.getElementById('attDateInput').addEventListener('change', function () { form.submit(); });
    document.getElementById('attStatusSelect').addEventListener('change', function () { form.submit(); });
})();
</script>
@endpush
@endsection
