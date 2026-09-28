<?php

namespace Tests\Feature\Rbac;

use App\Enums\Permission;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The team-context lifecycle (grill Q1/Q2): TenantContext is the single
 * authority for spatie's permissions team id — initialize binds it to the
 * tenant, end resets it, and initializeFromUser unsets cached relations so
 * a reused in-process user instance never resolves another team's roles.
 */
class RbacTenantContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(RbacSeeder::class);
    }

    public function test_initialize_binds_the_permissions_team_id_to_the_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        app(TenantContext::class)->initialize($tenant);

        $this->assertSame($tenant->getKey(), getPermissionsTeamId());

        app(TenantContext::class)->end();
    }

    public function test_end_resets_the_permissions_team_id(): void
    {
        $tenant = Tenant::factory()->create();

        app(TenantContext::class)->initialize($tenant);
        app(TenantContext::class)->end();

        $this->assertNull(getPermissionsTeamId());
    }

    public function test_initialize_from_user_unsets_cached_relations_so_roles_resolve_per_team(): void
    {
        $first = Tenant::factory()->create();
        $second = Tenant::factory()->create();
        $user = User::factory()->create();

        TenantMembership::query()->create(['user_id' => $user->id, 'tenant_id' => $first->getKey(), 'is_default' => true]);
        TenantMembership::query()->create(['user_id' => $user->id, 'tenant_id' => $second->getKey(), 'is_default' => false]);

        // Seed after the tenants exist so both have their roles (grill Q4).
        $this->seed(RbacSeeder::class);

        $adminInFirst = Role::query()->where('name', RbacSeeder::ADMIN_ROLE)->where('tenant_id', $first->getKey())->firstOrFail();

        setPermissionsTeamId($first->getKey());
        $user->assignRole($adminInFirst);

        // The same user instance is reused (in-process request reuse) while
        // the acting tenant moves to the second membership.
        session([TenantContext::SESSION_KEY => $second->getKey()]);

        $resolved = app(TenantContext::class)->initializeFromUser($user);

        $this->assertTrue($resolved);
        $this->assertSame($second->getKey(), getPermissionsTeamId());
        $this->assertFalse(
            $user->can(Permission::ClassificationsManage->value),
            'Cached team-first roles must not resolve inside the second tenant.',
        );
    }

    public function test_the_switch_flow_resolves_permissions_for_the_new_team(): void
    {
        $first = Tenant::factory()->create();
        $second = Tenant::factory()->create();
        $user = User::factory()->create();

        TenantMembership::query()->create(['user_id' => $user->id, 'tenant_id' => $first->getKey(), 'is_default' => true]);
        TenantMembership::query()->create(['user_id' => $user->id, 'tenant_id' => $second->getKey(), 'is_default' => false]);

        // Seed after the tenants exist so both have their roles (grill Q4).
        $this->seed(RbacSeeder::class);

        $adminInFirst = Role::query()->where('name', RbacSeeder::ADMIN_ROLE)->where('tenant_id', $first->getKey())->firstOrFail();

        setPermissionsTeamId($first->getKey());
        $user->assignRole($adminInFirst);

        $context = app(TenantContext::class);

        $this->assertTrue($context->switch($user, $second->getKey()));

        $resolved = $context->initializeFromUser($user);

        $this->assertTrue($resolved);
        $this->assertSame($second->getKey(), getPermissionsTeamId());
        $this->assertFalse($user->can(Permission::ClassificationsManage->value));
        $this->assertFalse($user->can(Permission::AssetsView->value), 'The second membership has no role yet — fail closed.');
    }
}
