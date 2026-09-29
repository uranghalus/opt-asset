<?php

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\TenantSwitch;
use App\Models\User;
use App\Tenancy\Facades\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Multi-membership + tenant switcher.
 *
 * Decisions (grill 2026-09-26, carried through the 2026-09-27 rebuild): one
 * SSO account may belong to several tenants via tenant_memberships; the
 * active tenant lives in the session; roles will live per membership (T02);
 * every switch is audited.
 */
class TenantMembershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Allowlist for platform-admin tests; individual tests override.
        config(['platform.admin_emails' => ['boss@optigate.test']]);
    }

    public function test_a_user_can_belong_to_multiple_tenants(): void
    {
        $user = User::factory()->create();
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $user->memberships()->create(['tenant_id' => $tenantA->id, 'is_default' => true]);
        $user->memberships()->create(['tenant_id' => $tenantB->id]);

        $this->assertSame(2, $user->memberships()->count());
        $this->assertTrue($user->defaultMembership()->is($user->memberships()->where('is_default', true)->first()));
    }

    public function test_memberships_are_cross_tenant_readable_by_design(): void
    {
        $user = User::factory()->create();
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $user->memberships()->create(['tenant_id' => $tenantA->id, 'is_default' => true]);
        $user->memberships()->create(['tenant_id' => $tenantB->id]);

        // Acting inside tenant A's context, the user's membership list must
        // still contain BOTH tenants — the pivot is deliberately not
        // tenant-scoped, otherwise switching would be impossible.
        TenantContext::initialize($tenantA);

        $this->assertSame(
            [$tenantA->id, $tenantB->id],
            $user->tenants()->pluck('tenants.id')->all(),
        );

        TenantContext::end();
    }

    public function test_a_member_can_switch_to_another_tenant_they_belong_to(): void
    {
        $user = User::factory()->create();
        $tenantA = Tenant::factory()->create(['code' => 'DMB']);
        $tenantB = Tenant::factory()->create(['code' => 'DMP']);

        $user->memberships()->create(['tenant_id' => $tenantA->id, 'is_default' => true]);
        $user->memberships()->create(['tenant_id' => $tenantB->id]);

        $response = $this->actingAs($user)
            ->post(route('tenant.switch'), ['tenant_id' => $tenantB->id]);

        $response->assertRedirect();

        // Session now holds the new active tenant...
        $this->assertSame($tenantB->id, session('tenant.active_id'));

        // ...the audit trail records who switched from where to where...
        $switch = TenantSwitch::query()->sole();
        $this->assertSame($user->id, $switch->user_id);
        $this->assertSame($tenantA->id, $switch->from_tenant_id);
        $this->assertSame($tenantB->id, $switch->to_tenant_id);

        // ...and the next request acts inside tenant B.
        $this->actingAs($user)->get(route('dashboard'));
        $this->assertSame($tenantB->id, TenantContext::id());
    }

    public function test_switching_to_a_tenant_without_membership_is_rejected(): void
    {
        $user = User::factory()->create();
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $user->memberships()->create(['tenant_id' => $tenantA->id, 'is_default' => true]);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('tenant.switch'), ['tenant_id' => $tenantB->id])
            ->assertForbidden();

        $this->assertNull(session('tenant.active_id'));
        $this->assertSame(0, TenantSwitch::query()->count());
    }

    public function test_switching_to_a_suspended_tenant_is_rejected_even_with_membership(): void
    {
        $user = User::factory()->create();
        $tenantA = Tenant::factory()->create();
        $suspended = Tenant::factory()->suspended()->create();

        $user->memberships()->create(['tenant_id' => $tenantA->id, 'is_default' => true]);
        $user->memberships()->create(['tenant_id' => $suspended->id]);

        $this->actingAs($user)
            ->post(route('tenant.switch'), ['tenant_id' => $suspended->id])
            ->assertForbidden();

        $this->assertNull(session('tenant.active_id'));
    }

    public function test_middleware_prefers_the_session_membership_over_the_default(): void
    {
        $user = User::factory()->create();
        $default = Tenant::factory()->create(['code' => 'DEFAULT']);
        $other = Tenant::factory()->create(['code' => 'OTHER']);

        $user->memberships()->create(['tenant_id' => $default->id, 'is_default' => true]);
        $user->memberships()->create(['tenant_id' => $other->id]);

        session(['tenant.active_id' => $other->id]);

        $this->actingAs($user)->get(route('dashboard'));

        $this->assertSame($other->id, TenantContext::id());
    }

    public function test_middleware_falls_back_to_the_default_membership_without_a_session(): void
    {
        $user = User::factory()->create();
        $default = Tenant::factory()->create(['code' => 'DEFAULT']);
        $other = Tenant::factory()->create();

        $user->memberships()->create(['tenant_id' => $default->id, 'is_default' => true]);
        $user->memberships()->create(['tenant_id' => $other->id]);

        $this->actingAs($user)->get(route('dashboard'));

        $this->assertSame($default->id, TenantContext::id());
    }

    public function test_a_stale_session_tenant_self_heals_to_the_default(): void
    {
        $user = User::factory()->create();
        $default = Tenant::factory()->create(['code' => 'DEFAULT']);

        $user->memberships()->create(['tenant_id' => $default->id, 'is_default' => true]);

        // Points at a tenant the user has no membership on anymore.
        session(['tenant.active_id' => Tenant::factory()->create()->id]);

        $this->actingAs($user)->get(route('dashboard'));

        $this->assertSame($default->id, TenantContext::id());
    }

    public function test_a_user_without_any_membership_is_denied_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();

        $this->assertFalse(TenantContext::check());
    }

    public function test_an_active_membership_on_a_suspended_tenant_is_denied(): void
    {
        $user = User::factory()->create();
        $suspended = Tenant::factory()->suspended()->create();

        $user->memberships()->create(['tenant_id' => $suspended->id, 'is_default' => true]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();

        $this->assertFalse(TenantContext::check());
    }

    public function test_platform_access_follows_the_single_gate(): void
    {
        // The gate (config platform.gate) grants via the superadmin flag OR
        // the environment fallback grant. A superadmin reaches the platform
        // area WITH memberships too.
        $admin = User::factory()->create(['email' => 'boss@optigate.test']);
        $admin->promoteIfAllowlisted();
        $admin->memberships()->create([
            'tenant_id' => Tenant::factory()->create()->id,
            'is_default' => true,
        ]);
        $this->actingAs($admin)
            ->get(route('platform.business-units.index'))
            ->assertOk();

        // The environment fallback grant reaches the platform area even
        // without the persisted flag (fresh-install bootstrap path).
        $fallback = User::factory()->create(['email' => 'fallback@optigate.test']);
        config(['platform.admin_emails' => ['fallback@optigate.test']]);
        $this->actingAs($fallback->refresh())
            ->get(route('platform.business-units.index'))
            ->assertOk();

        // Nobody with a grant may act in a tenant context they do not own:
        // a membership-less admin is denied the dashboard surface.
        $contextless = User::factory()->create(['email' => 'fresh@optigate.test']);
        $this->actingAs($contextless)
            ->get(route('dashboard'))
            ->assertForbidden();

        // NOT allowlisted, never promoted → the JIT hole stays closed.
        $stranger = User::factory()->create();
        $this->actingAs($stranger)
            ->get(route('platform.business-units.index'))
            ->assertNotFound();
    }

    public function test_guests_cannot_switch_tenants(): void
    {
        $this->post(route('tenant.switch'), ['tenant_id' => 'x'])
            ->assertRedirect(route('saml.redirect'));
    }
}
