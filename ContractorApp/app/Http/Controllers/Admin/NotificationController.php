<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\PushService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        Gate::authorize('notifications.view');

        return view('admin.notifications.index', [
            'contractors' => Contractor::orderBy('company_name')->pluck('company_name', 'id'),
            'deviceCount' => User::whereNotNull('push_token')->count(),
            'devices' => User::with('contractor:id,company_name')->whereNotNull('push_token')->latest('updated_at')->get(['id', 'name', 'email', 'contractor_id', 'push_token', 'updated_at']),
        ]);
    }

    public function send(Request $request, ActivityLogger $activity, PushService $push): RedirectResponse
    {
        Gate::authorize('notifications.send');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:500'],
            'contractor_id' => ['nullable', 'integer', 'exists:contractors,id'],
        ]);

        $tokens = User::whereNotNull('push_token')
            ->when($validated['contractor_id'] ?? null, fn ($query, $id) => $query->where('contractor_id', $id))
            ->pluck('push_token')
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            return back()->withInput()->with('toast', 'No registered devices to notify.');
        }

        $sent = 0;
        $errors = [];

        foreach ($tokens as $token) {
            $result = $push->send($token, $validated['title'], $validated['body']);
            $result['ok'] ? $sent++ : $errors[] = $result['error'];
        }

        $activity->record('sent', "{$request->user()->name} sent push notification \"{$validated['title']}\" to {$sent} of {$tokens->count()} device(s)");

        $message = "Notification sent to {$sent} of {$tokens->count()} device(s).";
        if ($errors) {
            $message .= ' Error: '.implode(', ', array_unique($errors));
        }

        return to_route('admin.notifications.index')->with('toast', $message);
    }

    public function removeDevice(User $user): RedirectResponse
    {
        Gate::authorize('notifications.send');

        $user->forceFill(['push_token' => null])->save();

        return to_route('admin.notifications.index')->with('toast', "Device removed for {$user->name}.");
    }

    public function clearDevices(): RedirectResponse
    {
        Gate::authorize('notifications.send');

        $count = User::whereNotNull('push_token')->update(['push_token' => null]);

        return to_route('admin.notifications.index')->with('toast', "Cleared {$count} registered device(s).");
    }
}
