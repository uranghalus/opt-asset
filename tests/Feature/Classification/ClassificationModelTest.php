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
use App\Tenancy\TenantContextRequiredException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Model-level contract for the classification chain (T03.2): the
 * fail-closed tenant wrapper stamps the acting tenant on create, throws
 * without a context, and never lets an injected tenant_id cross the
 * isolation boundary. Chain relations resolve in both directions.
 */
class ClassificationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_chain_record_stamps_the_acting_tenant(): void
    {
        $user = User::factory()->forTenant(Tenant::factory()->create())->create();
        TenantContext::initializeFromUser($user);

        $group = AssetGroup::query()->create(['code' => 'A', 'name' => 'Golongan A']);

        $this->assertSame(TenantContext::id(), $group->tenant_id);
    }

    public function test_creating_any_chain_level_without_an_acting_tenant_throws(): void
    {
        TenantContext::end();

        $this->expectException(TenantContextRequiredException::class);

        AssetSubCluster::query()->create([
            'asset_cluster_id' => str()->ulid()->toBase32(),
            'code' => 'Z999',
            'name' => 'Must not persist',
        ]);
        Item::query()->create(['name' => 'Must not persist']);
    }

    public function test_an_injected_tenant_id_never_overrides_the_acting_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenantA)->create();
        TenantContext::initializeFromUser($user);

        // Simulates `tenant_id` sneaking in through request input: the
        // acting tenant must win, never the injected value.
        $group = AssetGroup::query()->create([
            'tenant_id' => $tenantB->getKey(),
            'code' => 'B',
            'name' => 'Golongan B',
        ]);

        $this->assertSame($tenantA->getKey(), $group->refresh()->tenant_id);
        $this->assertSame(1, AssetGroup::query()->count());
    }

    public function test_chain_relations_resolve_up_and_down(): void
    {
        $user = User::factory()->forTenant(Tenant::factory()->create())->create();
        TenantContext::initializeFromUser($user);

        $group = AssetGroup::query()->create(['code' => 'A', 'name' => 'Golongan A']);
        $category = AssetCategory::factory()->forGroup($group)->create();
        $cluster = AssetCluster::factory()->forCategory($category)->create();
        $subCluster = AssetSubCluster::factory()->forCluster($cluster)->create();
        $item = Item::factory()->forSubCluster($subCluster)->create();

        $this->assertTrue($group->categories->contains($category));
        $this->assertTrue($category->clusters->contains($cluster));
        $this->assertTrue($cluster->subClusters->contains($subCluster));
        $this->assertTrue($subCluster->items->contains($item));
        $this->assertTrue($item->subCluster->is($subCluster));
        $this->assertTrue($subCluster->cluster->is($cluster));
        $this->assertTrue($cluster->category->is($category));
        $this->assertTrue($category->group->is($group));
    }

    public function test_items_can_exist_without_a_classification(): void
    {
        $user = User::factory()->forTenant(Tenant::factory()->create())->create();
        TenantContext::initializeFromUser($user);

        $item = Item::factory()->create();

        $this->assertNull($item->asset_sub_cluster_id);
        $this->assertNull($item->subCluster);
    }

    public function test_chain_factories_resolve_tenant_through_the_parent(): void
    {
        $user = User::factory()->forTenant(Tenant::factory()->create())->create();
        TenantContext::initializeFromUser($user);

        $group = AssetGroup::factory()->create();
        $category = AssetCategory::factory()->forGroup($group)->create();

        $this->assertSame($group->tenant_id, $category->tenant_id);
        $this->assertSame(1, AssetGroup::query()->count());
    }
}
