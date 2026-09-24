# 03: Router Migration

**What to build:** Configure the `routes/web.php` for `saml/*` API routes, and overwrite default application `auth` logic and unauthenticated redirects to redirect explicitly to SSO rather than standard Login pages.

**Blocked by:** 02-controller-and-auth.md

**Status:** ready-for-agent

- [x] Add `/saml/*` routes mimicking `opt-work`.
- [x] Override `Route::inertia('/', 'welcome')` to issue a redirect.
- [x] Adjust default Laravel UI Login triggers if necessary to bounce automatically to SSO.
