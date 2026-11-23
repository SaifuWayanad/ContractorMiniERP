<?php

namespace App\Http\Controllers;

use App\Models\EmployeeStatusOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeStatusOptionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', EmployeeStatusOption::class);

        return view('employee-statuses.index', [
            'statuses' => EmployeeStatusOption::query()->orderBy('name')->paginate($this->perPage()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', EmployeeStatusOption::class);

        $status = $this->createFrom($request);

        return to_route('employee-statuses.index')->with('toast', "Status \"{$status->name}\" added.");
    }

    public function quickStore(Request $request): JsonResponse
    {
        $this->authorize('create', EmployeeStatusOption::class);

        $status = $this->createFrom($request);

        return response()->json(['id' => $status->id, 'name' => $status->name], 201);
    }

    public function update(Request $request, EmployeeStatusOption $employeeStatusOption): RedirectResponse
    {
        $this->authorize('update', $employeeStatusOption);

        $employeeStatusOption->update($this->validatedName($request, $employeeStatusOption->contractor_id, $employeeStatusOption->id));

        return to_route('employee-statuses.index')->with('toast', "Status renamed to \"{$employeeStatusOption->name}\".");
    }

    public function destroy(EmployeeStatusOption $employeeStatusOption): RedirectResponse
    {
        $this->authorize('delete', $employeeStatusOption);

        $name = $employeeStatusOption->name;
        $employeeStatusOption->delete();

        return to_route('employee-statuses.index')->with('toast', "Status \"{$name}\" deleted. Existing employees keep their value.");
    }

    private function createFrom(Request $request): EmployeeStatusOption
    {
        $contractorId = $request->user()->contractor_id;
        $data = $this->validatedName($request, $contractorId);

        $status = new EmployeeStatusOption($data);
        $status->contractor_id = $contractorId;
        $status->save();

        return $status;
    }

    /** @return array{name: string} */
    private function validatedName(Request $request, ?int $contractorId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('employee_statuses', 'name')
                ->where('contractor_id', $contractorId)
                ->ignore($ignoreId)],
        ], [
            'name.unique' => 'This status already exists.',
        ]);
    }
}
