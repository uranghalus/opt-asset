<?php

namespace Tests\Feature\Classification;

use App\Models\AssetCategory;
use App\Models\AssetCluster;
use App\Models\AssetGroup;
use App\Models\AssetSubCluster;
use App\Models\Item;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Tenancy\Facades\TenantContext;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Isolation over HTTP (T03.4): an Admin Tenant user never reads or targets
 * another tenant's chain records — foreign ids 404 through route model
 * binding, cross-tenant parents are rejected by validation (fail-closed,
 * watchpoint from #30), and the tenant switch flow rebinds the spatie team
 * before the permission checks run.
 */
class ClassificationHttpIsolationTest extends TestCase
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
     * An Admin Tenant user of the given tenant.
     *
     * @param  Tenant  $tenant  the tenant whose seeded Admin role is assigned
     * @return User the created user with the role assigned for that team
     */
    protected function adminUserFor(Tenant $tenant): User
    {
        $user = User::factory()->forTenant($tenant)->create();

        $role = Role::query()->where('name', RbacSeeder::ADMIN_ROLE)->where('tenant_id', $tenant->getKey())->firstOrFail();

        setPermissionsTeamId($tenant->getKey());
        $user->assignRole($role);
        $user->unsetRelation('roles')->unsetRelation('permissions');

        return $user;
    }

    /**
     * Seed an identical chain per tenant through the real write path, with
     * identical codes on both sides (the two-tenant proof shape).
     */
    protected function seedChain(Tenant $tenant): void
    {
        TenantContext::initialize($tenant);

        $group = AssetGroup::query()->create(['code' => 'A', 'name' => 'Golongan A']);
        $category = AssetCategory::query()->create(['asset_group_id' => $group->getKey(), 'code' => 'A1', 'name' => 'Kategori A']);
        $cluster = AssetCluster::query()->create(['asset_category_id' => $category->getKey(), 'code' => 'A11', 'name' => 'Kelompok A']);
        $subCluster = AssetSubCluster::query()->create(['asset_cluster_id' => $cluster->getKey(), 'code' => 'A111', 'name' => 'Sub Kelompok A']);
        Item::query()->create(['asset_sub_cluster_id' => $subCluster->getKey(), 'name' => 'Item A']);
    }

    public function test_the_index_never_shows_another_tenants_records(): void
    {
        $this->seedChain($this->tenantA);
        $this->seedChain($this->tenantB);

        $user = $this->adminUserFor($this->tenantA);

        $this->actingAs($user)
            ->get(route('classifications.groups.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $inertia) => $inertia
                ->component('classification/groups/index')
                ->has('groups.data', 1));
    }

    public function test_foreign_ids_return_404_on_every_verb(): void
    {
        $this->seedChain($this->tenantB);

        // The context is still tenant B here, so these lookups resolve B's
        // own chain records.
        $groupB = AssetGroup::query()->where('code', 'A')->firstOrFail();
        $categoryB = AssetCategory::query()->where('code', 'A1')->firstOrFail();
        $clusterB = AssetCluster::query()->where('code', 'A11')->firstOrFail();
        $subClusterB = AssetSubCluster::query()->where('code', 'A111')->firstOrFail();
        $itemB = Item::query()->where('name', 'Item A')->firstOrFail();

        $user = $this->adminUserFor($this->tenantA);
        $this->actingAs($user);

        $this->get(route('classifications.groups.edit', ['group' => $groupB->getKey()]))->assertNotFound();
        $this->put(route('classifications.groups.update', ['group' => $groupB->getKey()]), ['code' => 'X', 'name' => 'x'])->assertNotFound();
        $this->delete(route('classifications.groups.destroy', ['group' => $groupB->getKey()]))->assertNotFound();
        $this->put(route('classifications.categories.update', ['category' => $categoryB->getKey()]), ['code' => 'X', 'name' => 'x'])->assertNotFound();
        $this->put(route('classifications.clusters.update', ['cluster' => $clusterB->getKey()]), ['code' => 'X', 'name' => 'x'])->assertNotFound();
        $this->put(route('classifications.sub-clusters.update', ['subCluster' => $subClusterB->getKey()]), ['code' => 'X', 'name' => 'x'])->assertNotFound();
        $this->put(route('classifications.items.update', ['item' => $itemB->getKey()]), ['name' => 'x'])->assertNotFound();

        $this->assertDatabaseHas('asset_groups', ['id' => $groupB->getKey(), 'code' => 'A']);
    }

    public function test_a_cross_tenant_group_parent_is_rejected_on_category_create(): void
    {
        $this->seedChain($this->tenantB);
        $groupB = AssetGroup::query()->where('code', 'A')->firstOrFail();

        $user = $this->adminUserFor($this->tenantA);
        $this->actingAs($user);

        $this->from(route('classifications.categories.create'))
            ->post(route('classifications.categories.store'), [
                'asset_group_id' => $groupB->getKey(),
                'code' => 'A1',
                'name' => 'Kategori lintas tenant',
            ])
            ->assertRedirect(route('classifications.categories.create'))
            ->assertSessionHasErrors('asset_group_id');

        $this->assertDatabaseCount('asset_categories', 1);
    }

    public function test_a_cross_tenant_category_parent_is_rejected_on_cluster_create(): void
    {
        $this->seedChain($this->tenantB);
        $categoryB = AssetCategory::query()->where('code', 'A1')->firstOrFail();

        $user = $this->adminUserFor($this->tenantA);
        $this->actingAs($user);

        $this->post(route('classifications.clusters.store'), [
            'asset_category_id' => $categoryB->getKey(),
            'code' => 'A11',
            'name' => 'Kelompok lintas tenant',
        ])->assertRedirect()
            ->assertSessionHasErrors('asset_category_id');

        $this->assertDatabaseCount('asset_clusters', 1);
    }

    public function test_a_cross_tenant_cluster_parent_is_rejected_on_sub_cluster_create(): void
    {
        $this->seedChain($this->tenantB);
        $clusterB = AssetCluster::query()->where('code', 'A11')->firstOrFail();

        $user = $this->adminUserFor($this->tenantA);
        $this->actingAs($user);

        $this->post(route('classifications.sub-clusters.store'), [
            'asset_cluster_id' => $clusterB->getKey(),
            'code' => 'A111',
            'name' => 'Sub kelompok lintas tenant',
        ])->assertRedirect()
            ->assertSessionHasErrors('asset_cluster_id');

        $this->assertDatabaseCount('asset_sub_clusters', 1);
    }

    public function test_a_cross_tenant_sub_cluster_is_rejected_for_items(): void
    {
        $this->seedChain($this->tenantB);
        $subClusterB = AssetSubCluster::query()->where('code', 'A111')->firstOrFail();

        $user = $this->adminUserFor($this->tenantA);
        $this->actingAs($user);

        $this->post(route('classifications.items.store'), [
            'name' => 'Item lintas tenant',
            'asset_sub_cluster_id' => $subClusterB->getKey(),
        ])->assertRedirect()
            ->assertSessionHasErrors('asset_sub_cluster_id');

        // An unclassified item is still allowed (items are the leaf level).
        $this->post(route('classifications.items.store'), ['name' => 'Item lepas'])
            ->assertRedirect();

        $this->assertDatabaseCount('items', 2);
    }

    public function test_the_tenant_switch_still_rebinds_the_team_before_permission_checks(): void
    {
        $user = User::factory()->create();

        TenantMembership::query()->create(['user_id' => $user->id, 'tenant_id' => $this->tenantA->getKey(), 'is_default' => true]);
        TenantMembership::query()->create(['user_id' => $user->id, 'tenant_id' => $this->tenantB->getKey(), 'is_default' => false]);

        $role = Role::query()->where('name', RbacSeeder::ADMIN_ROLE)->where('tenant_id', $this->tenantB->getKey())->firstOrFail();

        setPermissionsTeamId($this->tenantB->getKey());
        $user->assignRole($role);

        // The default context resolves to tenant A (default membership) —
        // the Admin role lives on team B, so the gated route is denied.
        $this->actingAs($user)->get(route('classifications.groups.index'))->assertForbidden();

        // Switch through the real HTTP switch flow...
        $this->post(route('tenant.switch'), ['tenant_id' => $this->tenantB->getKey()])->assertRedirect();

        // ...and the permission middleware now resolves team B's roles.
        $this->get(route('classifications.groups.index'))->assertOk();
    }
}
