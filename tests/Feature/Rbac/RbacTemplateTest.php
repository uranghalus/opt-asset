<?php

namespace Tests\Feature\Rbac;

use App\Enums\Permission;
use App\Models\Tenant;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The RBAC seed (grill 2026-09-28): the ten locked `domain.action`
 * permission strings exist, a global template role named `default`
 * (team_id null) carries view-only access as the JIT landing role source,
 * and every active tenant is seeded with a `default` clone plus an `Admin
 * Tenant` role holding the full current permission set.
 */
class RbacTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_seeds_the_ten_locked_permissions(): void
    {
        $this->seed(RbacSeeder::class);

        foreach (Permission::cases() as $permission) {
            $this->assertDatabaseHas('permissions', ['name' => $permission->value, 'guard_name' => 'web']);
        }

        $this->assertSame(count(Permission::cases()), DB::table('permissions')->count());
    }

    public function test_the_seeder_seeds_the_global_default_template_role_with_view_only_access(): void
    {
        $this->seed(RbacSeeder::class);

        $template = Role::query()->whereNull('tenant_id')->where('name', 'default')->first();

        $this->assertNotNull($template, 'The global template role `default` must exist.');
        $this->assertSame(
            [Permission::AssetsView->value],
            $template->permissions->pluck('name')->all(),
            'The template must be view-only: assets.view and nothing else.',
        );
    }

    public function test_the_seeder_is_idempotent(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(RbacSeeder::class);

        $this->assertSame(count(Permission::cases()), DB::table('permissions')->count());
        $this->assertSame(1, Role::query()->whereNull('tenant_id')->where('name', 'default')->count());
    }

    public function test_the_seeder_clones_the_default_role_for_every_active_tenant(): void
    {
        Tenant::factory()->count(2)->create();
        Tenant::factory()->suspended()->create();

        $this->seed(RbacSeeder::class);

        $activeTenants = Tenant::query()->where('status', 'active')->get();
        $this->assertSame(2, $activeTenants->count());

        foreach ($activeTenants as $tenant) {
            $default = Role::query()->where('name', 'default')->where('tenant_id', $tenant->getKey())->first();

            $this->assertNotNull($default, "Tenant [{$tenant->getKey()}] must have a cloned default role.");
            $this->assertSame([Permission::AssetsView->value], $default->permissions->pluck('name')->all());
        }

        $this->assertSame(
            0,
            Role::query()
                ->where('name', 'default')
                ->whereNotNull('tenant_id')
                ->whereNotIn('tenant_id', $activeTenants->pluck('id'))
                ->count(),
            'Suspended tenants get no roles.',
        );
    }

    public function test_the_seeder_seeds_the_admin_role_for_every_active_tenant(): void
    {
        Tenant::factory()->count(2)->create();

        $this->seed(RbacSeeder::class);

        foreach (Tenant::query()->where('status', 'active')->get() as $tenant) {
            $admin = Role::query()->where('name', 'Admin Tenant')->where('tenant_id', $tenant->getKey())->first();

            $this->assertNotNull($admin, "Tenant [{$tenant->getKey()}] must have an Admin Tenant role.");
            $this->assertEqualsCanonicalizing(Permission::values(), $admin->permissions->pluck('name')->all());
        }
    }

    public function test_re_running_the_seeder_realigns_the_admin_role_with_new_permissions(): void
    {
        $tenant = Tenant::factory()->create();

        $this->seed(RbacSeeder::class);

        // Simulate lag: the Admin role was created before a permission case
        // landed in the registry, so it is missing one.
        $admin = Role::query()->where('name', 'Admin Tenant')->where('tenant_id', $tenant->getKey())->first();
        $admin->revokePermissionTo(Permission::UsersManage->value);
        $this->assertNotEqualsCanonicalizing(Permission::values(), $admin->permissions->pluck('name')->all());

        // Re-running the seeder is the sanctioned sync mechanism (grill Q4).
        $this->seed(RbacSeeder::class);

        $admin = Role::query()->where('name', 'Admin Tenant')->where('tenant_id', $tenant->getKey())->first();
        $this->assertEqualsCanonicalizing(Permission::values(), $admin->permissions->pluck('name')->all());
    }
}
