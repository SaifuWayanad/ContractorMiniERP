<?php

namespace App\Http\Controllers;

use App\Models\Work;
use App\Models\WorkDailyWorker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkDailyWorkerController extends Controller
{
    public function store(Request $request, Work $work): RedirectResponse
    {
        $this->authorize('update', $work);

        $validated = $request->validate([
            'date'          => ['required', 'date'],
            'morning_count' => ['required', 'integer', 'min:0', 'max:9999'],
            'evening_count' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ]);

        WorkDailyWorker::updateOrCreate(
            ['work_id' => $work->id, 'date' => $validated['date']],
            [
                'contractor_id' => $request->user()->contractor_id,
                'morning_count' => $validated['morning_count'],
                'evening_count' => $validated['evening_count'] ?? 0,
                'notes'         => $validated['notes'] ?? null,
            ]
        );

        return back()->with('toast', 'Site worker count saved.');
    }

    public function destroy(Work $work, WorkDailyWorker $worker): RedirectResponse
    {
        $this->authorize('update', $work);

        $worker->delete();

        return back()->with('toast', 'Record deleted.');
    }
}
