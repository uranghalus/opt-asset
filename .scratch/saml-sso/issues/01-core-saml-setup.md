# 01: Core SAML Setup

**What to build:** Install the required SAML provider, configure the service definitions in `config/services.php`, and bind the Socialite listener in Laravel.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] Install `socialiteproviders/saml2`.
- [ ] Migrate `saml2` config structure from `opt-work` to `opt-asset`.
- [ ] Bind `\SocialiteProviders\Saml2\Saml2ExtendSocialite@handle` on `\SocialiteProviders\Manager\SocialiteWasCalled::class`.
