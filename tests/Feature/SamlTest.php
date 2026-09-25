<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Saml2\Provider;
use Tests\Support\FakeIdentityProvider;
use Tests\TestCase;

class SamlTest extends TestCase
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

        // Each request must build its Saml2 driver from scratch — the Socialite
        // manager memoizes driver instances (including their resolved users)
        // across the multiple requests some tests make.
        Socialite::forgetDrivers();

        URL::forceRootUrl(config('app.url'));
    }

    public function test_the_saml2_driver_is_registered_when_socialite_was_called_is_dispatched(): void
    {
        event(app(SocialiteWasCalled::class));

        $this->assertInstanceOf(Provider::class, Socialite::driver('saml2'));
    }

    public function test_the_redirect_route_issues_a_signed_authn_request_to_the_identity_provider(): void
    {
        $response = $this->get(route('saml.redirect'));

        $response->assertRedirect();

        $redirectUrl = (string) $response->headers->get('Location');
        $this->assertStringContainsString(FakeIdentityProvider::SSO_URL, $redirectUrl);

        $authnRequest = FakeIdentityProvider::decodeAuthnRequest($redirectUrl);

        $this->assertStringStartsWith('_', $authnRequest->getID());
        $this->assertSame(FakeIdentityProvider::spEntityId(), $authnRequest->getIssuer()->getValue());
        $this->assertSame(FakeIdentityProvider::acsUrl(), $authnRequest->getAssertionConsumerServiceURL());
    }

    public function test_the_redirect_route_remembers_the_relay_state_in_the_session(): void
    {
        $response = $this->get(route('saml.redirect'));

        $state = session('state');

        $this->assertIsString($state);
        $this->assertNotSame('', $state);
        $this->assertStringContainsString(
            'RelayState='.urlencode($state),
            (string) $response->headers->get('Location'),
        );
    }

    public function test_a_signed_response_completes_login_and_creates_the_user(): void
    {
        $response = $this->withSession(['state' => FakeIdentityProvider::STATE])
            ->get(FakeIdentityProvider::assertionResponseUrl());

        $response->assertRedirect(route('dashboard'));

        $user = User::query()->sole();

        $this->assertSame(FakeIdentityProvider::EMAIL, $user->email);
        $this->assertSame(FakeIdentityProvider::NAME, $user->name);
        $this->assertTrue($this->app['auth']->guard()->user()?->is($user));
    }

    public function test_a_signed_response_logs_an_existing_user_back_in_without_duplicating_the_account(): void
    {
        $user = User::factory()->create(['email' => FakeIdentityProvider::EMAIL]);

        $this->withSession(['state' => FakeIdentityProvider::STATE])
            ->get(FakeIdentityProvider::assertionResponseUrl());

        $this->assertSame(1, User::query()->count());
        $this->assertTrue($this->app['auth']->guard()->user()?->is($user));
    }

    public function test_an_idp_initiated_response_without_sp_initiated_state_is_accepted_as_stateless(): void
    {
        // No "state" in the session: the identity provider started the flow
        // (IdP-initiated SSO), which never carries our relay state.
        $this->get(FakeIdentityProvider::assertionResponseUrl());

        $this->assertTrue($this->app['auth']->guard()->check());
    }

    public function test_a_forged_state_value_is_rejected(): void
    {
        $this->withSession(['state' => 'attacker-crafted-state'])
            ->get(FakeIdentityProvider::assertionResponseUrl());

        $this->assertFalse($this->app['auth']->guard()->check());
    }

    public function test_an_assertion_signed_by_an_unknown_identity_provider_is_rejected(): void
    {
        $this->withSession(['state' => FakeIdentityProvider::STATE])
            ->get(FakeIdentityProvider::assertionResponseUrl(['signed_by' => 'sp']));

        $this->assertFalse($this->app['auth']->guard()->check());
    }

    public function test_an_assertion_from_an_unexpected_issuer_is_rejected(): void
    {
        $this->withSession(['state' => FakeIdentityProvider::STATE])
            ->get(FakeIdentityProvider::assertionResponseUrl([
                'issuer' => 'https://evil.example/metadata',
            ]));

        $this->assertFalse($this->app['auth']->guard()->check());
    }

    public function test_an_unsuccessful_status_is_rejected(): void
    {
        $this->withSession(['state' => FakeIdentityProvider::STATE])
            ->get(FakeIdentityProvider::assertionResponseUrl(['success' => false]));

        $this->assertFalse($this->app['auth']->guard()->check());
    }

    public function test_a_replayed_assertion_is_rejected(): void
    {
        $responseUrl = FakeIdentityProvider::assertionResponseUrl();

        $this->withSession(['state' => FakeIdentityProvider::STATE])->get($responseUrl);

        $this->assertSame(1, User::query()->count());

        // The same assertion a second time must not be accepted again, even
        // with a matching relay state. Forget the memoized Socialite driver
        // instances so the second request is validated from scratch, like a
        // real second HTTP request would be.
        Socialite::forgetDrivers();

        $this->withSession(['state' => FakeIdentityProvider::STATE])
            ->get($responseUrl)
            ->assertRedirect(route('login'));
    }

    public function test_the_email_attribute_identifies_the_local_account(): void
    {
        $this->withSession(['state' => FakeIdentityProvider::STATE])
            ->get(FakeIdentityProvider::assertionResponseUrl([
                'email' => 'other.person@optigate.test',
            ]));

        $this->assertSame(1, User::query()->count());
        $this->assertSame('other.person@optigate.test', User::query()->sole()->email);
    }

    public function test_an_invalid_state_exception_surfaces_as_a_login_error(): void
    {
        $this->withSession(['state' => 'attacker-crafted-state'])
            ->get(FakeIdentityProvider::assertionResponseUrl())
            ->assertRedirect(route('login'));
    }

    public function test_the_service_provider_metadata_route_renders_valid_xml(): void
    {
        $response = $this->get(route('saml.metadata'));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/samlmetadata+xml',
            (string) $response->headers->get('Content-Type'),
        );

        $metadata = (string) $response->getContent();
        $this->assertStringContainsString(FakeIdentityProvider::spEntityId(), $metadata);
        $this->assertStringContainsString(FakeIdentityProvider::acsUrl(), $metadata);
        $this->assertStringContainsString('SingleLogoutService', $metadata);
    }

    public function test_an_idp_logout_request_terminates_the_local_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(FakeIdentityProvider::logoutRequestUrl());

        $this->assertFalse($this->app['auth']->guard()->check());
    }

    public function test_a_login_error_lands_back_on_the_login_screen_with_a_message(): void
    {
        $response = $this->withSession(['state' => FakeIdentityProvider::STATE])
            ->get(FakeIdentityProvider::assertionResponseUrl(['success' => false]))
            ->assertRedirect(route('login'));

        $response->assertSessionHas('errors');
    }
}
