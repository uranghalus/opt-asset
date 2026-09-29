<?php

namespace Tests\Feature\Rbac;

use App\Enums\Permission;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\FakeIdentityProvider;
use Tests\TestCase;

/**
 * JIT provisioning (T02): the SAML login path assigns the tenant's
 * `default` view-only role to every active membership that has no role yet,
 * persists the SAML NameID, and leaves existing role assignments untouched.
 */
class RbacJitTest extends TestCase
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

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(RbacSeeder::class);
    }

    public function test_a_jit_login_assigns_the_tenant_default_role_to_a_membership_without_one(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->forTenant($tenant)->create(['email' => FakeIdentityProvider::EMAIL]);

        $this->withSession(['state' => FakeIdentityProvider::STATE])
            ->get(FakeIdentityProvider::assertionResponseUrl());

        $user = User::query()->where('email', FakeIdentityProvider::EMAIL)->sole();

        setPermissionsTeamId($tenant->getKey());
        $user->unsetRelation('roles');

        $this->assertTrue($user->hasRole('default'));
        $this->assertSame([Permission::AssetsView->value], $user->roles->first()->permissions->pluck('name')->all());
    }

    public function test_the_default_role_is_view_only(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->forTenant($tenant)->create(['email' => FakeIdentityProvider::EMAIL]);

        $this->withSession(['state' => FakeIdentityProvider::STATE])
            ->get(FakeIdentityProvider::assertionResponseUrl());

        $user = User::query()->where('email', FakeIdentityProvider::EMAIL)->sole();

        setPermissionsTeamId($tenant->getKey());
        $user->unsetRelation('roles')->unsetRelation('permissions');

        $this->assertTrue($user->can('assets.view'));
        $this->assertFalse($user->can('assets.create'));
        $this->assertFalse($user->can('classifications.manage'));
    }

    public function test_a_membership_with_an_existing_role_is_not_overwritten(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create(['email' => FakeIdentityProvider::EMAIL]);

        $adminRole = Role::create(['name' => 'Admin Tenant', 'tenant_id' => $tenant->getKey(), 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::values());
        setPermissionsTeamId($tenant->getKey());
        $user->assignRole($adminRole);

        $this->withSession(['state' => FakeIdentityProvider::STATE])
            ->get(FakeIdentityProvider::assertionResponseUrl());

        $user = User::query()->where('email', FakeIdentityProvider::EMAIL)->sole();

        setPermissionsTeamId($tenant->getKey());
        $user->unsetRelation('roles');

        $this->assertTrue($user->hasRole('Admin Tenant'));
        $this->assertFalse($user->hasRole('default'));
    }

    public function test_a_suspended_tenant_membership_gets_no_role(): void
    {
        $tenant = Tenant::factory()->suspended()->create();
        User::factory()->forTenant($tenant)->create(['email' => FakeIdentityProvider::EMAIL]);

        $this->withSession(['state' => FakeIdentityProvider::STATE])
            ->get(FakeIdentityProvider::assertionResponseUrl());

        $user = User::query()->where('email', FakeIdentityProvider::EMAIL)->sole();

        setPermissionsTeamId($tenant->getKey());
        $user->unsetRelation('roles');

        $this->assertFalse($user->hasRole('default'));
        $this->assertSame(
            0,
            Role::query()->where('name', 'default')->where('tenant_id', $tenant->getKey())->count(),
            'No clone may be created for a suspended tenant.',
        );
    }

    public function test_the_saml_name_id_is_persisted_when_the_account_is_created(): void
    {
        $responseUrl = FakeIdentityProvider::assertionResponseUrl();

        $this->withSession(['state' => FakeIdentityProvider::STATE])->get($responseUrl);

        $user = User::query()->sole();

        $this->assertNotNull($user->saml_name_id);

        $assertion = FakeIdentityProvider::decodeAssertionResponse($responseUrl);
        $this->assertSame($assertion->getAllAssertions()[0]->getSubject()->getNameID()->getValue(), $user->saml_name_id);
    }

    public function test_the_saml_name_id_is_relaid_when_the_identity_provider_sends_a_new_one(): void
    {
        User::factory()->forTenant()->create([
            'email' => FakeIdentityProvider::EMAIL,
            'saml_name_id' => 'stale-name-id',
        ]);

        $this->withSession(['state' => FakeIdentityProvider::STATE])
            ->get(FakeIdentityProvider::assertionResponseUrl());

        $user = User::query()->where('email', FakeIdentityProvider::EMAIL)->sole();

        $this->assertNotSame('stale-name-id', $user->saml_name_id);
    }
}
