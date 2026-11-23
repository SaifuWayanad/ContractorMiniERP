<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Attendance::class);

        $user           = $request->user();
        $linkedEmployee = $user->linkedEmployee();

        $filters = [
            'date'        => $request->string('date')->value() ?: today()->format('Y-m-d'),
            // Employee portal users always see only their own records.
            'employee_id' => $linkedEmployee ? $linkedEmployee->id : ($request->integer('employee_id') ?: null),
            'status'      => in_array($request->string('status')->value(), ['present', 'half_day', 'leave']) ? $request->string('status')->value() : null,
        ];

        $date = \Carbon\Carbon::parse($filters['date']);

        $attendances = Attendance::with('employee')
            ->whereDate('date', $date)
            ->when($filters['employee_id'], fn (Builder $q, int $eid) => $q->where('employee_id', $eid))
            ->when($filters['status'] === 'present', fn (Builder $q) => $q
                ->where('morning_status', 'present')
                ->where(fn (Builder $q) => $q->whereNull('evening_status')->orWhere('evening_status', 'present'))
            )
            ->when($filters['status'] === 'half_day', fn (Builder $q) => $q
                ->where(fn (Builder $q) => $q
                    ->where(fn (Builder $q) => $q->where('morning_status', 'present')->where('evening_status', 'absent'))
                    ->orWhere(fn (Builder $q) => $q->where('morning_status', 'absent')->where('evening_status', 'present'))
                    ->orWhere('morning_status', 'half_day')
                    ->orWhere('evening_status', 'half_day')
                )
            )
            ->when($filters['status'] === 'leave', fn (Builder $q) => $q
                ->whereIn('morning_status', ['leave', 'absent'])
                ->where(fn (Builder $q) => $q->whereNull('evening_status')->orWhereIn('evening_status', ['leave', 'absent']))
            )
            ->orderBy('employee_id')
            ->get();

        $employees = $linkedEmployee
            ? collect([$linkedEmployee->id => $linkedEmployee->name])
            : Employee::where('status', 'active')->orderBy('name')->pluck('name', 'id');

        return view('attendance.index', [
            'attendances'      => $attendances,
            'filters'          => $filters,
            'date'             => $date,
            'employees'        => $employees,
            'isEmployeePortal' => $linkedEmployee !== null,
            'linkedEmployeeId' => $linkedEmployee?->id,
        ]);
    }

    public function calendarView(Request $request): View
    {
        $this->authorize('viewAny', Attendance::class);

        $user           = $request->user();
        $linkedEmployee = $user->linkedEmployee();

        return view('attendance.calendar-view', [
            'isEmployeePortal' => $linkedEmployee !== null,
            'linkedEmployeeId' => $linkedEmployee?->id,
        ]);
    }

    public function calendarData(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Attendance::class);

        $month      = $request->string('month')->value() ?: now()->format('Y-m');
        $start      = \Carbon\Carbon::parse($month . '-01')->startOfMonth();
        $end        = $start->copy()->endOfMonth();
        $employeeId = $request->integer('employee_id') ?: null;

        $records = Attendance::whereBetween('date', [$start, $end])
            ->when($employeeId, fn (Builder $q) => $q->where('employee_id', $employeeId))
            ->get();

        $summary = [];
        foreach ($records->groupBy(fn ($a) => $a->date->format('Y-m-d')) as $date => $dayRecords) {
            $p = $h = $a = $l = 0;
            foreach ($dayRecords as $att) {
                $m = $att->morning_status;
                $e = $att->evening_status;
                $leaveLike = [AttendanceStatus::Leave, AttendanceStatus::Absent];
                $isLeave   = fn ($s) => $s === null || in_array($s, $leaveLike, true);
                if (
                    $m === AttendanceStatus::HalfDay || $e === AttendanceStatus::HalfDay ||
                    ($m === AttendanceStatus::Present && $e !== null && $isLeave($e)) ||
                    ($isLeave($m) && $m !== null && $e === AttendanceStatus::Present)
                ) {
                    $h++;
                } elseif ($m === AttendanceStatus::Present) {
                    $p++;
                } else {
                    $l++;
                }
            }
            $summary[$date] = ['p' => $p, 'h' => $h, 'l' => $l];
        }

        return response()->json($summary);
    }

    public function mark(Request $request): View
    {
        $this->authorize('create', Attendance::class);

        $date = $request->string('date')->value() ?: today()->format('Y-m-d');
        $parsedDate = \Carbon\Carbon::parse($date);

        $employees = Employee::where('status', 'active')->orderBy('name')->get(['id', 'name', 'designation']);

        $existing = Attendance::whereDate('date', $parsedDate)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        return view('attendance.mark', [
            'employees' => $employees,
            'existing'  => $existing,
            'date'      => $date,
            'parsedDate' => $parsedDate,
            'statuses'  => [
                AttendanceStatus::Present->value => AttendanceStatus::Present->label(),
                AttendanceStatus::Leave->value   => AttendanceStatus::Leave->label(),
            ],
        ]);
    }

    public function saveMark(Request $request): RedirectResponse
    {
        $this->authorize('create', Attendance::class);

        $validated = $request->validate([
            'date'                          => ['required', 'date'],
            'attendance'                    => ['required', 'array'],
            'attendance.*.morning_status'   => ['required', 'string'],
            'attendance.*.evening_status'   => ['nullable', 'string'],
            'attendance.*.notes'            => ['nullable', 'string', 'max:500'],
        ]);

        $contractorId = $request->user()->contractor_id;
        $date = $validated['date'];

        foreach ($validated['attendance'] as $employeeId => $data) {
            Attendance::updateOrCreate(
                ['employee_id' => $employeeId, 'date' => $date],
                [
                    'contractor_id'  => $contractorId,
                    'morning_status' => $data['morning_status'],
                    'evening_status' => $data['evening_status'] ?? null,
                    'notes'          => $data['notes'] ?? null,
                ]
            );
        }

        return to_route('attendance.index', ['date' => $date])->with('toast', 'Attendance saved.');
    }

    public function destroy(Attendance $attendance): RedirectResponse
    {
        $this->authorize('delete', $attendance);

        $date = $attendance->date->format('Y-m-d');
        $attendance->delete();

        return to_route('attendance.index', ['date' => $date])->with('toast', 'Attendance record deleted.');
    }
}
