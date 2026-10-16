<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * The signed-in contractor's own company profile. The contractor always comes from the signed-in user.
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $contractor = $request->user()->contractor;
        $this->authorize('viewProfile', $contractor);

        return view('profile.show', [
            'contractor' => $contractor->loadCount(['users', 'clients', 'projects']),
        ]);
    }

    public function edit(Request $request): View
    {
        $contractor = $request->user()->contractor;
        $this->authorize('updateProfile', $contractor);

        return view('profile.edit', ['contractor' => $contractor]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $contractor = $request->user()->contractor;
        $contractor->fill($request->safe()->except(['logo', 'remove_logo']));

        if ($request->boolean('remove_logo') && $contractor->logo_path !== null) {
            Storage::disk('public')->delete($contractor->logo_path);
            $contractor->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            if ($contractor->logo_path !== null) {
                Storage::disk('public')->delete($contractor->logo_path);
            }
            $contractor->logo_path = $request->file('logo')->store("logos/{$contractor->id}", 'public');
        }

        $contractor->save();

        return to_route('profile.show')->with('toast', 'Profile updated.');
    }
}
