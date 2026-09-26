<?php

namespace App\Http\Middleware;

use App\Tenancy\Facades\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the central platform admin area (T01b, tightened by T01c).
 *
 * Platform admin criterion (grill decision 2026-09-26): an authenticated SSO
 * user with ZERO tenant memberships whose email is on the explicit
 * allowlist (config platform.admin_emails from PLATFORM_ADMIN_EMAILS).
 *
 * This closes the T01b hole where every JIT-provisioned SSO user (born
 * without a tenant) automatically qualified as platform admin — anyone in
 * the organization could have administered all tenants.
 *
 * Fail-closed on all edges:
 *  - guests are handed to the SSO redirect (auth middleware upstream does
 *    this; kept here as a defensive re-check);
 *  - non-allowlisted users — including membership-less JIT users — get 404:
 *    no evidence the area exists.
 *
 * Tenancy is deliberately never initialized on platform routes: the surface
 * manages central records in central context. This middleware must never be
 * combined with the `tenant` alias on the same route.
 */
class EnsurePlatformAdmin
{
    /**
     * Handle an incoming request.
     *
     * Any tenant context is ended first — the platform surface is central
     * context by definition, also under stateful runtimes (Octane, queue
     * workers, in-process request reuse in tests).
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        TenantContext::end();

        $user = $request->user();

        if ($user === null) {
            return redirect()->route('saml.redirect');
        }

        if (! $user->isPlatformAdmin()) {
            abort(404);
        }

        return $next($request);
    }
}
