<?php

namespace App\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Context;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;

/**
 * Single authority for the acting tenant context.
 *
 * Owns the lifecycle of stancl's Tenancy state (initialize / end) and mirrors
 * it into Laravel's Context so queue jobs spawned mid-request carry the
 * acting tenant without per-job boilerplate (T07/T08 will consume this in
 * their queued label generation).
 *
 * Every path to a tenant context goes through here: the HTTP middleware
 * (authenticated SSO user), queued jobs, and Artisan commands.
 */
class TenantContext
{
    /**
     * Initialize tenancy for the given tenant and record the context.
     *
     * Re-initializing with the same tenant is a no-op (stancl short-circuits
     * an identical tenant key); switching tenants ends the old context.
     *
     * Side effects: sets stancl Tenancy state and the `tenant.id` /
     * `tenant.code` context log metadata.
     */
    public function initialize(Tenant $tenant): void
    {
        tenancy()->initialize($tenant);

        Context::add('tenant.id', (string) $tenant->getKey());
        Context::addHidden('tenant.code', $tenant->code);
    }

    /**
     * Resolve the tenant from an SSO-provisioned user and initialize it.
     *
     * Returns false when the user has no usable tenant (bootstrap account)
     * or the tenant is not `active` — fail-closed: no context is set.
     */
    public function initializeFromUser(User $user): bool
    {
        if ($user->tenant_id === null || $user->tenant === null) {
            return false;
        }

        if ($user->tenant->status !== 'active') {
            return false;
        }

        $this->initialize($user->tenant);

        return true;
    }

    /**
     * Whether a tenant context is currently acting.
     */
    public function check(): bool
    {
        return tenancy()->initialized && tenancy()->tenant instanceof TenantContract;
    }

    /**
     * The acting tenant key, or null when no context is initialized.
     */
    public function id(): ?string
    {
        $tenant = tenancy()->initialized ? tenancy()->tenant : null;

        return $tenant instanceof TenantContract
            ? (string) $tenant->getTenantKey()
            : null;
    }

    /**
     * End the acting tenant context.
     */
    public function end(): void
    {
        tenancy()->end();

        Context::forget('tenant.id');
        Context::forget('tenant.code');
    }
}
