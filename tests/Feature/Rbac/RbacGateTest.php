<?php

namespace Tests\Feature\Rbac;

use App\Enums\Permission;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Enforcement (grill Q5): permissions resolve per team through the Gate,
 * a superadmin bypasses everything via Gate::before (true/null, never
 * false), a roleless user is blocked from everything (fail closed), and the
 * package's `permission:` middleware blocks an unauthorized action.
 */
class RbacGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(RbacSeeder::class);

        // A probe route exercising the package's permission middleware — the
        // production middleware chain (Slice 6) wires the team context; here
        // the tests set it exactly as that middleware will.
        Route::middleware(['auth', 'permission:'.Permission::AssetsCreate->value])
            ->get('/_rbac-probe', fn () => response('ok'));
    }

    /**
     * A tenant user holding the named role (seeded fresh per tenant).
     *
     * @param  Tenant  $tenant  the tenant whose roles are ensured and assigned
     * @param  string  $roleName  the seeded role name ('default' or 'Admin Tenant')
     * @return User the created user with the role assigned for that team
     */
    protected function userWithRole(Tenant $tenant, string $roleName): User
    {
        // The seeder is the sanctioned per-tenant seeding mechanism — re-run
        // it so the fresh tenant has its `default` clone and Admin role.
        $this->seed(RbacSeeder::class);

        $user = User::factory()->forTenant($tenant)->create();

        $role = Role::query()->where('name', $roleName)->where('tenant_id', $tenant->getKey())->firstOrFail();

        setPermissionsTeamId($tenant->getKey());
        $user->assignRole($role);

        return $user;
    }

    public function test_a_superadmin_passes_every_gate(): void
    {
        $user = User::factory()->forTenant()->create(['is_superadmin' => true]);

        setPermissionsTeamId($user->defaultMembership()?->tenant_id);
        $user->unsetRelation('roles')->unsetRelation('permissions');

        $this->assertTrue($user->can(Permission::AssetsCreate->value));
        $this->assertTrue($user->can(Permission::ClassificationsManage->value));
        $this->assertTrue($user->can(Permission::UsersManage->value));
    }

    public function test_a_default_role_user_can_view_but_not_write(): void
    {
        $tenant = Tenant::factory()->create();
        $user = $this->userWithRole($tenant, RbacSeeder::DEFAULT_ROLE);

        setPermissionsTeamId($tenant->getKey());
        $user->unsetRelation('roles')->unsetRelation('permissions');

        $this->assertTrue($user->can(Permission::AssetsView->value));
        $this->assertFalse($user->can(Permission::AssetsCreate->value));
        $this->assertFalse($user->can(Permission::DisposalsRun->value));
    }

    public function test_an_admin_tenant_role_user_can_do_every_tenant_action(): void
    {
        $tenant = Tenant::factory()->create();
        $user = $this->userWithRole($tenant, RbacSeeder::ADMIN_ROLE);

        setPermissionsTeamId($tenant->getKey());
        $user->unsetRelation('roles')->unsetRelation('permissions');

        foreach (Permission::cases() as $permission) {
            $this->assertTrue($user->can($permission->value), "Admin Tenant must be granted [{$permission->value}].");
        }
    }

    public function test_a_user_without_any_role_is_blocked_from_everything(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();

        setPermissionsTeamId($tenant->getKey());
        $user->unsetRelation('roles')->unsetRelation('permissions');

        $this->assertFalse($user->can(Permission::AssetsView->value));
        $this->assertFalse($user->can(Permission::AssetsCreate->value));
    }

    public function test_the_permission_middleware_blocks_an_unauthorized_action(): void
    {
        $tenant = Tenant::factory()->create();
        $user = $this->userWithRole($tenant, RbacSeeder::DEFAULT_ROLE);

        setPermissionsTeamId($tenant->getKey());

        $this->actingAs($user)->get('/_rbac-probe')->assertForbidden();
    }

    public function test_the_permission_middleware_allows_an_authorized_action(): void
    {
        $tenant = Tenant::factory()->create();
        $user = $this->userWithRole($tenant, RbacSeeder::ADMIN_ROLE);

        setPermissionsTeamId($tenant->getKey());

        $this->actingAs($user)->get('/_rbac-probe')->assertOk();
    }

    public function test_roles_resolve_per_team_for_a_multi_membership_user(): void
    {
        $first = Tenant::factory()->create();
        $second = Tenant::factory()->create();
        $user = User::factory()->create();

        TenantMembership::query()->create(['user_id' => $user->id, 'tenant_id' => $first->getKey(), 'is_default' => true]);
        TenantMembership::query()->create(['user_id' => $user->id, 'tenant_id' => $second->getKey(), 'is_default' => false]);

        // Seed after the tenants exist so both have their roles (grill Q4).
        $this->seed(RbacSeeder::class);

        $adminInFirst = Role::query()->where('name', RbacSeeder::ADMIN_ROLE)->where('tenant_id', $first->getKey())->firstOrFail();
        $defaultInSecond = Role::query()->where('name', RbacSeeder::DEFAULT_ROLE)->where('tenant_id', $second->getKey())->firstOrFail();

        setPermissionsTeamId($first->getKey());
        $user->assignRole($adminInFirst);

        setPermissionsTeamId($second->getKey());
        $user->unsetRelation('roles');
        $user->assignRole($defaultInSecond);

        // Acting inside the first tenant: admin — every action allowed.
        setPermissionsTeamId($first->getKey());
        $user->unsetRelation('roles')->unsetRelation('permissions');
        $this->assertTrue($user->can(Permission::ClassificationsManage->value));
        $this->assertTrue($user->can(Permission::AssetsView->value));

        // Acting inside the second tenant: staff — view only.
        setPermissionsTeamId($second->getKey());
        $user->unsetRelation('roles')->unsetRelation('permissions');
        $this->assertFalse($user->can(Permission::ClassificationsManage->value));
        $this->assertTrue($user->can(Permission::AssetsView->value));
    }
}
