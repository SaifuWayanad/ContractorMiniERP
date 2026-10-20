<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\WorkType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileWorkTypeController extends Controller
{
    /**
     * Contractor users see their own types. Admins pass ?contractor_id= to pick one contractor's list.
     */
    public function index(Request $request): JsonResponse
    {
        $query = WorkType::query()->orderBy('name');

        if (! $request->user()->contractor_id && $request->filled('contractor_id')) {
            $query->where('contractor_id', (int) $request->input('contractor_id'));
        }

        return response()->json($query->get(['id', 'name'])->map(fn (WorkType $t) => [
            'id' => $t->id,
            'name' => $t->name,
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $contractorId = $request->user()->contractor_id
            ?? (int) $request->input('contractor_id')
            ?: null;

        if ($contractorId === null) {
            return response()->json(['message' => 'contractor_id is required for admin users.'], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('work_types', 'name')->where('contractor_id', $contractorId)],
        ], [
            'name.unique' => 'This type of work already exists.',
        ]);

        $workType = new WorkType($data);
        $workType->contractor_id = $contractorId;
        $workType->save();

        return response()->json(['id' => $workType->id, 'name' => $workType->name, 'message' => 'Work type added.'], 201);
    }

    public function update(Request $request, WorkType $workType): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('work_types', 'name')
                ->where('contractor_id', $workType->contractor_id)
                ->ignore($workType->id)],
        ], [
            'name.unique' => 'This type of work already exists.',
        ]);

        $workType->update($data);

        return response()->json(['id' => $workType->id, 'name' => $workType->name, 'message' => 'Work type updated.']);
    }

    public function destroy(WorkType $workType): JsonResponse
    {
        $workType->delete();

        return response()->json(['message' => 'Work type deleted.']);
    }
}
