<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Quick status change for a project from the list or detail page.
 * Marking a project completed also records today as its actual end date if none is set.
 */
class ProjectStatusController extends Controller
{
    public function __invoke(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(ProjectStatus::class)],
        ]);

        $project->status = ProjectStatus::from($validated['status']);

        if ($project->status === ProjectStatus::Completed && $project->actual_end_date === null) {
            $project->actual_end_date = today();
        }

        $project->save();

        return back()->with('toast', "\"{$project->name}\" is now {$project->status->label()}.");
    }
}
