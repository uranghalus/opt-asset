<?php

namespace App\Tenancy;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\TenantSwitch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Context;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;

/**
 * Single authority for the acting tenant context.
 *
 * A session-based user acts inside a tenant resolved from their
 * memberships: session pointer → default membership → fail. The session
 * value self-heals: pointing at a tenant without an active membership
 * falls back to the default membership, never to an error. Suspended
 * tenants are never resolvable, fail-closed, in either place.
 *
 * Jobs and Artisan commands bypass memberships and bind explicitly via
 * initialize($tenant) / $tenant->run() — the same primitives the switcher
 * resolves to.
 */
class TenantContext
{
    /**
     * Session key holding the active tenant id.
     */
    public const SESSION_KEY = 'tenant.active_id';

    /**
     * Initialize tenancy for an explicit tenant and record the context.
     *
     * The non-HTTP path (queue jobs, Artisan commands, tests).
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
     * Resolve the acting tenant from the user's memberships and initialize
     * it: session membership → default membership → false (fail-closed,
     * no context on failure).
     */
    public function initializeFromUser(User $user): bool
    {
        $tenant = $this->resolveFor($user);

        if ($tenant === null) {
            return false;
        }

        $this->initialize($tenant);

        return true;
    }

    /**
     * Switch the user's active tenant, validating an active membership on
     * the target (or superadmin access), and writing an audit row.
     *
     * @return bool true when switched; false when the user may not act in
     *              the target (no state is changed)
     */
    public function switch(User $user, string $tenantId): bool
    {
        // Superadmin: any active tenant is enterable, membership or not.
        $target = $user->isPlatformAdmin()
            ? $this->activeTenant($tenantId)
            : $this->activeMembershipTenant($user, $tenantId);

        if ($target === null) {
            return false;
        }

        // Audit truthfulness: record the tenant the user was EFFECTIVELY
        // acting in (resolved: session pointer or default membership), not
        // merely the raw session pointer — a first-time switcher without a
        // pointer still acts inside their default tenant.
        $fromId = $this->resolveFor($user)?->getKey();

        session([self::SESSION_KEY => $target->getKey()]);

        TenantSwitch::query()->create([
            'user_id' => $user->id,
            'from_tenant_id' => $fromId,
            'to_tenant_id' => $target->getKey(),
        ]);

        return true;
    }

    /**
     * Resolve the tenant for the user without initializing anything.
     *
     * Superadmins may act in ANY active tenant (session pointer honored
     * without a membership); regular users are bound to memberships.
     */
    public function resolveFor(User $user): ?Tenant
    {
        $sessionTenantId = session(self::SESSION_KEY);

        if (is_string($sessionTenantId) && $sessionTenantId !== '') {
            $tenant = $user->isPlatformAdmin()
                ? $this->activeTenant($sessionTenantId)
                : $this->activeMembershipTenant($user, $sessionTenantId);

            if ($tenant !== null) {
                return $tenant;
            }
            // Stale pointer: self-heal by falling through below.
        }

        if ($user->isPlatformAdmin()) {
            // No pointer: fall back to a membership default, else the
            // first active tenant — a superadmin is never context-less.
            return $this->defaultMembershipTenant($user)
                ?? Tenant::query()->where('status', 'active')->orderBy('name')->first();
        }

        return $this->defaultMembershipTenant($user);
    }

    /**
     * An active tenant by id, regardless of memberships (superadmin path).
     */
    protected function activeTenant(string $tenantId): ?Tenant
    {
        return Tenant::query()
            ->whereKey($tenantId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * The user's active membership tenant for the given tenant id, or null
     * when there is no membership or the tenant is not active.
     */
    protected function activeMembershipTenant(User $user, string $tenantId): ?Tenant
    {
        return self::membershipQuery($user)
            ->where('tenant_memberships.tenant_id', $tenantId)
            ->first()?->tenant;
    }

    /**
     * The user's default membership tenant, or null without one / when the
     * tenant is not active.
     */
    protected function defaultMembershipTenant(User $user): ?Tenant
    {
        return self::membershipQuery($user)
            ->where('tenant_memberships.is_default', true)
            ->first()?->tenant;
    }

    /**
     * Base query: the user's memberships joined to active tenants.
     *
     * @return Builder<TenantMembership>
     */
    protected static function membershipQuery(User $user): Builder
    {
        return $user->memberships()
            ->getQuery()
            ->select('tenant_memberships.*')
            ->join('tenants', 'tenants.id', '=', 'tenant_memberships.tenant_id')
            ->where('tenants.status', 'active')
            ->with('tenant');
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
