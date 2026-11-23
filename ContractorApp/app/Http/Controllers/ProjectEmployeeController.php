<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectEmployeeController extends Controller
{
    public function sync(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'employee_ids'   => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
        ]);

        $project->employees()->sync($validated['employee_ids'] ?? []);

        return back()->with('toast', 'Employee assignments updated.');
    }
}
