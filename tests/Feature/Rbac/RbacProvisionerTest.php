<?php

namespace Tests\Feature\Rbac;

use App\Enums\Permission;
use App\Models\Tenant;
use App\Rbac\RbacProvisioner;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Clone-on-first-use (grill Q3): a tenant's `default` role is cloned from
 * the global template on demand — idempotent, transactional, race-safe via
 * the package's composite unique index — and later template changes never
 * propagate to copies that already exist.
 */
class RbacProvisionerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(RbacSeeder::class);
    }

    public function test_it_clones_the_global_template_into_the_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $role = app(RbacProvisioner::class)->ensureDefaultRole($tenant);

        $this->assertSame('default', $role->name);
        $this->assertSame($tenant->getKey(), $role->tenant_id);
        $this->assertSame([Permission::AssetsView->value], $role->permissions->pluck('name')->all());
    }

    public function test_it_is_idempotent_and_never_duplicates_the_tenant_role(): void
    {
        $tenant = Tenant::factory()->create();
        $provisioner = app(RbacProvisioner::class);

        $first = $provisioner->ensureDefaultRole($tenant);
        $second = $provisioner->ensureDefaultRole($tenant);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, Role::query()->where('name', 'default')->where('tenant_id', $tenant->getKey())->count());
    }

    public function test_template_changes_do_not_propagate_to_existing_copies(): void
    {
        $tenant = Tenant::factory()->create();

        app(RbacProvisioner::class)->ensureDefaultRole($tenant);

        // The template gains a permission after the tenant already cloned it.
        $template = Role::query()->whereNull('tenant_id')->where('name', 'default')->first();
        $template->syncPermissions([Permission::AssetsView->value, Permission::ReportsView->value]);

        $role = app(RbacProvisioner::class)->ensureDefaultRole($tenant);

        $this->assertSame([Permission::AssetsView->value], $role->permissions->pluck('name')->all());
    }

    public function test_tenants_get_independent_copies(): void
    {
        $first = Tenant::factory()->create();
        $second = Tenant::factory()->create();
        $provisioner = app(RbacProvisioner::class);

        $roleInFirst = $provisioner->ensureDefaultRole($first);
        $roleInSecond = $provisioner->ensureDefaultRole($second);

        $this->assertNotSame($roleInFirst->getKey(), $roleInSecond->getKey());
        $this->assertSame(2, Role::query()->where('name', 'default')->whereNotNull('tenant_id')->count());
    }
}
