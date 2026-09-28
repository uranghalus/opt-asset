<?php

namespace Tests\Feature\Rbac;

use App\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The spatie/laravel-permission schema facts T02 relies on (grill
 * 2026-09-28): teams permissions enabled with a tenant team foreign key,
 * ULID-compatible team columns, and the composite unique index that makes
 * concurrent role creation race-safe at the database level.
 *
 * Two package facts verified against the vendor source: the team key in
 * create attributes must be the configured `tenant_id` (an unknown key is
 * silently dropped at insert time), and the package's duplicate check also
 * matches global roles (`whereNull(tenant_id) OR tenant_id = current`) —
 * so a team-scoped role sharing a name with a global role must be created
 * through the query builder, not Role::create.
 */
class RbacSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_package_tables_exist(): void
    {
        foreach (['roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table [{$table}] must exist.");
        }
    }

    public function test_the_team_column_holds_tenant_ulids(): void
    {
        $this->assertTrue(Schema::hasColumn('roles', 'tenant_id'));
        $this->assertTrue(Schema::hasColumn('model_has_roles', 'tenant_id'));
        $this->assertTrue(Schema::hasColumn('model_has_permissions', 'tenant_id'));

        $this->assertSame('varchar', Schema::getColumnType('roles', 'tenant_id'));
    }

    public function test_team_scoped_roles_can_share_a_name_across_tenants(): void
    {
        $first = Tenant::factory()->create();
        $second = Tenant::factory()->create();

        $roleInFirst = Role::create(['name' => 'reader', 'tenant_id' => $first->getKey(), 'guard_name' => 'web']);
        $roleInSecond = Role::create(['name' => 'reader', 'tenant_id' => $second->getKey(), 'guard_name' => 'web']);

        $this->assertNotSame($roleInFirst->getKey(), $roleInSecond->getKey());
        $this->assertSame($first->getKey(), $roleInFirst->tenant_id);
        $this->assertSame($second->getKey(), $roleInSecond->tenant_id);
        $this->assertSame(2, Role::query()->where('name', 'reader')->count());
    }

    public function test_a_role_name_is_unique_within_a_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        Role::create(['name' => 'reader', 'tenant_id' => $tenant->getKey(), 'guard_name' => 'web']);

        // The application-level check fires first; the composite unique
        // index (team_id, name, guard_name) is the backstop for concurrent
        // creates that both pass this check.
        $this->expectException(RoleAlreadyExists::class);

        Role::create(['name' => 'reader', 'tenant_id' => $tenant->getKey(), 'guard_name' => 'web']);
    }

    public function test_a_global_role_name_is_unique(): void
    {
        Role::create(['name' => 'reader', 'tenant_id' => null, 'guard_name' => 'web']);

        $this->expectException(RoleAlreadyExists::class);

        Role::create(['name' => 'reader', 'tenant_id' => null, 'guard_name' => 'web']);
    }

    public function test_a_role_cannot_reference_a_missing_tenant(): void
    {
        $this->expectException(QueryException::class);

        Role::create(['name' => 'reader', 'tenant_id' => '01NOTAREALTENANT0000000000', 'guard_name' => 'web']);
    }
}
