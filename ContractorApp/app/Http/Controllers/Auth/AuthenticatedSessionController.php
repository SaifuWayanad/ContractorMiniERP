<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, ActivityLogger $activity): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user()->load('role', 'contractor');

        if (! $user->canSignIn()) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'username' => 'Your account is inactive. Please contact your administrator.',
            ]);
        }

        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->saveQuietly();
        $activity->record('login', "{$user->name} signed in", user: $user);

        return redirect()->intended($user->homeRoute());
    }

    public function destroy(Request $request, ActivityLogger $activity): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            $activity->record('logout', "{$user->name} signed out", user: $user);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login')->with('status', 'You have been signed out.');
    }
}
