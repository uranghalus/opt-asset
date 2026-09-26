<?php

namespace App\Http\Middleware;

use App\Tenancy\Facades\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve the acting tenant from the authenticated user and initialize
 * tenancy for the rest of the request.
 *
 * Fail-closed semantics:
 *  - guest → no context (routes are already auth-protected);
 *  - authenticated user without a tenant (bootstrap account) or on a
 *    suspended/inactive tenant → 403, never a context.
 */
class InitializeTenantContext
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            $resolved = TenantContext::initializeFromUser($user);

            if (! $resolved) {
                abort(403, 'Your account is not attached to an active organization.');
            }
        }

        return $next($request);
    }
}
