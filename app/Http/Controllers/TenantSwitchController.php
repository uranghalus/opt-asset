<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Switch the acting tenant for the authenticated user (T01c).
 *
 * Validates an active membership on the target tenant, stores the session
 * pointer, and writes the audit row — all inside TenantContext::switch().
 * Cross-tenant attempts without membership are rejected 403 before any
 * state changes.
 */
class TenantSwitchController extends Controller
{
    /**
     * Handle the switch request.
     *
     * Side effects: session `tenant.active_id` pointer, a `tenant_switches`
     * audit row, and a toast confirming the new acting tenant.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string', 'exists:tenants,id'],
        ]);

        $switched = app(TenantContext::class)->switch(
            $request->user(),
            $validated['tenant_id'],
        );

        abort_unless($switched, 403, 'No active membership on that tenant.');

        $tenantName = Tenant::query()->whereKey($validated['tenant_id'])->value('name');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Now acting in :tenant.', [
                'tenant' => $tenantName !== null ? (string) $tenantName : $validated['tenant_id'],
            ]),
        ]);

        return redirect()->route('dashboard');
    }
}
