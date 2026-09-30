<?php

namespace Tests\Feature\Classification;

use App\Classification\CodeLockedException;
use App\Models\AssetCategory;
use App\Models\AssetCluster;
use App\Models\AssetGroup;
use App\Models\AssetSubCluster;
use App\Models\Item;
use App\Models\Tenant;
use App\Tenancy\Facades\TenantContext;
use App\Tenancy\TenantContextRequiredException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Two-tenant isolation + immutability proof for the classification chain
 * (T03.3, rules §1.1 + ADR-0001) — the same shape of proof as the Tenancy
 * isolation harness: two tenants, identical codes, zero leakage.
 *
 * The suite fails if any future change lets tenant A see or mutate tenant
 * B's chain, lets a referenced chain code change, or removes the DB guard
 * behind a referenced parent's deletion.
 */
class ClassificationIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::factory()->create();
        $this->tenantB = Tenant::factory()->create();
    }

    public function test_chain_queries_per_level_never_cross_tenants_with_identical_codes(): void
    {
        $chains = [
            $this->tenantA->getKey() => $this->seedChain($this->tenantA),
            $this->tenantB->getKey() => $this->seedChain($this->tenantB),
        ];

        // Act as tenant A: identical codes exist in both tenants, so every
        // per-level query must return exactly this tenant's row.
        TenantContext::initialize($this->tenantA);

        $this->assertSame(['A'], AssetGroup::query()->pluck('code')->all());
        $this->assertSame(['A1'], AssetCategory::query()->pluck('code')->all());
        $this->assertSame(['A11'], AssetCluster::query()->pluck('code')->all());
        $this->assertSame(['A111'], AssetSubCluster::query()->pluck('code')->all());
        $this->assertSame(['Sanyo 5PK-850'], Item::query()->pluck('name')->all());

        // Tenant B's primary keys are invisible from tenant A.
        $this->assertNull(AssetGroup::query()->find($chains[$this->tenantB->getKey()]['group']->getKey()));

        // Switching acting tenants flips the visible world completely.
        TenantContext::initialize($this->tenantB);

        $this->assertSame(
            [$chains[$this->tenantB->getKey()]['group']->getKey()],
            AssetGroup::query()->pluck('id')->all(),
        );
    }

    public function test_cascade_filtering_excludes_other_tenants_children_sharing_the_same_code(): void
    {
        $chainA = $this->seedChain($this->tenantA);
        $this->seedChain($this->tenantB);

        TenantContext::initialize($this->tenantA);

        $this->assertSame(['A1'], $chainA['group']->categories()->pluck('code')->all());
        $this->assertSame(['A11'], $chainA['category']->clusters()->pluck('code')->all());
        $this->assertSame(['A111'], $chainA['cluster']->subClusters()->pluck('code')->all());
        $this->assertSame(['Sanyo 5PK-850'], $chainA['sub_cluster']->items()->pluck('name')->all());
    }

    public function test_creating_a_group_without_an_acting_tenant_throws(): void
    {
        TenantContext::end();

        $this->expectException(TenantContextRequiredException::class);

        AssetGroup::query()->create(['code' => 'A', 'name' => 'Must not persist']);
    }

    public function test_creating_a_category_without_an_acting_tenant_throws(): void
    {
        TenantContext::end();

        $this->expectException(TenantContextRequiredException::class);

        AssetCategory::query()->create([
            'asset_group_id' => str()->ulid()->toBase32(),
            'code' => 'A1',
            'name' => 'Must not persist',
        ]);
    }

    public function test_creating_a_cluster_without_an_acting_tenant_throws(): void
    {
        TenantContext::end();

        $this->expectException(TenantContextRequiredException::class);

        AssetCluster::query()->create([
            'asset_category_id' => str()->ulid()->toBase32(),
            'code' => 'A11',
            'name' => 'Must not persist',
        ]);
    }

    public function test_editing_a_referenced_group_code_is_rejected(): void
    {
        $chain = $this->seedChain($this->tenantA);

        $chain['group']->code = 'B';

        $this->expectException(CodeLockedException::class);

        $chain['group']->save();
    }

    public function test_editing_a_referenced_category_code_is_rejected(): void
    {
        $chain = $this->seedChain($this->tenantA);

        $chain['category']->code = 'A9';

        $this->expectException(CodeLockedException::class);

        $chain['category']->save();
    }

    public function test_editing_a_referenced_cluster_code_is_rejected(): void
    {
        $chain = $this->seedChain($this->tenantA);

        $chain['cluster']->code = 'A99';

        $this->expectException(CodeLockedException::class);

        $chain['cluster']->save();
    }

    public function test_editing_a_referenced_sub_cluster_code_is_rejected(): void
    {
        $chain = $this->seedChain($this->tenantA);

        $chain['sub_cluster']->code = 'A999';

        $this->expectException(CodeLockedException::class);

        $chain['sub_cluster']->save();
    }

    public function test_a_rejected_code_edit_persists_nothing(): void
    {
        $chain = $this->seedChain($this->tenantA);

        try {
            $chain['group']->code = 'B';
            $chain['group']->save();
            $this->fail('Expected the referenced code edit to be rejected.');
        } catch (CodeLockedException) {
            $this->assertSame('A', $chain['group']->refresh()->code);
            $this->assertSame('A', AssetGroup::query()->whereKey($chain['group']->getKey())->value('code'));
        }
    }

    public function test_editing_an_unreferenced_chain_code_still_succeeds(): void
    {
        $chain = $this->seedChain($this->tenantA);

        // A Golongan without a Kategori is still a typo-fixable code.
        $unreferenced = AssetGroup::query()->create(['code' => 'B', 'name' => 'Golongan B']);
        $unreferenced->code = 'X';
        $unreferenced->save();

        $this->assertSame('X', $unreferenced->refresh()->code);

        // Same proof at the deepest level: a Sub Kelompok without Items.
        $bare = AssetSubCluster::query()->create([
            'asset_cluster_id' => $chain['cluster']->getKey(),
            'code' => 'A112',
            'name' => 'Sub Kelompok A112',
        ]);
        $bare->code = 'Z';
        $bare->save();

        $this->assertSame('Z', $bare->refresh()->code);
    }

    public function test_deleting_a_referenced_parent_level_is_rejected_by_the_database(): void
    {
        $chain = $this->seedChain($this->tenantA);

        $this->expectException(QueryException::class);

        $chain['group']->delete();
    }

    /**
     * Build one tenant's full chain through the real write path inside
     * that tenant's context — identical codes across tenants are what make
     * the isolation proof meaningful.
     *
     * @return array{group: AssetGroup, category: AssetCategory, cluster: AssetCluster, sub_cluster: AssetSubCluster, item: Item}
     */
    protected function seedChain(Tenant $tenant): array
    {
        TenantContext::initialize($tenant);

        $group = AssetGroup::query()->create(['code' => 'A', 'name' => 'Golongan A']);
        $category = AssetCategory::query()->create([
            'asset_group_id' => $group->getKey(), 'code' => 'A1', 'name' => 'Kategori A1',
        ]);
        $cluster = AssetCluster::query()->create([
            'asset_category_id' => $category->getKey(), 'code' => 'A11', 'name' => 'Kelompok A11',
        ]);
        $subCluster = AssetSubCluster::query()->create([
            'asset_cluster_id' => $cluster->getKey(), 'code' => 'A111', 'name' => 'Sub Kelompok A111',
        ]);
        $item = Item::query()->create([
            'asset_sub_cluster_id' => $subCluster->getKey(), 'name' => 'Sanyo 5PK-850',
        ]);

        return [
            'group' => $group,
            'category' => $category,
            'cluster' => $cluster,
            'sub_cluster' => $subCluster,
            'item' => $item,
        ];
    }
}
