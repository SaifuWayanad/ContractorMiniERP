@extends('layouts.app')

@section('title', $employee->name)

@section('content')
<x-page-header :title="$employee->name" :subtitle="$employee->designation ?? 'Employee'" :back="route('employees.index')">
    <x-slot:actions>
        @can('update', $employee)
            @if(!$employee->hasLogin())
                <a href="{{ route('employees.login.create', $employee) }}" class="btn btn-outline-info btn-sm"><i class="bi bi-person-plus me-1"></i>Create Login</a>
            @endif
            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
        @endcan
        @can('create', App\Models\Attendance::class)
            <a href="{{ route('attendance.mark', ['date' => today()->format('Y-m-d')]) }}" class="btn btn-primary btn-sm"><i class="bi bi-calendar-check me-1"></i>Mark Attendance</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="row g-3">
    {{-- Details card --}}
    <div class="col-12 col-md-5 col-lg-4">
        <x-card title="Employee Details">
            <dl class="detail-list">
                <div class="detail-row">
                    <dt>Status</dt>
                    <dd><span class="badge {{ App\Models\EmployeeStatusOption::badgeClass($employee->status ?? '') }}">{{ $employee->status ?? '—' }}</span></dd>
                </div>
                @if($employee->employee_code)
                <div class="detail-row">
                    <dt>Code</dt>
                    <dd>{{ $employee->employee_code }}</dd>
                </div>
                @endif
                @if($employee->phone)
                <div class="detail-row">
                    <dt>Phone</dt>
                    <dd>{{ $employee->phone }}</dd>
                </div>
                @endif
                @if($employee->email)
                <div class="detail-row">
                    <dt>Email</dt>
                    <dd>{{ $employee->email }}</dd>
                </div>
                @endif
                @if($employee->joining_date)
                <div class="detail-row">
                    <dt>Joined</dt>
                    <dd>{{ $employee->joining_date->format('d M Y') }}</dd>
                </div>
                @endif
                <div class="detail-row">
                    <dt>Salary Type</dt>
                    <dd>{{ $employee->salary_type ?? '—' }}</dd>
                </div>
                <div class="detail-row">
                    <dt>Salary Amount</dt>
                    <dd>₹{{ number_format((float)$employee->salary_amount, 2) }}</dd>
                </div>
            </dl>
            @if($employee->notes)
                <div class="mt-3 pt-3 border-top">
                    <div class="text-muted small mb-1">Notes</div>
                    <div class="small">{{ $employee->notes }}</div>
                </div>
            @endif
            @can('update', $employee)
            <div class="mt-3 pt-3 border-top">
                <div class="text-muted small mb-2">Change Status</div>
                @if($statusOptions->isNotEmpty())
                <form method="post" action="{{ route('employees.update-status', $employee) }}" class="d-flex gap-2">
                    @csrf @method('PATCH')
                    <select name="status" class="form-select form-select-sm">
                        @foreach($statusOptions as $s)
                            <option value="{{ $s }}" @selected($employee->status === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">Update</button>
                </form>
                @else
                <div class="small text-muted">No status options yet. <a href="{{ route('employee-statuses.index') }}">Add status options</a></div>
                @endif
            </div>
            @endcan
        </x-card>

        {{-- Login card --}}
        <x-card title="Login Access" class="mt-3">
            @if($employee->hasLogin() && $employee->user)
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width:40px;height:40px;flex-shrink:0">
                        <i class="bi bi-person-check-fill text-success"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold text-truncate">{{ $employee->user->username }}</div>
                        <div class="text-muted small">
                            <span class="badge badge-soft-success">Active</span>
                            Contractor Staff
                        </div>
                    </div>
                </div>
                @can('update', $employee)
                <div class="mt-3 pt-2 border-top">
                    <x-delete-button
                        :action="route('employees.login.destroy', $employee)"
                        class="btn btn-sm btn-outline-danger"
                        title="Remove Login"
                        confirm="Remove login access for {{ $employee->name }}? They will no longer be able to sign in."
                        label="Remove Login"
                        icon="bi-person-x"
                    />
                </div>
                @endcan
            @else
                <div class="text-muted small mb-3">This employee does not have login access yet.</div>
                @can('update', $employee)
                    <a href="{{ route('employees.login.create', $employee) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-person-plus me-1"></i>Create Login
                    </a>
                @endcan
            @endif
        </x-card>

        @if($employee->salaryPayments->isNotEmpty())
        <x-card title="Recent Salary Payments" class="mt-3">
            <ul class="list-unstyled mb-0">
                @foreach($employee->salaryPayments as $sp)
                <li class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div>
                        <div class="small fw-semibold">{{ \Carbon\Carbon::createFromDate($sp->period_year, $sp->period_month, 1)->format('M Y') }}</div>
                        <div class="text-muted" style="font-size:0.78rem">{{ $sp->payment_date->format('d M Y') }}</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold">₹{{ number_format((float)$sp->amount, 2) }}</div>
                        <span class="badge {{ $sp->status->badgeClass() }}" style="font-size:0.68rem">{{ $sp->status->label() }}</span>
                    </div>
                </li>
                @endforeach
            </ul>
            @can('viewAny', App\Models\SalaryPayment::class)
                <div class="mt-2"><a href="{{ route('salary-payments.index', ['employee_id' => $employee->id]) }}" class="card-link small">View All</a></div>
            @endcan
        </x-card>
        @endif
    </div>

    {{-- Attendance + Works --}}
    <div class="col-12 col-md-7 col-lg-8">
        <x-card title="Recent Attendance" class="mb-3">
            @if($employee->attendances->isEmpty())
                <div class="empty-inline"><i class="bi bi-calendar-x d-block fs-4 mb-1"></i>No attendance records yet.</div>
            @else
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Morning</th>
                            <th>Evening</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employee->attendances as $att)
                        <tr>
                            <td class="text-nowrap">{{ $att->date->format('d M Y') }}</td>
                            <td><span class="badge {{ $att->morning_status->badgeClass() }}">{{ $att->morning_status->label() }}</span></td>
                            <td>
                                @if($att->evening_status)
                                    <span class="badge {{ $att->evening_status->badgeClass() }}">{{ $att->evening_status->label() }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $att->notes ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-2"><a href="{{ route('attendance.index', ['employee_id' => $employee->id]) }}" class="card-link small">View All</a></div>
            @endif
        </x-card>

        @if($employee->works->isNotEmpty())
        <x-card title="Assigned Works">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Work</th>
                        <th class="d-none d-sm-table-cell">Project</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employee->works as $work)
                    <tr>
                        <td><a href="{{ route('works.show', $work) }}" class="row-title">{{ $work->title }}</a></td>
                        <td class="d-none d-sm-table-cell text-muted small">{{ $work->project?->name ?? '—' }}</td>
                        <td><span class="badge {{ $work->status->badgeClass() }}">{{ $work->status->label() }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </x-card>
        @endif
    </div>
</div>
@endsection
