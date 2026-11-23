<?php

namespace App\Http\Controllers;

use App\Models\Work;
use App\Models\WorkWorker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkWorkerController extends Controller
{
    public function store(Request $request, Work $work): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
        ]);

        $worker = WorkWorker::create(['work_id' => $work->id, 'name' => $validated['name']]);

        if ($request->expectsJson()) {
            return response()->json(['worker' => ['id' => $worker->id, 'name' => $worker->name]], 201);
        }

        return back()->with('toast', 'Worker added.');
    }

    public function destroy(Request $request, Work $work, WorkWorker $worker): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work);

        $worker->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('toast', 'Worker removed.');
    }
}
