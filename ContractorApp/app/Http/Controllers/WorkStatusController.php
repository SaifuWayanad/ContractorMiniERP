<?php

namespace App\Http\Controllers;

use App\Enums\WorkStatus;
use App\Models\Work;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Quick status change for a work item (e.g. "Mark completed").
 */
class WorkStatusController extends Controller
{
    public function __invoke(Request $request, Work $work): RedirectResponse
    {
        $this->authorize('update', $work);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(WorkStatus::class)],
        ]);

        $work->status = WorkStatus::from($validated['status']);
        $work->save();

        $message = $work->status === WorkStatus::Completed
            ? "\"{$work->title}\" marked as completed."
            : "\"{$work->title}\" is now {$work->status->label()}.";

        return back()->with('toast', $message);
    }
}
