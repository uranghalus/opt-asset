<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Admin Allowlist
    |--------------------------------------------------------------------------
    |
    | Emails allowed into the central platform area (/platform/*). Criterion
    | (T01c): zero tenant memberships AND an explicit entry here. This is the
    | fail-closed replacement for "every JIT SSO user without a tenant" —
    | without an allowlist, anyone in the organization could administer all
    | tenants. Comma-separated in the environment.
    |
    */

    // Laravel parses comma-separated env values into arrays; be tolerant
    // of both a plain string and the pre-split array form.
    'admin_emails' => array_filter(array_map(
        fn ($email) => strtolower(trim((string) $email)),
        (array) env('PLATFORM_ADMIN_EMAILS', []),
    )),

];
