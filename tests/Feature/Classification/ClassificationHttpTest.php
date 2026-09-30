<?php

namespace Tests\Feature\Classification;

use App\Models\AssetCategory;
use App\Models\AssetCluster;
use App\Models\AssetGroup;
use App\Models\AssetSubCluster;
use App\Models\Item;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\Facades\TenantContext;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * HTTP layer of the classification chain (T03.4): the first production
 * consumer of the RBAC machinery. Every route runs behind
 * permission:classifications.manage — a default-role user is denied on every
 * route, an Admin Tenant user manages the chain over real HTTP, and ADR-0001
 * surfaces as a validation error instead of a 500.
 */
class ClassificationHttpTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        // Tenants must exist before seeding so both receive their roles
        // (RbacSeeder seeds roles for active tenants only).
        $this->tenantA = Tenant::factory()->create();
        $this->tenantB = Tenant::factory()->create();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(RbacSeeder::class);
    }

    /**
     * A tenant user holding the named seeded role for that team.
     *
     * @param  Tenant  $tenant  the tenant whose roles are assigned
     * @param  string  $roleName  the seeded role name ('default' or 'Admin Tenant')
     * @return User the created user with the role assigned for that team
     */
    protected function userWithRole(Tenant $tenant, string $roleName): User
    {
        $user = User::factory()->forTenant($tenant)->create();

        $role = Role::query()->where('name', $roleName)->where('tenant_id', $tenant->getKey())->firstOrFail();

        setPermissionsTeamId($tenant->getKey());
        $user->assignRole($role);
        $user->unsetRelation('roles')->unsetRelation('permissions');

        return $user;
    }

    /**
     * An Admin Tenant user of the given tenant.
     */
    protected function adminUserFor(Tenant $tenant): User
    {
        return $this->userWithRole($tenant, RbacSeeder::ADMIN_ROLE);
    }

    /**
     * Seed a three-level chain under the acting tenant through the real
     * write path, returning the sub-cluster for item tests.
     */
    protected function seedSubCluster(): AssetSubCluster
    {
        TenantContext::initialize($this->tenantA);

        $group = AssetGroup::query()->create(['code' => 'A', 'name' => 'Golongan A']);
        $category = AssetCategory::factory()->forGroup($group)->create();
        $cluster = AssetCluster::factory()->forCategory($category)->create();

        return AssetSubCluster::factory()->forCluster($cluster)->create();
    }

    public function test_guests_are_redirected_to_sso(): void
    {
        $this->get(route('classifications.groups.index'))
            ->assertRedirect(route('saml.redirect'));
    }

    public function test_a_default_role_user_is_denied_on_every_route(): void
    {
        // A real group of the user's own tenant: the binding must resolve
        // so the permission middleware (not a 404) delivers the denial.
        TenantContext::initialize($this->tenantA);
        $group = AssetGroup::query()->create(['code' => 'A', 'name' => 'Golongan A']);

        $user = $this->userWithRole($this->tenantA, RbacSeeder::DEFAULT_ROLE);

        $this->actingAs($user);

        $this->get(route('classifications.groups.index'))->assertForbidden();
        $this->get(route('classifications.groups.create'))->assertForbidden();
        $this->post(route('classifications.groups.store'), ['code' => 'B', 'name' => 'Golongan B'])->assertForbidden();
        $this->get(route('classifications.groups.edit', ['group' => $group->getKey()]))->assertForbidden();
        $this->put(route('classifications.groups.update', ['group' => $group->getKey()]), ['code' => 'C', 'name' => 'x'])->assertForbidden();
        $this->delete(route('classifications.groups.destroy', ['group' => $group->getKey()]))->assertForbidden();
        $this->get(route('classifications.items.index'))->assertForbidden();

        $this->assertDatabaseCount('asset_groups', 1);
    }

    public function test_an_admin_tenant_user_creates_a_chain_over_http(): void
    {
        $user = $this->adminUserFor($this->tenantA);

        $this->actingAs($user)
            ->get(route('classifications.groups.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $inertia) => $inertia->component('classification/groups/index'));

        $this->post(route('classifications.groups.store'), ['code' => 'A', 'name' => 'Golongan A'])
            ->assertRedirect(route('classifications.groups.index'));

        $group = AssetGroup::query()->where('code', 'A')->firstOrFail();

        $this->post(route('classifications.categories.store'), [
            'asset_group_id' => $group->getKey(),
            'code' => 'A1',
            'name' => 'Kategori A1',
        ])->assertRedirect(route('classifications.categories.index'));

        $this->assertDatabaseHas('asset_groups', ['code' => 'A', 'name' => 'Golongan A', 'tenant_id' => $this->tenantA->getKey()]);
        $this->assertDatabaseHas('asset_categories', ['code' => 'A1', 'name' => 'Kategori A1', 'tenant_id' => $this->tenantA->getKey()]);
    }

    public function test_a_duplicate_code_is_rejected_within_the_same_tenant(): void
    {
        $user = $this->adminUserFor($this->tenantA);
        $this->actingAs($user);

        $this->post(route('classifications.groups.store'), ['code' => 'A', 'name' => 'Golongan A'])->assertRedirect();

        $this->from(route('classifications.groups.create'))
            ->post(route('classifications.groups.store'), ['code' => 'A', 'name' => 'Golongan duplikat'])
            ->assertRedirect(route('classifications.groups.create'))
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('asset_groups', 1);
    }

    public function test_the_same_code_is_allowed_in_another_tenant(): void
    {
        $userA = $this->adminUserFor($this->tenantA);
        $this->actingAs($userA);

        $this->post(route('classifications.groups.store'), ['code' => 'A', 'name' => 'Golongan A'])->assertRedirect();

        $userB = $this->adminUserFor($this->tenantB);
        $this->actingAs($userB);

        $this->post(route('classifications.groups.store'), ['code' => 'A', 'name' => 'Golongan B'])->assertRedirect();

        $this->assertDatabaseCount('asset_groups', 2);
    }

    public function test_an_empty_name_is_rejected(): void
    {
        $user = $this->adminUserFor($this->tenantA);
        $this->actingAs($user);

        $this->from(route('classifications.groups.create'))
            ->post(route('classifications.groups.store'), ['code' => 'A', 'name' => ''])
            ->assertRedirect(route('classifications.groups.create'))
            ->assertSessionHasErrors('name');
    }

    public function test_a_missing_parent_is_rejected(): void
    {
        $user = $this->adminUserFor($this->tenantA);
        $this->actingAs($user);

        $this->from(route('classifications.categories.create'))
            ->post(route('classifications.categories.store'), ['code' => 'A1', 'name' => 'Kategori A1'])
            ->assertRedirect(route('classifications.categories.create'))
            ->assertSessionHasErrors('asset_group_id');
    }

    public function test_updating_an_unreferenced_code_succeeds(): void
    {
        $user = $this->adminUserFor($this->tenantA);
        $this->actingAs($user);

        $this->post(route('classifications.groups.store'), ['code' => 'A', 'name' => 'Golongan A'])->assertRedirect();

        $group = AssetGroup::query()->where('code', 'A')->firstOrFail();

        $this->put(route('classifications.groups.update', ['group' => $group->getKey()]), ['code' => 'B', 'name' => 'Golongan A'])
            ->assertRedirect(route('classifications.groups.index'));

        $this->assertDatabaseHas('asset_groups', ['id' => $group->getKey(), 'code' => 'B']);
    }

    public function test_updating_a_referenced_code_returns_a_validation_error(): void
    {
        $user = $this->adminUserFor($this->tenantA);
        $this->actingAs($user);

        $this->post(route('classifications.groups.store'), ['code' => 'A', 'name' => 'Golongan A'])->assertRedirect();
        $group = AssetGroup::query()->where('code', 'A')->firstOrFail();
        $this->post(route('classifications.categories.store'), [
            'asset_group_id' => $group->getKey(),
            'code' => 'A1',
            'name' => 'Kategori Elektronik',
        ])->assertRedirect();

        $this->from(route('classifications.groups.edit', ['group' => $group->getKey()]))
            ->put(route('classifications.groups.update', ['group' => $group->getKey()]), ['code' => 'Z', 'name' => 'Golongan A'])
            ->assertRedirect(route('classifications.groups.edit', ['group' => $group->getKey()]))
            ->assertSessionHasErrors('code');

        $this->assertStringContainsString('Kategori Elektronik', (string) session('errors')?->first('code'));
        $this->assertDatabaseHas('asset_groups', ['id' => $group->getKey(), 'code' => 'A']);
    }

    public function test_a_referenced_code_update_is_a_literal_422_for_json_requests(): void
    {
        $user = $this->adminUserFor($this->tenantA);
        $this->actingAs($user);

        $this->post(route('classifications.groups.store'), ['code' => 'A', 'name' => 'Golongan A'])->assertRedirect();
        $group = AssetGroup::query()->where('code', 'A')->firstOrFail();
        $this->post(route('classifications.categories.store'), [
            'asset_group_id' => $group->getKey(),
            'code' => 'A1',
            'name' => 'Kategori Elektronik',
        ])->assertRedirect();

        $this->putJson(route('classifications.groups.update', ['group' => $group->getKey()]), ['code' => 'Z', 'name' => 'Golongan A'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_the_name_of_a_referenced_level_stays_editable(): void
    {
        $user = $this->adminUserFor($this->tenantA);
        $this->actingAs($user);

        $this->post(route('classifications.groups.store'), ['code' => 'A', 'name' => 'Golongan A'])->assertRedirect();
        $group = AssetGroup::query()->where('code', 'A')->firstOrFail();
        $this->post(route('classifications.categories.store'), [
            'asset_group_id' => $group->getKey(),
            'code' => 'A1',
            'name' => 'Kategori A1',
        ])->assertRedirect();

        $this->put(route('classifications.groups.update', ['group' => $group->getKey()]), ['code' => 'A', 'name' => 'Golongan direnovasi'])
            ->assertRedirect(route('classifications.groups.index'));

        $this->assertDatabaseHas('asset_groups', ['id' => $group->getKey(), 'code' => 'A', 'name' => 'Golongan direnovasi']);
    }

    public function test_deleting_an_unreferenced_level_succeeds(): void
    {
        $user = $this->adminUserFor($this->tenantA);
        $this->actingAs($user);

        $this->post(route('classifications.groups.store'), ['code' => 'A', 'name' => 'Golongan A'])->assertRedirect();

        $group = AssetGroup::query()->where('code', 'A')->firstOrFail();

        $this->delete(route('classifications.groups.destroy', ['group' => $group->getKey()]))
            ->assertRedirect(route('classifications.groups.index'));

        $this->assertDatabaseMissing('asset_groups', ['id' => $group->getKey()]);
    }

    public function test_deleting_a_referenced_level_returns_a_validation_error(): void
    {
        $user = $this->adminUserFor($this->tenantA);
        $this->actingAs($user);

        $this->post(route('classifications.groups.store'), ['code' => 'A', 'name' => 'Golongan A'])->assertRedirect();
        $group = AssetGroup::query()->where('code', 'A')->firstOrFail();
        $this->post(route('classifications.categories.store'), [
            'asset_group_id' => $group->getKey(),
            'code' => 'A1',
            'name' => 'Kategori Elektronik',
        ])->assertRedirect();

        $this->from(route('classifications.groups.index'))
            ->delete(route('classifications.groups.destroy', ['group' => $group->getKey()]))
            ->assertRedirect(route('classifications.groups.index'))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('asset_groups', ['id' => $group->getKey()]);
    }

    public function test_items_can_be_created_with_and_without_a_sub_cluster(): void
    {
        $user = $this->adminUserFor($this->tenantA);
        $this->actingAs($user);

        $this->post(route('classifications.items.store'), ['name' => 'Item lepas'])
            ->assertRedirect(route('classifications.items.index'));

        $this->post(route('classifications.items.store'), [
            'name' => 'Item terklasifikasi',
            'asset_sub_cluster_id' => $this->seedSubCluster()->getKey(),
        ])->assertRedirect(route('classifications.items.index'));

        $this->assertDatabaseHas('items', ['name' => 'Item lepas', 'asset_sub_cluster_id' => null]);
        $this->assertDatabaseHas('items', ['name' => 'Item terklasifikasi']);
    }

    public function test_item_names_are_unique_per_tenant(): void
    {
        $user = $this->adminUserFor($this->tenantA);
        $this->actingAs($user);

        $this->post(route('classifications.items.store'), ['name' => 'Sanyo 5PK-850'])->assertRedirect();

        $this->from(route('classifications.items.create'))
            ->post(route('classifications.items.store'), ['name' => 'Sanyo 5PK-850'])
            ->assertRedirect(route('classifications.items.create'))
            ->assertSessionHasErrors('name');
    }
}
