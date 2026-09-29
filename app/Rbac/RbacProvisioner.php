<?php

namespace App\Rbac;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * Clone-on-first-use role provisioning (T02 grill 2026-09-28, Q3).
 *
 * A tenant's `default` role is cloned from the global template role (team_id
 * null, seeded by RbacSeeder) on demand — idempotent, transactional, and
 * race-safe: concurrent first logins are settled by the package's composite
 * unique index (team_id, name, guard_name), with the loser re-fetching the
 * winner's row.
 *
 * Deliberately created through the query builder, NOT Role::create: the
 * package's duplicate check also matches global roles
 * (`whereNull(tenant_id) OR tenant_id = current`), so a team-scoped role
 * sharing its name with the global template would always be rejected.
 */
class RbacProvisioner
{
    /**
     * The tenant's `default` role, cloned from the global template when
     * missing.
     *
     * Side effects: writes to the spatie `roles`/`role_has_permissions`
     * tables on first call per tenant; throws when the global template is
     * missing (the RBAC seed was never run — fail closed rather than
     * masking a broken setup with a hardcoded fallback).
     */
    public function ensureDefaultRole(Tenant $tenant): Role
    {
        $existing = $this->tenantDefaultRole($tenant);

        if ($existing !== null) {
            return $existing;
        }

        $template = $this->globalTemplateRole();

        if ($template === null) {
            throw new RuntimeException('The global template role `default` is missing; run the RbacSeeder first.');
        }

        try {
            return DB::transaction(function () use ($tenant, $template): Role {
                $role = Role::query()->create([
                    'name' => RbacSeeder::DEFAULT_ROLE,
                    'guard_name' => 'web',
                    'tenant_id' => $tenant->getKey(),
                ]);

                // The template is the single source of the landing permission
                // set — new clones mirror it at clone time; later template
                // changes never propagate to copies that already exist.
                $role->syncPermissions($template->permissions->pluck('name')->all());

                return $role;
            });
        } catch (QueryException $exception) {
            // Two concurrent first logins raced: the composite unique index
            // rejected our insert, so the winner's row exists now.
            $winner = $this->tenantDefaultRole($tenant);

            if ($winner === null) {
                throw $exception;
            }

            return $winner;
        }
    }

    /**
     * The global template role (team_id null) seeded by the RbacSeeder.
     */
    protected function globalTemplateRole(): ?Role
    {
        return Role::query()
            ->where('name', RbacSeeder::DEFAULT_ROLE)
            ->whereNull('tenant_id')
            ->first();
    }

    /**
     * The tenant's own `default` role — an exact team-scoped lookup, never
     * the global template and never another tenant's clone.
     */
    protected function tenantDefaultRole(Tenant $tenant): ?Role
    {
        return Role::query()
            ->where('name', RbacSeeder::DEFAULT_ROLE)
            ->where('tenant_id', $tenant->getKey())
            ->first();
    }

    /**
     * Assign the tenant's `default` view-only role to every active
     * membership that has no role for that tenant yet (JIT landing role).
     *
     * Called from the SAML JIT path after the user logs in; idempotent —
     * memberships that already hold any role for the team (e.g. an Admin
     * Tenant assignment) are left untouched, and re-running is a no-op.
     *
     * Side effects: writes role assignments for the spatie `model_has_roles`
     * table, may clone the tenant's `default` role, and leaves the
     * permissions team context pointing at the last processed membership's
     * tenant.
     */
    public function assignDefaultRoles(User $user): void
    {
        foreach ($user->memberships()->with('tenant')->get() as $membership) {
            $tenant = $membership->tenant;

            // Suspended tenants are never resolvable (T01) — their members
            // fail closed, so no landing role is provisioned for them.
            if ($tenant === null || $tenant->status !== 'active') {
                continue;
            }

            // Package rule: after changing the team context, cached model
            // relations must be unset before any authorization check, or the
            // previous team's roles are re-served from memory.
            setPermissionsTeamId($tenant->getKey());
            $user->unsetRelation('roles')->unsetRelation('permissions');

            if ($user->roles->isNotEmpty()) {
                // The membership already holds a role for this team (e.g. an
                // Admin Tenant assignment) — it is never overwritten.
                continue;
            }

            $user->assignRole($this->ensureDefaultRole($tenant));
        }
    }
}
