<?php

namespace App\Http\Controllers;

use App\Enums\EmployeeStatus;
use App\Enums\SalaryType;
use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Employee::class);

        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'status' => $request->string('status')->value() ?: null,
        ];

        $employees = Employee::query()
            ->when($filters['search'] !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($filters): void {
                $like = '%'.$filters['search'].'%';
                $q->where('name', 'like', $like)
                    ->orWhere('employee_code', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('designation', 'like', $like);
            }))
            ->when($filters['status'], fn (Builder $q, string $s) => $q->where('status', $s))
            ->orderBy('name')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('employees.index', [
            'employees'  => $employees,
            'filters'    => $filters,
            'statusOptions' => EmployeeStatus::options(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Employee::class);

        return view('employees.create', $this->formData(new Employee()));
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        $this->authorize('create', Employee::class);

        Employee::create($request->validated());

        return to_route('employees.index')->with('toast', 'Employee added.');
    }

    public function show(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load(['attendances' => fn ($q) => $q->orderByDesc('date')->limit(10), 'salaryPayments' => fn ($q) => $q->orderByDesc('payment_date')->limit(6), 'works' => fn ($q) => $q->with('project:id,name')->orderByDesc('id')->limit(5), 'user']);

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);

        return view('employees.edit', $this->formData($employee));
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $employee->update($request->validated());

        return to_route('employees.show', $employee)->with('toast', 'Employee updated.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);

        $employee->delete();

        return to_route('employees.index')->with('toast', 'Employee deleted.');
    }

    /** @return array<string, mixed> */
    private function formData(Employee $employee): array
    {
        return [
            'employee'      => $employee,
            'salaryTypes'   => SalaryType::options(),
            'statusOptions' => EmployeeStatus::options(),
        ];
    }
}
