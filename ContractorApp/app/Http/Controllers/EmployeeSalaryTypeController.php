<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSalaryType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeSalaryTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', EmployeeSalaryType::class);

        return view('employee-salary-types.index', [
            'salaryTypes' => EmployeeSalaryType::query()->orderBy('name')->paginate($this->perPage()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', EmployeeSalaryType::class);

        $type = $this->createFrom($request);

        return to_route('employee-salary-types.index')->with('toast', "Salary type \"{$type->name}\" added.");
    }

    public function quickStore(Request $request): JsonResponse
    {
        $this->authorize('create', EmployeeSalaryType::class);

        $type = $this->createFrom($request);

        return response()->json(['id' => $type->id, 'name' => $type->name], 201);
    }

    public function update(Request $request, EmployeeSalaryType $employeeSalaryType): RedirectResponse
    {
        $this->authorize('update', $employeeSalaryType);

        $employeeSalaryType->update($this->validatedName($request, $employeeSalaryType->contractor_id, $employeeSalaryType->id));

        return to_route('employee-salary-types.index')->with('toast', "Salary type renamed to \"{$employeeSalaryType->name}\".");
    }

    public function destroy(EmployeeSalaryType $employeeSalaryType): RedirectResponse
    {
        $this->authorize('delete', $employeeSalaryType);

        $name = $employeeSalaryType->name;
        $employeeSalaryType->delete();

        return to_route('employee-salary-types.index')->with('toast', "Salary type \"{$name}\" deleted. Existing employees keep their value.");
    }

    private function createFrom(Request $request): EmployeeSalaryType
    {
        $contractorId = $request->user()->contractor_id;
        $data = $this->validatedName($request, $contractorId);

        $type = new EmployeeSalaryType($data);
        $type->contractor_id = $contractorId;
        $type->save();

        return $type;
    }

    /** @return array{name: string} */
    private function validatedName(Request $request, ?int $contractorId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('employee_salary_types', 'name')
                ->where('contractor_id', $contractorId)
                ->ignore($ignoreId)],
        ], [
            'name.unique' => 'This salary type already exists.',
        ]);
    }
}
