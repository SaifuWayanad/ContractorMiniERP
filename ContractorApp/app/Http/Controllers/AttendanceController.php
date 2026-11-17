<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Attendance::class);

        $filters = [
            'date'        => $request->string('date')->value() ?: today()->format('Y-m-d'),
            'employee_id' => $request->integer('employee_id') ?: null,
        ];

        $date = \Carbon\Carbon::parse($filters['date']);

        $attendances = Attendance::with('employee')
            ->whereDate('date', $date)
            ->when($filters['employee_id'], fn (Builder $q, int $eid) => $q->where('employee_id', $eid))
            ->orderBy('employee_id')
            ->get();

        $employees = Employee::where('status', 'active')->orderBy('name')->pluck('name', 'id');

        return view('attendance.index', [
            'attendances' => $attendances,
            'filters'     => $filters,
            'date'        => $date,
            'employees'   => $employees,
        ]);
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
            'statuses'  => AttendanceStatus::options(),
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
