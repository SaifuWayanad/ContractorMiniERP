<?php

namespace App\Http\Controllers;

use App\Models\WorkType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The contractor's types of work (the dropdown on the project form).
 * Projects store the name, so renaming or deleting a type never changes existing projects.
 */
class WorkTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', WorkType::class);

        return view('work-types.index', [
            'workTypes' => WorkType::query()->orderBy('name')->paginate($this->perPage()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', WorkType::class);

        $workType = $this->createFrom($request);

        return to_route('work-types.index')->with('toast', "Type of work \"{$workType->name}\" added.");
    }

    public function quickStore(Request $request): JsonResponse
    {
        $this->authorize('create', WorkType::class);

        $workType = $this->createFrom($request);

        return response()->json(['id' => $workType->id, 'name' => $workType->name], 201);
    }

    public function update(Request $request, WorkType $workType): RedirectResponse
    {
        $this->authorize('update', $workType);

        $workType->update($this->validatedName($request, $workType->contractor_id, $workType->id));

        return to_route('work-types.index')->with('toast', "Type of work renamed to \"{$workType->name}\".");
    }

    public function destroy(WorkType $workType): RedirectResponse
    {
        $this->authorize('delete', $workType);

        $name = $workType->name;
        $workType->delete();

        return to_route('work-types.index')->with('toast', "Type of work \"{$name}\" deleted. Existing projects keep their value.");
    }

    private function createFrom(Request $request): WorkType
    {
        $contractorId = $request->user()->contractor_id;
        $data = $this->validatedName($request, $contractorId);

        $workType = new WorkType($data);
        $workType->contractor_id = $contractorId;
        $workType->save();

        return $workType;
    }

    /**
     * @return array{name: string}
     */
    private function validatedName(Request $request, ?int $contractorId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('work_types', 'name')
                ->where('contractor_id', $contractorId)
                ->ignore($ignoreId)],
        ], [
            'name.unique' => 'This type of work already exists.',
        ]);
    }
}
