@extends('layouts.app')

@section('title', 'Mark Attendance')

@section('content')
<x-page-header title="Mark Attendance" :subtitle="'Date: '.$parsedDate->format('d M Y')" :back="route('attendance.index', ['date' => $date])">
    <x-slot:actions>
        <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-clock-history me-1"></i>History</a>
    </x-slot:actions>
</x-page-header>

{{-- Date picker --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" action="{{ route('attendance.mark') }}" class="d-flex align-items-center gap-2 flex-wrap">
            <label class="text-muted small mb-0">Date:</label>
            <input type="date" class="form-control form-control-sm" name="date" value="{{ $date }}" style="max-width:160px" onchange="this.form.submit()" />
        </form>
    </div>
</div>

@if($employees->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-people" title="No active employees" message="Add employees first to mark attendance.">
            @can('create', App\Models\Employee::class)
                <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">Add Employee</a>
            @endcan
        </x-empty-state>
    </div>
@else
<form method="post" action="{{ route('attendance.save-mark') }}">
    @csrf
    <input type="hidden" name="date" value="{{ $date }}" />

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong class="small text-uppercase tracking-wide text-muted">{{ $parsedDate->format('l, d M Y') }}</strong>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-success" id="markAllPresent">All Present</button>
                <button type="button" class="btn btn-sm btn-outline-danger" id="markAllAbsent">All Absent</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="border-collapse:separate;border-spacing:0">
                <thead class="table-light">
                    <tr>
                        <th style="width:35%">Employee</th>
                        <th style="width:25%">Morning</th>
                        <th style="width:25%">Evening</th>
                        <th style="width:15%">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $i => $emp)
                    @php $att = $existing[$emp->id] ?? null; @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $emp->name }}</div>
                            @if($emp->designation)<div class="text-muted small">{{ $emp->designation }}</div>@endif
                        </td>
                        <td>
                            <div class="cs-wrapper cs-sm" id="morningWrapper_{{ $emp->id }}">
                                <div class="cs-trigger">
                                    <span class="cs-value">{{ $att ? $att->morning_status->label() : 'Present' }}</span>
                                    <i class="bi bi-chevron-down cs-arrow"></i>
                                </div>
                                <div class="cs-panel">
                                    <div class="cs-list">
                                        @foreach($statuses as $val => $label)
                                            <div class="cs-item" data-value="{{ $val }}">{{ $label }}</div>
                                        @endforeach
                                    </div>
                                </div>
                                <input type="hidden" name="attendance[{{ $emp->id }}][morning_status]" id="morningInput_{{ $emp->id }}" value="{{ $att ? $att->morning_status->value : 'present' }}">
                            </div>
                        </td>
                        <td>
                            <div class="cs-wrapper cs-sm" id="eveningWrapper_{{ $emp->id }}">
                                <div class="cs-trigger">
                                    <span class="cs-value {{ $att && $att->evening_status ? '' : 'cs-placeholder' }}">{{ $att && $att->evening_status ? $att->evening_status->label() : '— Optional —' }}</span>
                                    <i class="bi bi-chevron-down cs-arrow"></i>
                                </div>
                                <div class="cs-panel">
                                    <div class="cs-list">
                                        <div class="cs-item" data-value="">— Optional —</div>
                                        @foreach($statuses as $val => $label)
                                            <div class="cs-item" data-value="{{ $val }}">{{ $label }}</div>
                                        @endforeach
                                    </div>
                                </div>
                                <input type="hidden" name="attendance[{{ $emp->id }}][evening_status]" id="eveningInput_{{ $emp->id }}" value="{{ $att && $att->evening_status ? $att->evening_status->value : '' }}">
                            </div>
                        </td>
                        <td>
                            <input type="text" name="attendance[{{ $emp->id }}][notes]" class="form-control form-control-sm" placeholder="Note…" value="{{ old('attendance.'.$emp->id.'.notes', $att?->notes ?? '') }}" maxlength="500" style="min-height:calc(1.5em + 0.5rem + 2px)">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <a href="{{ route('attendance.index', ['date' => $date]) }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Save Attendance</button>
        </div>
    </div>
</form>
@endif

@push('scripts')
<script>
(function () {
    @foreach($employees as $emp)
    (function() {
        var mCs = new CustomSelect('morningWrapper_{{ $emp->id }}');
        var mIn = document.getElementById('morningInput_{{ $emp->id }}');
        var mOrig = mCs._select.bind(mCs);
        mCs._select = function(v, l) { mOrig(v, l); mIn.value = v; };

        var eCs = new CustomSelect('eveningWrapper_{{ $emp->id }}');
        var eIn = document.getElementById('eveningInput_{{ $emp->id }}');
        var eOrig = eCs._select.bind(eCs);
        eCs._select = function(v, l) { eOrig(v, l); eIn.value = v; };
    })();
    @endforeach

    // Store all morning CS instances for bulk actions
    var morningCsList = [
        @foreach($employees as $emp)
        {cs: null, inputId: 'morningInput_{{ $emp->id }}', wrapperId: 'morningWrapper_{{ $emp->id }}'},
        @endforeach
    ];

    function bulkMark(value, label) {
        morningCsList.forEach(function(item) {
            var cs = new CustomSelect(item.wrapperId);
            cs._select(value, label);
            document.getElementById(item.inputId).value = value;
        });
    }

    document.getElementById('markAllPresent').addEventListener('click', function() { bulkMark('present', 'Present'); });
    document.getElementById('markAllAbsent').addEventListener('click', function() { bulkMark('absent', 'Absent'); });
})();
</script>
@endpush
@endsection
