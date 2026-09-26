<?php

namespace App\Http\Middleware;

use App\Tenancy\Facades\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the central platform admin area (T01b).
 *
 * Platform admins are bootstrap accounts: authenticated SSO users with no
 * tenant attachment (tenant_id = null). Fail-closed on both edges:
 *
 *  - guests are handed to the SSO redirect (auth middleware upstream does
 *    this; kept here as a defensive re-check);
 *  - tenant users get 404 — no evidence the area exists.
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

        if ($user->tenant_id !== null) {
            abort(404);
        }

        return $next($request);
    }
}
