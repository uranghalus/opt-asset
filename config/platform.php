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

    'admin_emails' => array_values(array_filter(array_map(
        'strtolower',
        explode(',', (string) env('PLATFORM_ADMIN_EMAILS', '')),
    ))),

];
