<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Rbac\RbacProvisioner;
use App\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use LightSaml\Error\LightSamlException;
use SocialiteProviders\Saml2\Provider;
use SocialiteProviders\Saml2\User as Saml2User;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SamlController extends Controller
{
    /**
     * Send the user to the identity provider (SP-initiated SSO).
     */
    public function redirect(): SymfonyRedirect
    {
        return $this->driver()->redirect();
    }

    /**
     * Consume the SAML response and log the user in.
     *
     * Accepts both SP-initiated responses (validated against the relay state
     * stored in the session) and IdP-initiated responses (accepted only when
     * no SP-initiated flow is in progress, and still validated by the
     * signature, issuer and timestamp checks of the SAML provider itself).
     */
    public function acs(): RedirectResponse
    {
        // Decided before resolving the user: the provider pulls the relay
        // state out of the session while validating it, so the information is
        // gone after the first resolution attempt.
        $stateless = ! $this->hasSpInitiatedState();

        try {
            $samlUser = $stateless
                ? $this->statelessSamlUser()
                : $this->samlUser();
        } catch (Throwable $exception) {
            Log::warning('SAML login failed.', [
                'exception' => $exception,
            ]);

            return $this->loginFailure();
        }

        $user = $this->loginUser($samlUser);

        // A stale relay state from an abandoned SP-initiated flow must not
        // make a later IdP-initiated response look state-forged.
        request()->session()->forget('state');

        // Route by membership, not by a single tenant column:
        // platform admins (env fallback grant or superadmin flag) land in
        // their area, members land on the dashboard acting inside their
        // default tenant.
        $defaultRoute = $user->isPlatformAdmin()
            ? route('platform.business-units.index')
            : route('dashboard');

        return redirect()->intended($defaultRoute);
    }

    /**
     * Consume an unsolicited SAML logout request from the identity provider
     * (IdP-initiated single logout) and answer it.
     */
    public function sls(): Response
    {
        $this->logoutLocally();

        try {
            return $this->driver()->logoutResponse();
        } catch (Throwable $exception) {
            Log::warning('SAML logout response failed.', [
                'exception' => $exception,
            ]);

            return redirect()->route('home')->with('status', 'You have been logged out.');
        }
    }

    /**
     * Publish this service provider's SAML metadata for identity provider
     * configuration.
     */
    public function metadata(): Response
    {
        return $this->driver()->getServiceProviderMetadata();
    }

    /**
     * The configured Saml2 Socialite provider.
     */
    protected function driver(): Provider
    {
        /** @var Provider $driver */
        $driver = Socialite::driver('saml2');

        return $driver;
    }

    /**
     * Resolve the SAML user with relay state validation (SP-initiated).
     *
     * @throws Throwable
     */
    protected function samlUser(): Saml2User
    {
        $user = $this->driver()->user();

        assert($user instanceof Saml2User);

        return $user;
    }

    /**
     * Resolve the SAML user without relay state validation (IdP-initiated).
     *
     * @throws Throwable
     */
    protected function statelessSamlUser(): Saml2User
    {
        $user = $this->driver()->stateless()->user();

        assert($user instanceof Saml2User);

        return $user;
    }

    /**
     * Whether this session has an SP-initiated SAML flow in progress.
     */
    protected function hasSpInitiatedState(): bool
    {
        return (bool) request()->session()->get('state');
    }

    /**
     * Find or create the local account for a SAML-authenticated identity,
     * log them in, and settle their tenant data.
     *
     * Settling (grill decision 2026-09-27):
     *  - allowlisted emails are promoted (env fallback grant → DB flag);
     *  - a platform admin with no membership is attached as the default
     *    member of the first active tenant when one exists, so the resolved
     *    tenant id can be pinned into the session's user data;
     *  - the resolved tenant id is stored in the session (`tenant.active_id`)
     *    — "tenant ID set within the user data upon login" — and exposed on
     *    the auth payload by HandleInertiaRequests.
     *
     * @throws LightSamlException
     */
    protected function loginUser(Saml2User $samlUser): User
    {
        // Log raw attributes in debug mode so we can inspect what the IdP sends.
        Log::debug('SAML assertion received.', [
            'id' => $samlUser->getId(),
            'name' => $samlUser->getName(),
            'email' => $samlUser->getEmail(),
            'attributes' => $samlUser->getRaw(),
        ]);

        $email = $this->resolveEmail($samlUser);

        if (! $email) {
            Log::warning('SAML: no email found in assertion.', [
                'id' => $samlUser->getId(),
                'attributes' => $samlUser->getRaw(),
            ]);

            throw new LightSamlException('The identity provider did not return an email address.');
        }

        $user = User::query()->firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->forceFill([
                'name' => $samlUser->getName() ?: $email,
                'email' => $email,
                // SAML accounts authenticate through the identity provider, so
                // an unguessable password keeps the column satisfied while
                // making password login impossible.
                'password' => Hash::make(Str::random(64)),
                'email_verified_at' => now(),
                'is_superadmin' => false,
                'saml_name_id' => $samlUser->getId(),
            ])->save();
        } elseif ($user->saml_name_id !== $samlUser->getId()) {
            // Re-linking (FR-15): the identity provider now sends a different
            // NameID for this account — persist it so the SAML mapping stays
            // truthful.
            $user->forceFill(['saml_name_id' => $samlUser->getId()])->save();
        }

        // Bootstrap seed: allowlisted emails are promoted to
        // superadmin at login; the flag then lives in the database, so
        // grant/revoke no longer requires an .env change.
        $user->promoteIfAllowlisted();

        $this->settleTenantData($user, ['name_id' => $samlUser->getId()]);

        Auth::login($user);

        // JIT landing role (T02): every active membership without a role
        // lands on the tenant's `default` view-only role, cloned from the
        // global template when missing. Idempotent on every login.
        app(RbacProvisioner::class)->assignDefaultRoles($user);

        return $user;
    }

    /**
     * Settle the user's tenant data after authentication (see loginUser).
     *
     * @param  array<string, mixed>  $raw
     */
    protected function settleTenantData(User $user, array $raw = []): void
    {
        // Correlate the SSO identity for future per-tenant SSO work (T09).
        if ($user->saml_name_id === null && isset($raw['name_id']) && is_string($raw['name_id']) && $raw['name_id'] !== '') {
            $user->forceFill(['saml_name_id' => $raw['name_id']])->save();
        }

        // Platform admins are attached to the first active tenant so a
        // resolved tenant id exists for the dashboard surface; regular
        // members keep whatever memberships provisioning gave them.
        if ($user->isPlatformAdmin()
            && ! $user->memberships()->exists()
            && ($first = Tenant::query()->where('status', 'active')->orderBy('name')->first()) !== null) {
            $user->memberships()->create([
                'tenant_id' => $first->getKey(),
                'is_default' => true,
            ]);
        }

        // Pin the resolved tenant id into the session's user data at login.
        $resolvedTenantId = app(TenantContext::class)->resolveFor($user)?->getKey();

        if ($resolvedTenantId !== null) {
            session([TenantContext::SESSION_KEY => $resolvedTenantId]);
        }
    }

    /**
     * Resolve an email address from the SAML assertion using multiple strategies:
     *
     *  1. Standard attribute map (ClaimTypes, OASIS URIs) via getEmail().
     *  2. The NameID, if it looks like an email address.
     *  3. A first-pass scan of every raw attribute value that looks like an email.
     */
    protected function resolveEmail(Saml2User $samlUser): ?string
    {
        // Strategy 1 – standard mapped attributes.
        if ($email = $samlUser->getEmail()) {
            return $email;
        }

        // Strategy 2 – NameID that happens to be an email address.
        if ($id = $samlUser->getId()) {
            if (filter_var($id, FILTER_VALIDATE_EMAIL)) {
                return $id;
            }
        }

        // Strategy 3 – brute-force scan of every raw attribute value.
        foreach ($samlUser->getRaw() as $attribute) {
            foreach ($attribute->getAllAttributeValues() as $value) {
                $string = is_object($value) && method_exists($value, 'getValue')
                    ? $value->getValue()
                    : (string) $value;
                if (filter_var($string, FILTER_VALIDATE_EMAIL)) {
                    return $string;
                }
            }
        }

        return null;
    }

    /**
     * Terminate the local session.
     */
    protected function logoutLocally(): void
    {
        Auth::logout();

        request()->session()->invalidate();

        request()->session()->regenerateToken();
    }

    /**
     * Bounce back to the login screen with a generic, user-safe message.
     */
    protected function loginFailure(): RedirectResponse
    {
        return redirect()
            ->route('home')
            ->withErrors(['saml' => 'Sign-in with your organization account failed. Please try again.']);
    }
}
