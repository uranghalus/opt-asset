<?php

namespace Tests\Feature\Classification;

use App\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The classification chain schema facts T03 relies on (rules §1.3 + grill
 * 2026-09-29): four chain levels plus items, tenant-owned, ULID primary
 * keys, parent foreign keys that restrict deletion (ADR-0001), and the
 * composite unique constraints making chain codes unique per tenant per
 * level — while staying freely reusable across tenants.
 *
 * Rows are inserted through the query builder on purpose: the schema
 * contract must hold before any Eloquent model exists to stamp it.
 */
class ClassificationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_chain_tables_exist(): void
    {
        foreach (['asset_groups', 'asset_categories', 'asset_clusters', 'asset_sub_clusters', 'items'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table [{$table}] must exist.");
        }
    }

    public function test_every_chain_table_is_tenant_owned_with_ulid_keys(): void
    {
        foreach (['asset_groups', 'asset_categories', 'asset_clusters', 'asset_sub_clusters', 'items'] as $table) {
            $this->assertTrue(Schema::hasColumn($table, 'tenant_id'), "Table [{$table}] must be tenant-owned.");
            $this->assertSame('varchar', Schema::getColumnType($table, 'id'), "Table [{$table}] must use ULID primary keys.");
            $this->assertSame('varchar', Schema::getColumnType($table, 'tenant_id'));
        }
    }

    public function test_items_sub_cluster_reference_is_nullable(): void
    {
        $column = collect(Schema::getColumns('items'))->firstWhere('name', 'asset_sub_cluster_id');

        $this->assertNotNull($column);
        $this->assertTrue((bool) $column['nullable'], 'Items may exist without a Sub Kelompok (auto-created imports).');
    }

    public function test_a_chain_code_is_unique_per_tenant_but_reusable_across_tenants(): void
    {
        $first = Tenant::factory()->create();
        $second = Tenant::factory()->create();

        $this->insertRow('asset_groups', ['tenant_id' => $first->getKey(), 'code' => 'A', 'name' => 'Group A']);
        $this->insertRow('asset_groups', ['tenant_id' => $second->getKey(), 'code' => 'A', 'name' => 'Group A elsewhere']);

        $this->expectException(QueryException::class);

        $this->insertRow('asset_groups', ['tenant_id' => $first->getKey(), 'code' => 'A', 'name' => 'Duplicate in same tenant']);
    }

    public function test_an_item_name_is_unique_per_tenant(): void
    {
        $first = Tenant::factory()->create();

        $this->insertRow('items', ['tenant_id' => $first->getKey(), 'name' => 'Sanyo 5PK-850']);
        $this->insertRow('items', ['tenant_id' => Tenant::factory()->create()->getKey(), 'name' => 'Sanyo 5PK-850']);

        $this->expectException(QueryException::class);

        $this->insertRow('items', ['tenant_id' => $first->getKey(), 'name' => 'Sanyo 5PK-850']);
    }

    public function test_a_referenced_parent_level_cannot_be_deleted(): void
    {
        $tenant = Tenant::factory()->create();
        $groupId = $this->insertRow('asset_groups', ['tenant_id' => $tenant->getKey(), 'code' => 'A', 'name' => 'Group A']);
        $this->insertRow('asset_categories', [
            'tenant_id' => $tenant->getKey(),
            'asset_group_id' => $groupId,
            'code' => 'A1',
            'name' => 'Category A1',
        ]);

        $this->expectException(QueryException::class);

        DB::table('asset_groups')->where('id', $groupId)->delete();
    }

    public function test_an_item_locks_its_sub_cluster_against_deletion(): void
    {
        $tenant = Tenant::factory()->create();
        $groupId = $this->insertRow('asset_groups', ['tenant_id' => $tenant->getKey(), 'code' => 'A', 'name' => 'Group A']);
        $categoryId = $this->insertRow('asset_categories', [
            'tenant_id' => $tenant->getKey(), 'asset_group_id' => $groupId, 'code' => 'A1', 'name' => 'Category A1',
        ]);
        $clusterId = $this->insertRow('asset_clusters', [
            'tenant_id' => $tenant->getKey(), 'asset_category_id' => $categoryId, 'code' => 'A11', 'name' => 'Cluster A11',
        ]);
        $subClusterId = $this->insertRow('asset_sub_clusters', [
            'tenant_id' => $tenant->getKey(), 'asset_cluster_id' => $clusterId, 'code' => 'A111', 'name' => 'Sub A111',
        ]);
        $this->insertRow('items', [
            'tenant_id' => $tenant->getKey(), 'asset_sub_cluster_id' => $subClusterId, 'name' => 'Sanyo 5PK-850',
        ]);

        $this->expectException(QueryException::class);

        DB::table('asset_sub_clusters')->where('id', $subClusterId)->delete();
    }

    public function test_chain_rows_cannot_reference_a_missing_tenant(): void
    {
        $this->expectException(QueryException::class);

        $this->insertRow('asset_groups', ['tenant_id' => '01NOTAREALTENANT0000000000', 'code' => 'A', 'name' => 'Orphan']);
    }

    public function test_the_migration_rolls_back_and_replays_cleanly(): void
    {
        $this->artisan('migrate:rollback', ['--step' => 1])->assertExitCode(0);

        foreach (['items', 'asset_sub_clusters', 'asset_clusters', 'asset_categories', 'asset_groups'] as $table) {
            $this->assertFalse(Schema::hasTable($table), "Table [{$table}] should be gone after rollback.");
        }

        $this->artisan('migrate')->assertExitCode(0);

        $this->assertTrue(Schema::hasTable('asset_groups'));
        $this->assertTrue(Schema::hasTable('items'));
    }

    /**
     * Insert a row through the query builder, bypassing Eloquent — the
     * schema contract must hold before any model exists to stamp it.
     *
     * @param  string  $table  table under test
     * @param  array<string, mixed>  $attributes  column values; id and timestamps are filled unless overridden
     * @return string the generated ULID primary key
     */
    protected function insertRow(string $table, array $attributes): string
    {
        $id = (string) str()->ulid();

        DB::table($table)->insert(array_merge([
            'id' => $id,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return $id;
    }
}
