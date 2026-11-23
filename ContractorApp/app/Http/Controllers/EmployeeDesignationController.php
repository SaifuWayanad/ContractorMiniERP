<?php

namespace App\Http\Controllers;

use App\Models\EmployeeDesignation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeDesignationController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', EmployeeDesignation::class);

        return view('employee-designations.index', [
            'designations' => EmployeeDesignation::query()->orderBy('name')->paginate($this->perPage()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', EmployeeDesignation::class);

        $designation = $this->createFrom($request);

        return to_route('employee-designations.index')->with('toast', "Designation \"{$designation->name}\" added.");
    }

    public function quickStore(Request $request): JsonResponse
    {
        $this->authorize('create', EmployeeDesignation::class);

        $designation = $this->createFrom($request);

        return response()->json(['id' => $designation->id, 'name' => $designation->name], 201);
    }

    public function update(Request $request, EmployeeDesignation $employeeDesignation): RedirectResponse
    {
        $this->authorize('update', $employeeDesignation);

        $employeeDesignation->update($this->validatedName($request, $employeeDesignation->contractor_id, $employeeDesignation->id));

        return to_route('employee-designations.index')->with('toast', "Designation renamed to \"{$employeeDesignation->name}\".");
    }

    public function destroy(EmployeeDesignation $employeeDesignation): RedirectResponse
    {
        $this->authorize('delete', $employeeDesignation);

        $name = $employeeDesignation->name;
        $employeeDesignation->delete();

        return to_route('employee-designations.index')->with('toast', "Designation \"{$name}\" deleted. Existing employees keep their value.");
    }

    private function createFrom(Request $request): EmployeeDesignation
    {
        $contractorId = $request->user()->contractor_id;
        $data = $this->validatedName($request, $contractorId);

        $designation = new EmployeeDesignation($data);
        $designation->contractor_id = $contractorId;
        $designation->save();

        return $designation;
    }

    /**
     * @return array{name: string}
     */
    private function validatedName(Request $request, ?int $contractorId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('employee_designations', 'name')
                ->where('contractor_id', $contractorId)
                ->ignore($ignoreId)],
        ], [
            'name.unique' => 'This designation already exists.',
        ]);
    }
}
