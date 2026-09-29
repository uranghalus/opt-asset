<?php

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\TenantSwitch;
use App\Models\User;
use App\Tenancy\Facades\TenantContext;
use App\Tenancy\TenantContext as TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Superadmin access + universal tenant switcher.
 *
 * Decisions (grill 2026-09-26, carried through the 2026-09-27 rebuild):
 * superuser status is a database flag (users.is_superadmin), seeded from
 * PLATFORM_ADMIN_EMAILS at SAML login — grant/revoke lives in data, not
 * config. Superadmin keeps tenant memberships AND reaches platform CRUD;
 * the switcher offers ALL active tenants to a superadmin and
 * memberships-only to regular users. The security boundary is unchanged:
 * all data access still happens inside a tenant context (switch), never as
 * unscoped cross-tenant queries.
 */
class SuperadminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['platform.admin_emails' => ['boss@optigate.test']]);
    }

    public function test_allowlisted_emails_are_promoted_to_superadmin_at_saml_login(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'email' => 'boss@optigate.test',
            'saml_name_id' => null,
        ]);
        $user->memberships()->create(['tenant_id' => $tenant->id, 'is_default' => true]);

        // Simulates the JIT path executed during SAML login.
        $user->promoteIfAllowlisted();
        $user->refresh();

        $this->assertTrue($user->is_superadmin);
        $this->assertTrue($user->fresh()->isPlatformAdmin());

        // A membership-holding superadmin reaches the platform area again.
        $this->actingAs($user)
            ->get(route('platform.business-units.index'))
            ->assertOk();
    }

    public function test_non_allowlisted_jit_users_stay_regular_users(): void
    {
        $user = User::factory()->create();
        $user->promoteIfAllowlisted();

        $this->assertFalse($user->fresh()->is_superadmin);
        $this->assertFalse($user->isPlatformAdmin());

        $this->actingAs($user)
            ->get(route('platform.business-units.index'))
            ->assertNotFound();
    }

    public function test_superadmin_switches_into_any_active_tenant_without_membership(): void
    {
        $super = User::factory()->create(['email' => 'boss@optigate.test']);
        $super->promoteIfAllowlisted();

        $memberTenant = Tenant::factory()->create(['code' => 'MEMBER']);
        $foreignTenant = Tenant::factory()->create(['code' => 'FOREIGN']);

        $super->memberships()->create(['tenant_id' => $memberTenant->id, 'is_default' => true]);

        $this->actingAs($super)
            ->post(route('tenant.switch'), ['tenant_id' => $foreignTenant->id])
            ->assertRedirect();

        $this->assertSame($foreignTenant->id, session(TenantContextService::SESSION_KEY));

        $switch = TenantSwitch::query()->sole();
        $this->assertSame($super->id, $switch->user_id);
        $this->assertSame($memberTenant->id, $switch->from_tenant_id);
        $this->assertSame($foreignTenant->id, $switch->to_tenant_id);
    }

    public function test_superadmin_cannot_switch_into_a_suspended_tenant(): void
    {
        $super = User::factory()->create(['email' => 'boss@optigate.test']);
        $super->promoteIfAllowlisted();

        $suspended = Tenant::factory()->suspended()->create();

        $this->actingAs($super)
            ->post(route('tenant.switch'), ['tenant_id' => $suspended->id])
            ->assertForbidden();

        $this->assertSame(0, TenantSwitch::query()->count());
    }

    public function test_superadmin_session_pointer_resolves_without_membership(): void
    {
        $super = User::factory()->create(['email' => 'boss@optigate.test']);
        $super->promoteIfAllowlisted();

        $anyTenant = Tenant::factory()->create(['code' => 'ANY']);

        session([TenantContextService::SESSION_KEY => $anyTenant->id]);

        $this->actingAs($super)->get(route('dashboard'));

        $this->assertSame($anyTenant->id, TenantContext::id());
    }

    public function test_regular_user_cannot_switch_without_membership(): void
    {
        $user = User::factory()->create();
        $theirTenant = Tenant::factory()->create();
        $other = Tenant::factory()->create();

        $user->memberships()->create(['tenant_id' => $theirTenant->id, 'is_default' => true]);

        $this->actingAs($user)
            ->post(route('tenant.switch'), ['tenant_id' => $other->id])
            ->assertForbidden();
    }

    public function test_the_switcher_offers_all_active_tenants_to_a_superadmin(): void
    {
        $super = User::factory()->create(['email' => 'boss@optigate.test']);
        $super->promoteIfAllowlisted();

        $active = Tenant::factory()->create(['name' => 'Active Co']);
        Tenant::factory()->suspended()->create();
        $super->memberships()->create(['tenant_id' => $active->id, 'is_default' => true]);

        TenantContext::initialize($active);

        $page = $this->actingAs($super)->get(route('dashboard'));

        $page->assertOk()->assertInertia(fn ($inertia) => $inertia
            ->where('tenancy.switchable.0.name', 'Active Co')
            ->has('tenancy.switchable', 1));
    }

    public function test_the_switcher_offers_memberships_only_to_regular_users(): void
    {
        $user = User::factory()->create();
        $theirs = Tenant::factory()->create(['name' => 'Theirs']);
        Tenant::factory()->create(['name' => 'Not Theirs']);

        $user->memberships()->create(['tenant_id' => $theirs->id, 'is_default' => true]);

        $page = $this->actingAs($user)->get(route('dashboard'));

        $page->assertOk()->assertInertia(fn ($inertia) => $inertia
            ->where('tenancy.switchable.0.name', 'Theirs')
            ->has('tenancy.switchable', 1));
    }
}
