<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts the admin portal to internal (non-contractor) admin users.
 */
class EnsureAdminUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdmin() === true, Response::HTTP_FORBIDDEN, 'This area is for administrators.');

        return $next($request);
    }
}
