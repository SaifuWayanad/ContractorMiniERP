<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserStatusController extends Controller
{
    public function __invoke(Request $request, User $user, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate(['status' => ['required', Rule::enum(UserStatus::class)]]);
        $status = UserStatus::from($validated['status']);

        if ($user->is($request->user()) && $status !== UserStatus::Active) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->status = $status;
        $user->save();

        $activity->record('status_changed', "{$request->user()->name} set user \"{$user->name}\" to {$status->label()}", $user, contractorId: $user->contractor_id);

        return back()->with('toast', "\"{$user->name}\" is now {$status->label()}.");
    }
}
