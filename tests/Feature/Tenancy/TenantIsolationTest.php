<?php

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\Facades\TenantContext;
use App\Tenancy\TenantContextRequiredException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\Machine;
use Tests\TestCase;

use function Tests\Support\createMachineTable;

/**
 * Two-tenant isolation harness for the fail-closed BelongsToTenant wrapper.
 *
 * Every future domain table must pass the same shape of proof: acting as
 * tenant A never returns tenant B's rows, and no acting tenant returns
 * nothing. This file is the executable reference for that contract
 * (PROJECT-PLAN Phase 1).
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        createMachineTable();

        $this->tenantA = Tenant::factory()->create();
        $this->tenantB = Tenant::factory()->create();
    }

    protected function tearDown(): void
    {
        // Drop the fixture table so per-test state can never leak into the
        // next test through a surviving connection.
        DB::connection()->getSchemaBuilder()->dropIfExists('machines');

        parent::tearDown();
    }

    /**
     * Seed rows for both tenants through the raw query builder, bypassing the
     * stamping hook on purpose: the harness needs pre-existing rows exactly
     * like a database that was populated before the acting user logs in.
     *
     * @param  array<string, string>  $names  tenant id => machine name
     */
    protected function seedMachines(array $names): void
    {
        foreach ($names as $tenantId => $name) {
            DB::table('machines')->insert([
                'id' => (string) str()->ulid(),
                'tenant_id' => $tenantId,
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function test_queries_from_tenant_a_never_return_tenant_b_rows(): void
    {
        $this->seedMachines([
            $this->tenantA->getKey() => 'generator-A',
            $this->tenantB->getKey() => 'generator-B',
        ]);

        $userA = User::factory()->forTenant($this->tenantA)->create();
        $this->actingAs($userA);
        TenantContext::initializeFromUser($userA);

        // Model level: every list/find must be filtered.
        $this->assertSame(['generator-A'], Machine::query()->pluck('name')->all());
        $this->assertNull(Machine::query()->where('name', 'generator-B')->first());

        // Query-builder level: arbitrary Eloquent queries stay scoped too.
        $this->assertSame(
            ['generator-A'],
            Machine::query()->where('name', 'like', 'generator-%')->pluck('name')->all(),
        );

        // Switching acting tenants flips the visible world completely.
        $userB = User::factory()->forTenant($this->tenantB)->create();
        TenantContext::initializeFromUser($userB);
        $this->assertSame(['generator-B'], Machine::query()->pluck('name')->all());
    }

    public function test_queries_fail_closed_without_an_acting_tenant(): void
    {
        $this->seedMachines([
            $this->tenantA->getKey() => 'generator-A',
            $this->tenantB->getKey() => 'generator-B',
        ]);

        // Guest context: tenancy was never initialized, so the wrapper must
        // return zero rows — never an unscoped table scan.
        TenantContext::end();
        $this->assertFalse(TenantContext::check());
        $this->assertSame(0, Machine::query()->count());
    }

    public function test_an_authenticated_user_without_a_tenant_sees_no_domain_rows(): void
    {
        $this->seedMachines([
            $this->tenantA->getKey() => 'generator-A',
            $this->tenantB->getKey() => 'generator-B',
        ]);

        // Bootstrap accounts (e.g. the very first SSO admin) may exist before
        // any tenant does. Fail-closed still applies to them.
        $bootstrapUser = User::factory()->create();
        TenantContext::initializeFromUser($bootstrapUser);

        $this->assertFalse(TenantContext::check());
        $this->assertSame(0, Machine::query()->count());
    }

    public function test_creating_a_domain_record_stamps_the_acting_tenant(): void
    {
        $userA = User::factory()->forTenant($this->tenantA)->create();
        TenantContext::initializeFromUser($userA);

        $machine = Machine::query()->create(['name' => 'stamped-machine']);

        $this->assertSame($this->tenantA->getKey(), $machine->tenant_id);
        $this->assertSame(1, Machine::query()->count());
    }

    public function test_creating_a_domain_record_without_an_acting_tenant_is_blocked(): void
    {
        TenantContext::end();

        $this->expectException(TenantContextRequiredException::class);

        Machine::query()->create(['name' => 'must-not-persist']);
    }

    public function test_the_http_middleware_resolves_the_tenant_from_the_authenticated_user(): void
    {
        $userA = User::factory()->forTenant($this->tenantA)->create();
        $this->actingAs($userA);

        $this->get(route('dashboard'))->assertOk();

        $this->assertTrue(TenantContext::check());
        $this->assertSame($this->tenantA->getKey(), TenantContext::id());
    }

    public function test_the_http_middleware_never_initializes_a_suspended_tenant(): void
    {
        $userA = User::factory()
            ->forTenant(Tenant::factory()->suspended()->create())
            ->create();
        $this->actingAs($userA);

        // Fail-closed: a suspended tenant resolves to nothing, and the
        // request cannot proceed into tenant-scoped surfaces.
        $this->followingRedirects()->get(route('dashboard'))->assertForbidden();

        $this->assertFalse(TenantContext::check());
    }

    public function test_guest_requests_never_initialize_a_tenant(): void
    {
        $this->get('/')->assertRedirect();

        $this->assertFalse(TenantContext::check());
        $this->assertNull(TenantContext::id());
    }

    public function test_domain_scope_works_without_http_via_explicit_tenant_binding(): void
    {
        // This is the exact binding a queue job or Artisan command performs:
        // no HTTP session, explicit tenant, full isolation semantics.
        TenantContext::initialize($this->tenantB);

        $machine = Machine::query()->create(['name' => 'job-machine']);

        $this->assertSame($this->tenantB->getKey(), $machine->tenant_id);
        $this->assertSame(1, Machine::query()->count());

        // A closure run for another tenant is atomic and restores context.
        $seenId = $this->tenantA->run(function (Tenant $tenant): string {
            Machine::query()->create(['name' => 'closure-machine']);

            return $tenant->getKey();
        });

        $this->assertSame($this->tenantA->getKey(), $seenId);
        $this->assertSame($this->tenantB->getKey(), TenantContext::id());

        // Context restoration is proven by isolation: tenant B's scoped view
        // must show only its own row, never the one written inside tenant
        // A's closure.
        $this->assertSame(['job-machine'], Machine::query()->pluck('name')->all());

        TenantContext::initialize($this->tenantA);
        $this->assertSame(['closure-machine'], Machine::query()->pluck('name')->all());
    }

    public function test_composite_uniques_allow_the_same_value_across_tenants(): void
    {
        $userA = User::factory()->forTenant($this->tenantA)->create();
        TenantContext::initializeFromUser($userA);
        Machine::query()->create(['name' => 'shared-name']);

        $userB = User::factory()->forTenant($this->tenantB)->create();
        TenantContext::initializeFromUser($userB);
        Machine::query()->create(['name' => 'shared-name']);

        $this->assertSame(2, DB::table('machines')->count());
    }

    public function test_composite_uniques_reject_duplicates_within_one_tenant(): void
    {
        $userA = User::factory()->forTenant($this->tenantA)->create();
        TenantContext::initializeFromUser($userA);
        Machine::query()->create(['name' => 'duplicate-name']);

        $this->expectException(QueryException::class);

        Machine::query()->create(['name' => 'duplicate-name']);
    }

    public function test_raw_query_builder_is_outside_the_eloquent_scope_boundary(): void
    {
        // Documented sharp edge: global Eloquent scopes cannot protect raw
        // DB::table() access. Domain code must always go through Eloquent
        // models; this assertion pins the boundary so the convention is
        // impossible to forget silently.
        $this->seedMachines([
            $this->tenantA->getKey() => 'generator-A',
            $this->tenantB->getKey() => 'generator-B',
        ]);

        $userA = User::factory()->forTenant($this->tenantA)->create();
        TenantContext::initializeFromUser($userA);

        $this->assertSame(2, DB::table('machines')->count());
    }
}
