<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        Gate::authorize('settings.view');

        return view('admin.settings.edit', [
            'settings' => [
                'platform_name' => Setting::get('platform_name', config('app.name')),
                'support_email' => Setting::get('support_email'),
                'default_country' => Setting::get('default_country', 'India'),
                'records_per_page' => Setting::get('records_per_page', '25'),
            ],
        ]);
    }

    public function update(Request $request, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('settings.edit');

        $validated = $request->validate([
            'platform_name' => ['required', 'string', 'max:60'],
            'support_email' => ['nullable', 'email', 'max:150'],
            'default_country' => ['required', 'string', 'max:80'],
            'records_per_page' => ['required', 'integer', 'in:10,25,50,100'],
        ]);

        Setting::putMany(array_map(fn ($value): ?string => $value === null ? null : (string) $value, $validated));
        $activity->record('updated', "{$request->user()->name} updated the application settings");

        return back()->with('toast', 'Settings saved.');
    }
}
