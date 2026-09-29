<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Models\Tenant;
use App\Rbac\RbacProvisioner;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * RBAC seed (T02 grill 2026-09-28): seeds the locked permission strings, the
 * global template role named `default` (team_id null) that carries the
 * view-only JIT landing permissions, and — per active tenant — the `default`
 * clone plus the `Admin Tenant` role holding the full current permission set.
 *
 * Safe to re-run at any time — every step is idempotent (firstOrCreate) —
 * and re-running IS the sanctioned sync mechanism (grill Q4): when new
 * permission cases are added to the registry in later tickets, re-running
 * this seeder realigns the template and every tenant's Admin role with the
 * current set.
 */
class RbacSeeder extends Seeder
{
    /**
     * The name of the global template role and of each tenant's cloned
     * landing role (convention, no is_default column — grill Q3).
     */
    public const DEFAULT_ROLE = 'default';

    /**
     * The per-tenant administration role (grill Q4): holds every tenant
     * permission but is not a superadmin.
     */
    public const ADMIN_ROLE = 'Admin Tenant';

    /**
     * Seed permissions, the global template role, and per-tenant roles.
     *
     * Side effects: writes to the spatie `permissions`/`roles`/
     * `role_has_permissions` tables and flushes the permission cache.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::query()->firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        $template = Role::query()->firstOrCreate([
            'name' => self::DEFAULT_ROLE,
            'guard_name' => 'web',
            'tenant_id' => null,
        ]);

        $template->syncPermissions([Permission::AssetsView->value]);

        // Per-tenant seeding (grill Q4): every active tenant gets its own
        // `default` clone and an `Admin Tenant` role. Suspended and inactive
        // tenants get nothing — fail-closed, consistent with the JIT path.
        // Role ASSIGNMENT still happens only at login, never retroactively.
        $provisioner = app(RbacProvisioner::class);

        foreach (Tenant::where('status', 'active')->cursor() as $tenant) {
            $provisioner->ensureDefaultRole($tenant);

            $admin = Role::query()->firstOrCreate([
                'name' => self::ADMIN_ROLE,
                'guard_name' => 'web',
                'tenant_id' => $tenant->getKey(),
            ]);

            $admin->syncPermissions(Permission::values());
        }
    }
}
