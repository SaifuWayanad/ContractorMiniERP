<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out users who were deactivated (or whose contractor was deactivated) after they signed in.
 */
class EnsureAccountIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user()?->loadMissing('role', 'contractor');

        if ($user !== null && ! $user->canSignIn()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('login')->withErrors(['email' => 'Your account is inactive. Please contact your administrator.']);
        }

        return $next($request);
    }
}
