# Research: SAML SSO via Socialite Saml2 (source: opt-work)

**Date:** 2026-09-24
**Question:** How is SAML login implemented in `D:\Laravel Project\opt-work`, and what does porting it into opt-asset (replacing the Fortify starter auth) require?

**Status: implemented** on branch `feature/saml-sso` — epic #1, tickets #2–#7 (2026-09-25).

- Deps/config/provider listener (#2), fake-IdP test harness ported Pest → PHPUnit (#3),
  `SamlController` + routes + CSRF exception scoped to `saml/acs` + 16-test security suite (#4),
  `/` guest → SSO redirect and welcome/local auth page retirement (#5),
  full Fortify removal with local-only `POST /logout` (#6), env/docs/final verification (#7).
- Deviations from this research: user-initiated logout stays local-session-only (the provider's
  `logoutResponse()` needs an incoming IdP `LogoutRequest`); `SAML_SP_ENTITYID` is derived from
  `APP_URL` so each deployed app registers a distinct entity ID with the IdP.

## Sources

- `D:\Laravel Project\opt-work` — working SAML implementation (composer.json, app/, config/, routes/, tests/)
- `D:\Laravel Project\opt-asset` — current repo state (Fortify starter kit auth)
- Official package docs: https://socialiteproviders.com/Saml2/ (config keys, bindings, stateless, SLO, signing)
- Laravel Socialite docs: https://laravel.com/framework/docs/socialite

## 1. opt-work's implementation

**Stack:** `laravel/socialite ^5.31` + `socialiteproviders/saml2 ^4.10` (which wraps `lightsaml/lightsaml`). Fortify remains installed but reduced.

### Provider registration — `app/Providers/AppServiceProvider.php`

```php
Event::listen(function (SocialiteWasCalled $event) {
    $event->extendSocialite('saml2', Provider::class);
});
```

This matches the official docs' Laravel 11+ listener pattern.

### IdP configuration — `config/services.php` (`saml2` key)

| Key                         | Value                                        | Meaning (per official docs)                                                            |
| --------------------------- | -------------------------------------------- | -------------------------------------------------------------------------------------- |
| `metadata`                  | `null`                                       | Manual IdP config; avoids runtime HTTP fetch of metadata (SSL issue noted in comments) |
| `entityid`                  | `SAML_IDP_ENTITYID`                          | IdP's globally unique Entity ID                                                        |
| `certificate`               | `SAML_X509_CERT`                             | IdP's assertion-signing certificate (body, no headers)                                 |
| `acs`                       | `SAML_SSO_URL`                               | IdP's Single Sign-On URL (the docs call this key `acs`)                                |
| `slo`                       | `SAML_SLO_URL`                               | IdP's Single Logout URL (required when configured manually)                            |
| `sp_entityid`               | `SAML_SP_ENTITYID`                           | This app's entity ID — **must match the IdP portal registration exactly**              |
| `sp_acs`                    | `saml/acs`                                   | Local ACS path                                                                         |
| `sp_sls`                    | `saml/logout`                                | Local SLS path                                                                         |
| `sp_default_binding_method` | `SamlConstants::BINDING_SAML2_HTTP_REDIRECT` | Initiates with HTTP-Redirect                                                           |

### Controller — `app/Http/Controllers/SamlController.php`

Four actions:

- `redirect()` — SP-initiated SSO: `Socialite::driver('saml2')->redirect()`.
- `acs()` — consumes the assertion. Key design points:
    - Decides **before** resolving the user whether the flow is SP-initiated (`session('state')` present → stateful with relay-state validation) or IdP-initiated (no state → `$driver->stateless()->user()`). The provider consumes the relay state during validation, so this check must happen first.
    - A stale `state` key is forgotten after login so an abandoned SP-initiated flow can't make a later IdP-initiated response look forged.
    - `loginUser()`: `firstOrNew(['email' => ...])`; new users get `forceFill(name, email, password = Hash::make(Str::random(64)), email_verified_at = now())` — an unguessable password keeps the column satisfied while making password login impossible. Then `Auth::login($user)`.
    - `resolveEmail()` has three fallback strategies: mapped attribute email → NameID if it validates as email → brute-force scan of raw attribute values.
    - Failures are logged (`Log::warning`) and bounce to login with a generic message (`withErrors(['saml' => ...])`); no exception detail reaches the user.
- `sls()` — IdP-initiated single logout: terminates the local session (logout + invalidate + regenerateToken), then `driver()->logoutResponse()` to answer the IdP; on failure redirects to login with a status.
- `metadata()` — publishes this SP's metadata XML via `driver()->getServiceProviderMetadata()`.

### Routes — `routes/web.php`

```
GET  saml/redirect → redirect (SP-initiated)
GET|POST saml/acs → acs (both bindings accepted)
GET  saml/sls → sls ; GET saml/logout → sls (alias, the portal registers saml/logout)
GET  saml/metadata → metadata
```

### Middleware — `bootstrap/app.php`

- `validateCsrfTokens(except: ['saml/acs'])` — signed SAML POSTs cannot carry the CSRF token; protection comes from relay-state/signature/issuer/timestamp validation instead. This CSRF exception is explicitly the documented approach.
- opt-asset additionally encrypts cookies except `appearance`/`sidebar_state` (opt-work does too; no interaction with SAML).

### What opt-work kept of Fortify

- `FortifyServiceProvider` still registered; `fortify.php` features reduced to `Features::resetPasswords()` only (registration, email verification, 2FA, passkeys all removed).
- Keeps `Fortify::loginView` (Inertia `auth/login`), reset-password views, and the `login` rate limiter. Kept `ResetUserPassword` action. Dropped `CreateNewUser`.
- User model: no longer `MustVerifyEmail`, no Passkey/TwoFactor traits; plain `Authenticatable` + `Notifiable`.
- Frontend auth pages reduced to `login.tsx`, `forgot-password.tsx`, `reset-password.tsx`. `welcome.tsx` still links `login()` — i.e. **opt-work did not redirect `/` to SSO; it kept a welcome page**. The "redirect to SSO on launch" behavior is an opt-asset-specific requirement, not something to copy from opt-work.

### Test harness (Pest)

- `tests/Support/FakeIdentityProvider.php` builds real signed SAML messages using LightSaml + XMLSecLibs with fixture certs/keys under `tests/Fixtures/SAML/` (`idp_saml.crt`, `idp_saml.pem`, `sp_saml.crt`, `sp_saml.pem`). Supports options: `success`, `signed_by` ('idp'|'sp'), `issuer`, `state`, `name_id`, `in_response_to`, `email`, `name`, `first_name`, `last_name`.
- `tests/Feature/Auth/SamlTest.php` covers: driver registration; AuthnRequest correctness (issuer, ACS URL) and relay state in session; successful login creating/finding the user; IdP-initiated stateless acceptance; forged state rejected; wrong-key signature rejected; unexpected issuer rejected; unsuccessful status rejected; replay rejected; email attribute identifying the account; failure redirect with session errors; metadata XML validity; IdP logout request terminating the session.
- Notable harness detail: `Socialite::forgetDrivers()` in `beforeEach` and between replay requests — the Socialite manager memoizes driver instances (including resolved users) across requests in tests.

## 2. opt-asset's current state (what would be replaced)

- Fortify fully wired: features = registration, resetPasswords, emailVerification, twoFactorAuthentication, passkeys. Views for login/register/forgot/reset/verify-email/two-factor-challenge/confirm-password.
- `app/Actions/Fortify/CreateNewUser.php` + `ResetUserPassword.php`; `FortifyServiceProvider` with login view + 3 rate limiters (login, two-factor, passkeys).
- User model implements `MustVerifyEmail`, `PasskeyUser`; uses `PasskeyAuthenticatable`, `TwoFactorAuthenticatable`.
- Routes: `/` → `welcome` (public), `dashboard` behind `auth`+`verified`, settings routes (profile, security with RequirePassword, password update, appearance), passkeys well-known endpoint.
- Frontend auth pages: login, register, forgot-password, reset-password, verify-email, two-factor-challenge, confirm-password. Settings pages include `security.tsx` (password + 2FA + passkeys management).
- composer.json: has `laravel/fortify`, `@laravel/passkeys` on the JS side; **no socialite, no saml2** yet.
- Tests (PHPUnit): auth feature tests for registration, password reset, 2FA challenge, email verification, etc. — most would need removal alongside the features.

## 3. What "remove existing login and replace with SSO" means here

The opt-work pattern ports cleanly (same Inertia + Wayfinder + Inertia v3 stack, same `services.saml2` contract). The genuinely open decisions are product decisions, not technical ones:

1. **Entry behavior**: opt-work kept a public welcome page. opt-asset's requirement ("on launch, redirect to SSO portal") means `/` should either redirect to `saml.redirect` or render welcome for guests with an immediate SSO hand-off. Authenticated users landing on `/` should go to dashboard.
2. **How much Fortify to remove**: full removal (package + all auth pages + settings security features) vs opt-work's "keep resetPasswords + login view" compromise. With SSO-only, password reset has no self-service path (the IdP owns credentials), which argues for full removal.
3. **Existing tests**: the auth feature tests (registration, reset, 2FA, verification) test behavior being removed and must be deleted/replaced, not ported.
4. **IdP credentials**: the same env values (`SAML_IDP_ENTITYID`, `SAML_SSO_URL`, `SAML_SLO_URL`, `SAML_SP_ENTITYID`, `SAML_X509_CERT`) — values come from opt-work's `.env` if this app is meant to share the IdP portal registration; the SP entity ID may need to differ per app (each app registers itself with the IdP).

## 4. Verification plan basis

The opt-work test harness (FakeIdentityProvider + fixture certs) is proven against this exact package version and can be brought over nearly verbatim, converting Pest → PHPUnit to match opt-asset's test framework. That gives signature/state/issuer/replay/status coverage without a real IdP.
