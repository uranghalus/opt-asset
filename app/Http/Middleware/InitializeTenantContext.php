<?php

namespace App\Http\Middleware;

use App\Tenancy\Facades\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve the acting tenant from the authenticated user's memberships and
 * initialize tenancy for the rest of the request.
 *
 * Fail-closed semantics:
 *  - guest → no context (routes are already auth-protected);
 *  - platform admin (env fallback grant or superadmin flag) → allowed
 *    through with a resolved context when any active tenant exists; with
 *    zero tenants they land on the platform area to create the first one;
 *  - user without an active membership (or only on suspended tenants) → 403.
 */
class InitializeTenantContext
{
    /**
     * Handle an incoming request.
     *
     * Any stale tenant context is ended first: in-process request reuse
     * (tests, Octane, queue workers) must never inherit the previous
     * request's tenant, and the redirect/403 paths must leave NO context.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        TenantContext::end();

        $user = $request->user();

        if ($user !== null) {
            $resolved = TenantContext::initializeFromUser($user);

            if (! $resolved) {
                // Platform admins without a resolvable tenant (e.g. zero
                // tenants on a fresh install) belong in their area — route
                // them there rather than a confusing 403.
                if ($user->isPlatformAdmin()) {
                    return redirect()->route('platform.business-units.index');
                }

                // Any other failure (no membership, suspended tenant) is a
                // genuine access problem — fail closed.
                abort(403, 'Your account is not attached to an active business unit.');
            }
        }

        return $next($request);
    }
}
