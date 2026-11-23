<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeePortalSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $this->authorize('updateProfile', $request->user()->contractor);

        $contractor = $request->user()->contractor;

        return view('employee-portal-settings.edit', [
            'contractor'       => $contractor,
            'allSections'      => Contractor::PORTAL_SECTIONS,
            'enabledSections'  => $contractor->enabledPortalSections(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('updateProfile', $request->user()->contractor);

        $contractor = $request->user()->contractor;

        $selected = array_keys(array_filter(
            $request->input('sections', []),
            fn ($v) => $v === '1'
        ));

        // Dashboard is always included
        if (! in_array('dashboard', $selected)) {
            array_unshift($selected, 'dashboard');
        }

        // Keep only valid slugs
        $valid = array_keys(Contractor::PORTAL_SECTIONS);
        $selected = array_values(array_filter($selected, fn ($s) => in_array($s, $valid)));

        $contractor->employee_portal_sections = $selected;
        $contractor->save();

        return back()->with('toast', 'Employee portal settings saved.');
    }
}
