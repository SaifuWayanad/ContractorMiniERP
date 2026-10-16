<?php

namespace App\Http\Controllers\Mobile;

use App\Enums\WorkPriority;
use App\Enums\WorkStatus;
use App\Http\Controllers\Controller;
use App\Models\Work;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileWorkController extends Controller
{
    public function index(): JsonResponse
    {
        $works = Work::query()
            ->with('project:id,name')
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->latest()
            ->get()
            ->map(fn (Work $w) => [
                'id'         => $w->id,
                'title'      => $w->title,
                'status'     => $w->status->value,
                'priority'   => $w->priority->value,
                'due_date'   => $w->due_date?->toDateString(),
                'project'    => $w->project?->name,
                'project_id' => $w->project_id,
            ]);

        return response()->json($works);
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
            'project_id'  => ['required', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'title'       => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority'    => ['nullable', Rule::enum(WorkPriority::class)],
            'status'      => ['nullable', Rule::enum(WorkStatus::class)],
            'start_date'  => ['nullable', 'date'],
            'due_date'    => ['nullable', 'date'],
        ]);

        $data['status'] ??= WorkStatus::Pending->value;
        $data['priority'] ??= WorkPriority::Medium->value;

        $work = new Work($data);
        $work->project_id = $data['project_id'];
        $work->contractor_id = $contractorId;
        $work->save();

        return response()->json(['id' => $work->id, 'message' => 'Work created.'], 201);
    }

    public function update(Request $request, Work $work): JsonResponse
    {
        $data = $request->validate([
            'title'       => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority'    => ['nullable', Rule::enum(WorkPriority::class)],
            'status'      => ['nullable', Rule::enum(WorkStatus::class)],
            'due_date'    => ['nullable', 'date'],
        ]);

        $work->update($data);

        return response()->json(['message' => 'Work updated.']);
    }

    public function destroy(Work $work): JsonResponse
    {
        $work->delete();
        return response()->json(['message' => 'Work deleted.']);
    }
}
