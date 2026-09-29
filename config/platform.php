<?php

use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Admin Emails (bootstrap seed)
    |--------------------------------------------------------------------------
    |
    | Emails granted platform administration. This is an ENVIRONMENT FALLBACK
    | GRANT, not merely a login-time seed (grill decision 2026-09-27): the
    | gate below consults the allowlist directly, so a fresh install can
    | reach /platform/business-units and create the first business unit
    | without depending on a prior SAML round-trip or any migration timing.
    |
    | At SAML login, allowlisted users get the flag stamped to the database
    | (users.is_superadmin); from then on the data flag is authoritative and
    | grant/revoke lives in data.
    |
    | Laravel parses comma-separated env values into arrays; be tolerant of
    | both a plain string and the pre-split array form.
    |
    */

    'admin_emails' => array_filter(array_map(
        fn ($email) => strtolower(trim((string) $email)),
        (array) env('PLATFORM_ADMIN_EMAILS', []),
    )),

    /*
    |--------------------------------------------------------------------------
    | Platform Admin Gate
    |--------------------------------------------------------------------------
    |
    | The single authority for "may administer the platform area": either a
    | persisted superadmin flag or the environment fallback grant. Both
    | User::isPlatformAdmin() and the bootstrap flows resolve through this,
    | so there is exactly one definition of the boundary.
    |
    */

    'gate' => function (User $user): bool {
        return $user->is_superadmin === true
            || in_array(strtolower($user->email), config('platform.admin_emails', []), true);
    },

];
