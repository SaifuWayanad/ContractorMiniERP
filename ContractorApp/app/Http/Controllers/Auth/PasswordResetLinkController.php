<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a reset link. The response is the same whether or not the email exists, so the form
     * cannot be used to discover which addresses have accounts.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
        }

        return back()->with('status', 'If an account exists for that email, a password reset link has been sent.');
    }
}
