<?php

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\Facades\TenantContext;
use App\Tenancy\TenantContext as TenantContextService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\FakeIdentityProvider;
use Tests\TestCase;

/**
 * "Upon login, the tenant ID must be set within the user data."
 *
 * Drives the REAL SAML flow through the fake identity provider and proves
 * the post-login settlement: the resolved tenant id is pinned into the
 * session's user data (session tenant.active_id), allowlisted emails are
 * promoted, and membership-less platform admins are attached to the first
 * active tenant so the dashboard surface can resolve a context.
 */
class LoginTenantPinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.saml2', [
            'metadata' => FakeIdentityProvider::metadataXml(),
            'sp_entityid' => FakeIdentityProvider::spEntityId(),
            'sp_acs' => 'saml/acs',
            'sp_sls' => 'saml/sls',
        ]);

        Socialite::forgetDrivers();

        URL::forceRootUrl(config('app.url'));

        config(['platform.admin_emails' => ['superadmin@appdutamall.com']]);

        // RBAC (T02): JIT landing-role provisioning runs on every SAML login
        // and fail-closes when the global `default` template is missing —
        // seed it so this suite exercises login settlement, not RBAC setup.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RbacSeeder::class);
    }

    public function test_login_pins_the_resolved_tenant_id_into_the_user_data(): void
    {
        $tenant = Tenant::factory()->create(['code' => 'DMB']);

        $user = User::factory()->create([
            'email' => 'superadmin@appdutamall.com',
            'saml_name_id' => null,
        ]);
        $user->memberships()->create(['tenant_id' => $tenant->id, 'is_default' => true]);

        $this->withSession(['state' => FakeIdentityProvider::STATE])
            ->get(FakeIdentityProvider::assertionResponseUrl([
                'email' => 'superadmin@appdutamall.com',
            ]))
            ->assertRedirect();

        $this->assertTrue($this->app['auth']->guard()->check());

        // The tenant ID is set within the user data at login: the session
        // pointer now names the resolved default membership.
        $this->assertSame($tenant->getKey(), session(TenantContextService::SESSION_KEY));

        // Allowlisted email promoted at login (env fallback grant → flag).
        $this->assertTrue($user->fresh()->is_superadmin);

        // The next request acts inside the pinned tenant...
        $this->get(route('dashboard'))->assertOk();
        $this->assertSame($tenant->getKey(), TenantContext::id());

        // ...and the Inertia auth payload carries the tenant id.
        $this->get(route('dashboard'))->assertInertia(fn ($inertia) => $inertia
            ->where('auth.tenant_id', $tenant->getKey()));
    }

    public function test_a_membership_less_bootstrap_admin_is_attached_to_the_first_tenant_at_login(): void
    {
        $first = Tenant::factory()->create(['code' => 'AAA-FIRST', 'name' => 'Alpha']);
        Tenant::factory()->create(['code' => 'BBB-SECOND', 'name' => 'Beta']);

        // The account exists but was never attached to anything (fresh
        // install: no tenants existed when the user was provisioned, the
        // first tenant was created afterwards through the platform area).
        User::factory()->create(['email' => 'superadmin@appdutamall.com']);

        $this->withSession(['state' => FakeIdentityProvider::STATE])
            ->get(FakeIdentityProvider::assertionResponseUrl([
                'email' => 'superadmin@appdutamall.com',
            ]));

        $user = User::query()->where('email', 'superadmin@appdutamall.com')->sole();

        $this->assertTrue($user->is_superadmin);
        $this->assertSame(1, $user->memberships()->count());
        $this->assertTrue($user->defaultMembership()?->tenant->is($first));

        // Pinned into the user data at login.
        $this->assertSame($first->getKey(), session(TenantContextService::SESSION_KEY));
    }

    public function test_a_regular_member_login_pins_their_default_membership(): void
    {
        $tenant = Tenant::factory()->create();
        $fallback = Tenant::factory()->create();

        $user = User::factory()->create(['email' => 'member@optigate.test']);
        $user->memberships()->create(['tenant_id' => $tenant->id, 'is_default' => true]);

        $this->withSession(['state' => FakeIdentityProvider::STATE])
            ->get(FakeIdentityProvider::assertionResponseUrl(['email' => 'member@optigate.test']));

        // Non-allowlisted users keep regular status...
        $this->assertFalse($user->fresh()->is_superadmin);

        // ...and their default membership is pinned as the acting tenant.
        $this->assertSame($tenant->getKey(), session(TenantContextService::SESSION_KEY));

        // Sanity: the unattached tenant was never chosen.
        $this->assertNotSame($fallback->getKey(), session(TenantContextService::SESSION_KEY));
    }
}
