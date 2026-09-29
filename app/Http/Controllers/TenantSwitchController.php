<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Switch the acting tenant (business unit) for the authenticated user.
 *
 * Validates an active membership on the target tenant (or superadmin
 * access), stores the session pointer, and writes the audit row — all
 * inside TenantContext::switch(). Cross-tenant attempts without access are
 * rejected 403 before any state changes.
 */
class TenantSwitchController extends Controller
{
    /**
     * Handle the switch request.
     *
     * Side effects: session `tenant.active_id` pointer, a `tenant_switches`
     * audit row, and a toast confirming the new acting business unit.
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

        abort_unless($switched, 403, 'No active membership on that business unit.');

        $tenantName = Tenant::query()->whereKey($validated['tenant_id'])->value('name');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Sekarang aktif di :tenant.', [
                'tenant' => $tenantName !== null ? (string) $tenantName : $validated['tenant_id'],
            ]),
        ]);

        return redirect()->route('dashboard');
    }
}
