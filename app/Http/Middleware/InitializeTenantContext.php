<?php

namespace App\Http\Middleware;

use App\Tenancy\Facades\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve the acting tenant from the authenticated user's memberships and
 * initialize tenancy for the rest of the request (T01c multi-membership).
 *
 * Fail-closed semantics:
 *  - guest → no context (routes are already auth-protected);
 *  - platform admin (zero memberships + allowlisted email) → redirect to
 *    the platform area; these accounts must never reach tenant-scoped routes;
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
                // Platform admins have no tenant membership by design —
                // route them to their area rather than a confusing 403.
                if ($user->isPlatformAdmin()) {
                    return redirect()->route('platform.tenants.index');
                }

                // Any other failure (no membership, suspended tenant) is a
                // genuine access problem — fail closed.
                abort(403, 'Your account is not attached to an active organization.');
            }
        }

        return $next($request);
    }
}
