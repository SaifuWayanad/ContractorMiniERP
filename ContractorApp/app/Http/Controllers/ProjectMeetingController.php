<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMeeting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectMeetingController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'meeting_at' => ['required', 'date'],
            'purpose'    => ['required', 'string', 'max:500'],
        ]);

        $meeting = new ProjectMeeting($data);
        $meeting->project_id   = $project->id;
        $meeting->contractor_id = $project->contractor_id;
        $meeting->created_by   = $request->user()->id;
        $meeting->save();

        return back()->with('toast', 'Meeting logged.');
    }

    public function destroy(Project $project, ProjectMeeting $meeting): RedirectResponse
    {
        $this->authorize('update', $project);

        $meeting->delete();

        return back()->with('toast', 'Meeting removed.');
    }
}
