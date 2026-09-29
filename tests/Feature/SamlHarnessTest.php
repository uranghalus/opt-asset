<?php

namespace Tests\Feature;

use LightSaml\Error\LightSamlSecurityException;
use LightSaml\Model\Protocol\Response;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use Tests\Support\FakeIdentityProvider;
use Tests\TestCase;

/**
 * Self-test for the fake identity provider harness: proves the generated
 * SAML messages are structurally valid and carry signatures that verify
 * (or reject) exactly like a real identity provider's would.
 *
 * Signature checks go through LightSaml's SignatureXmlReader — the same
 * code path the Socialite Saml2 provider uses in production.
 */
class SamlHarnessTest extends TestCase
{
    public function test_generated_response_deserializes_with_expected_content(): void
    {
        $response = FakeIdentityProvider::decodeAssertionResponse(
            FakeIdentityProvider::assertionResponseUrl(),
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(FakeIdentityProvider::ENTITY_ID, $response->getIssuer()->getValue());

        $assertions = $response->getAllAssertions();
        $this->assertCount(1, $assertions);
        $this->assertSame(
            FakeIdentityProvider::EMAIL,
            $assertions[0]->getSubject()->getNameID()->getValue(),
        );
    }

    public function test_generated_response_signature_verifies_against_the_identity_provider_certificate(): void
    {
        $this->assertTrue($this->validateResponseSignature(
            [],
            FakeIdentityProvider::fixture('idp_saml.crt'),
        ));
    }

    public function test_generated_response_signature_is_rejected_against_the_service_provider_certificate(): void
    {
        $this->assertFalse($this->validateResponseSignature(
            [],
            FakeIdentityProvider::fixture('sp_saml.crt'),
        ));
    }

    public function test_response_signed_by_another_key_is_rejected_against_the_identity_provider_certificate(): void
    {
        $this->assertFalse($this->validateResponseSignature(
            ['signed_by' => 'sp'],
            FakeIdentityProvider::fixture('idp_saml.crt'),
        ));
    }

    public function test_identity_provider_metadata_publishes_certificate_and_endpoints(): void
    {
        $metadata = FakeIdentityProvider::metadataXml();

        $certificateBody = str_replace(
            ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n"],
            '',
            FakeIdentityProvider::fixture('idp_saml.crt'),
        );

        $this->assertStringContainsString($certificateBody, $metadata);
        $this->assertStringContainsString(FakeIdentityProvider::SSO_URL, $metadata);
        $this->assertStringContainsString(FakeIdentityProvider::SLO_URL, $metadata);
        $this->assertStringContainsString(FakeIdentityProvider::ENTITY_ID, $metadata);
    }

    /**
     * Validate a generated assertion response's signature against the given
     * PEM certificate, returning false for any validation failure.
     *
     * @param  array{signed_by?: 'idp'|'sp'}  $options
     */
    private function validateResponseSignature(array $options, string $certificate): bool
    {
        $response = FakeIdentityProvider::decodeAssertionResponse(
            FakeIdentityProvider::assertionResponseUrl($options),
        );

        $signature = $response->getSignature();
        $this->assertNotNull($signature);

        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'public']);
        $key->loadKey($certificate, false, true);

        try {
            return $signature->validate($key);
        } catch (LightSamlSecurityException) {
            return false;
        }
    }
}
