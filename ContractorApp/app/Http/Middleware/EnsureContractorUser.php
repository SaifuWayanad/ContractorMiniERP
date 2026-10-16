<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts the contractor portal to users who belong to a contractor.
 */
class EnsureContractorUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isContractorUser() === true, Response::HTTP_FORBIDDEN, 'This area is for contractor accounts.');

        return $next($request);
    }
}
