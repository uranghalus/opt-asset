<?php

namespace Tests\Feature\Platform;

use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\Facades\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Platform business unit CRUD.
 *
 * The platform surface is central context by definition: tenancy is never
 * initialized here. The gate is config/platform.php's single authority: a
 * persisted superadmin flag OR the environment fallback grant
 * (PLATFORM_ADMIN_EMAILS) — the fallback is what lets a fresh install
 * create the first business unit before any SAML login (grill 2026-09-27).
 */
class BusinessUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['platform.admin_emails' => ['boss@optigate.test']]);
    }

    /**
     * A platform admin: promoted superadmin flag. Idempotent — tests may
     * call this several times in one test.
     */
    protected function platformAdmin(): User
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'boss@optigate.test'],
            array_merge(User::factory()->definition(), [
                'email' => 'boss@optigate.test',
            ]),
        );
        $admin->promoteIfAllowlisted();

        return $admin->refresh();
    }

    public function test_bootstrap_accounts_reach_the_business_unit_index(): void
    {
        $this->actingAs($this->platformAdmin())
            ->get(route('platform.business-units.index'))
            ->assertOk();
    }

    public function test_the_env_fallback_grant_reaches_the_surface_before_any_login(): void
    {
        // Fresh-install path: the allowlisted account exists (JIT or seed)
        // but was NEVER promoted — the environment grant alone must open
        // the surface so the first business unit can be created.
        $bootstrap = User::factory()->create(['email' => 'superadmin@appdutamall.com']);
        config(['platform.admin_emails' => ['superadmin@appdutamall.com']]);

        $this->actingAs($bootstrap)
            ->get(route('platform.business-units.index'))
            ->assertOk();

        $this->actingAs($bootstrap)
            ->post(route('platform.business-units.store'), [
                'code' => 'DMB',
                'name' => 'Duta Mall Branch',
            ])->assertRedirect(route('platform.business-units.index'));

        $this->assertSame(1, Tenant::query()->count());
    }

    public function test_tenant_users_get_no_evidence_of_the_platform_area(): void
    {
        $tenantUser = User::factory()->forTenant()->create();

        $this->actingAs($tenantUser)
            ->get(route('platform.business-units.index'))
            ->assertNotFound();
    }

    public function test_guests_are_redirected_to_sso(): void
    {
        $this->get(route('platform.business-units.index'))
            ->assertRedirect(route('saml.redirect'));
    }

    public function test_tenancy_is_never_initialized_on_the_platform_surface(): void
    {
        $this->actingAs($this->platformAdmin())
            ->get(route('platform.business-units.index'));

        $this->assertFalse(TenantContext::check());
    }

    public function test_index_lists_business_units_paginated_with_server_side_filters(): void
    {
        Tenant::factory()->count(12)->create();

        $page = $this->actingAs($this->platformAdmin())
            ->get(route('platform.business-units.index', ['page' => 2]));

        $page->assertOk()
            ->assertInertia(fn (Assert $inertia) => $inertia
                ->component('platform/business-units/index')
                ->has('businessUnits.data', 2)
                ->where('businessUnits.total', 12));

        $filtered = $this->actingAs($this->platformAdmin())
            ->get(route('platform.business-units.index', ['search' => 'nonexistent']));

        $filtered->assertOk()
            ->assertInertia(fn (Assert $inertia) => $inertia
                ->has('businessUnits.data', 0));
    }

    public function test_search_matches_code_and_name(): void
    {
        $matching = Tenant::factory()->create([
            'code' => 'HO-2026',
            'name' => 'PT Head Office 2026',
        ]);
        Tenant::factory()->create();

        $byCode = $this->actingAs($this->platformAdmin())
            ->get(route('platform.business-units.index', ['search' => 'ho-20']));

        $byCode->assertOk()
            ->assertInertia(fn (Assert $inertia) => $inertia
                ->has('businessUnits.data', 1)
                ->where('businessUnits.data.0.id', $matching->getKey()));

        $byName = $this->actingAs($this->platformAdmin())
            ->get(route('platform.business-units.index', ['search' => 'head office']));

        $byName->assertOk()
            ->assertInertia(fn (Assert $inertia) => $inertia
                ->has('businessUnits.data', 1)
                ->where('businessUnits.data.0.id', $matching->getKey()));
    }

    public function test_a_platform_admin_can_create_a_business_unit(): void
    {
        $response = $this->actingAs($this->platformAdmin())
            ->post(route('platform.business-units.store'), [
                'code' => 'HO-2026',
                'name' => 'PT Head Office',
            ]);

        $response->assertRedirect(route('platform.business-units.index'));

        $tenant = Tenant::query()->where('code', 'HO-2026')->sole();
        $this->assertSame('PT Head Office', $tenant->name);
        $this->assertSame('active', $tenant->status);
    }

    public function test_business_unit_code_must_be_unique_and_url_safe(): void
    {
        Tenant::factory()->create(['code' => 'HO-2026']);

        $this->actingAs($this->platformAdmin())
            ->post(route('platform.business-units.store'), [
                'code' => 'HO-2026',
                'name' => 'Duplicate',
            ])->assertSessionHasErrors(['code']);

        $this->actingAs($this->platformAdmin())
            ->post(route('platform.business-units.store'), [
                'code' => 'bad code!',
                'name' => 'Unsafe',
            ])->assertSessionHasErrors(['code']);

        $this->assertSame(1, Tenant::query()->count());
    }

    public function test_a_platform_admin_can_edit_a_business_unit(): void
    {
        $tenant = Tenant::factory()->create(['code' => 'HO-2026']);

        $this->actingAs($this->platformAdmin())
            ->put(route('platform.business-units.update', $tenant), [
                'code' => 'HO-2027',
                'name' => 'PT Renamed',
            ])->assertRedirect(route('platform.business-units.index'));

        $this->assertSame('HO-2027', $tenant->refresh()->code);
        $this->assertSame('PT Renamed', $tenant->name);
    }

    public function test_edit_rejects_a_code_taken_by_another_business_unit(): void
    {
        $tenant = Tenant::factory()->create(['code' => 'HO-2026']);
        Tenant::factory()->create(['code' => 'BR-01']);

        $this->actingAs($this->platformAdmin())
            ->put(route('platform.business-units.update', $tenant), [
                'code' => 'BR-01',
                'name' => 'Conflicter',
            ])->assertSessionHasErrors(['code']);

        $this->assertSame('HO-2026', $tenant->refresh()->code);
    }

    public function test_status_transitions_are_restricted_to_known_statuses(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);

        $this->actingAs($this->platformAdmin())
            ->patch(route('platform.business-units.transition', $tenant), [
                'status' => 'suspended',
            ])->assertRedirect();

        $this->assertSame('suspended', $tenant->refresh()->status);

        // Unknown statuses are rejected by validation.
        $this->actingAs($this->platformAdmin())
            ->patch(route('platform.business-units.transition', $tenant), [
                'status' => 'deleted',
            ])->assertSessionHasErrors(['status']);

        $this->assertSame('suspended', $tenant->refresh()->status);
    }

    public function test_suspending_a_business_unit_fail_closes_its_users_immediately(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();
        $this->actingAs($user);

        $this->get(route('dashboard'))->assertOk();

        $this->actingAs($this->platformAdmin())
            ->patch(route('platform.business-units.transition', $tenant), [
                'status' => 'suspended',
            ]);

        // Fresh instance: a real second HTTP request would not carry the
        // tenant relation cached from the first one.
        $user->refresh();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();

        $this->assertFalse(TenantContext::check());
    }

    public function test_the_create_page_is_displayed(): void
    {
        $this->actingAs($this->platformAdmin())
            ->get(route('platform.business-units.create'))
            ->assertOk();
    }

    public function test_the_edit_page_is_displayed_with_the_business_unit(): void
    {
        $tenant = Tenant::factory()->create(['code' => 'HO-2026']);

        $page = $this->actingAs($this->platformAdmin())
            ->get(route('platform.business-units.edit', $tenant));

        $page->assertOk()
            ->assertInertia(fn (Assert $inertia) => $inertia
                ->component('platform/business-units/edit')
                ->where('businessUnit.code', 'HO-2026'));
    }

    public function test_validation_errors_redirect_back_for_inertia(): void
    {
        $this->actingAs($this->platformAdmin())
            ->from(route('platform.business-units.index'))
            ->post(route('platform.business-units.store'), [
                'code' => '',
                'name' => '',
            ])->assertRedirect(route('platform.business-units.index'))
            ->assertSessionHasErrors(['code', 'name']);
    }
}
