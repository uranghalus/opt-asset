# Research: Tenancy reconfiguration — stancl/tenancy on Opti-Asset

**Date:** 2026-09-27
**Question:** Per the user's mandate — read https://tenancyforlaravel.com/ first, then remove and reconfigure the multi-tenancy setup from scratch until fully functional: CRUD for tenant data ("business unit"), tenant dropdown switcher, bootstrap entry for `superadmin@appdutamall`, and tenant ID set into user data at login.
**Status:** verified against primary sources + live repo state; findings reconcile the user's 2026-09-27 spec with the already-landed T01a–T01d implementation

## Context read before research

- `docs/research/2026-09-25-foundation-decisions-ams-v2.md` — original tenancy decision record
- `docs/memory/obsidian-memory.md` — T01 (foundation), T01b (platform CRUD), T01c (multi-membership), T01d (superadmin + switcher) recorded as done on `feature/saml-sso`
- Live code: `app/Tenancy/*`, `app/Concerns/BelongsToTenant`, `app/Models/{Tenant,TenantMembership,TenantSwitch,User}`, platform controller + requests + pages, `sidebar-tenant-switcher.tsx`, 12 tenancy tests
- `composer.lock`: stancl/tenancy **v3.10.1**; Laravel 13, Inertia v3, React 19

## Finding 1 — The package docs mandate exactly what is implemented (single-database mode)

From https://tenancyforlaravel.com/docs/v3/single-database-tenancy/ (primary source):

- Single-DB mode requires **`DatabaseTenancyBootstrapper` disabled** and the database-creation listeners (`CreateDatabase`, `MigrateDatabase`, `SeedDatabase`) off — `config/tenancy.php` in this repo does precisely this (only `QueueTenancyBootstrapper` on).
- Primary models use `BelongsToTenant`; secondary use `BelongsToPrimaryModel`; global models stay unscoped. The package scope is documented **fail-open** ("not scoped at all when there's no current tenant") — which is why the repo wraps it with `App\Tenancy\FailClosedTenantScope` (no context → `WHERE 1=0`, never unscoped) and `App\Concerns\BelongsToTenant` (create without context → `TenantContextRequiredException`; request-input `tenant_id` overwritten by the acting tenant).
- Composite unique constraints at DB level (`unique(['tenant_id', 'slug'])`), manually scoped validation rules, `DB::` facade bypasses scoping, `withoutTenancy()` is the sanctioned escape hatch — all already encoded in the repo's harness conventions (`tests/Support/helpers.php`).
- Custom tenant columns (`id`, `code`, `name`, `status`) declared via `getCustomColumns()` on `App\Models\Tenant` so they stay real columns instead of the `data` JSON bucket — correct per the package's VirtualColumn contract.
- ULID ids via the `UniqueIdentifierGenerator` contract binding (`App\Tenancy\UlidIdentifierGenerator`) — package-sanctioned customization point.

**Conclusion:** the architecture standing on the branch is the correct reading of the mandated docs. The reconfiguration work should preserve its seams, not reinvent them.

## Finding 2 — Live-state audit (why the user experiences the setup as "not fully functional")

Verified via tinker against the dev database on 2026-09-27:

| Check | Result |
| --- | --- |
| `tenants` rows | **0** |
| `tenant_memberships` rows | 0 |
| `users` | exactly 1: `superadmin@appdutamall.com`, `is_superadmin = 0` |
| `config('platform.admin_emails')` | **empty array** |
| `bootstrap/cache/config.php` | not cached |

Consequences, traced through the code:

1. The superadmin account is **never promoted**: `promoteIfAllowlisted()` runs only inside the SAML `loginUser()` path, the allowlist is empty in this environment, and the seed migration only promotes users existing at migration time. With `is_superadmin = 0` and zero memberships, `InitializeTenantContext` 403s and `EnsurePlatformAdmin` 404s — **no screen in the app is reachable for this account**, and no tenant can ever be created because the CRUD lives behind the same gate. This is the bootstrap deadlock the user describes as "must be entered first".
2. The spec's email `superadmin@appdutamall` has no TLD; every existing allowlist/JIT path compares RFC emails (the IdP assertion email is `@appdutamall.com`). The bootstrap must key on the real account email.
3. The spec asks for the tenant id "set within the user data upon login". The T01c refactor deliberately **removed** `users.tenant_id` in favor of the membership pivot + session pointer (multi-membership), and memory marks that removal as settled. Reintroducing a physical column would regress the switcher's audit trail; the honest reconciliation is to keep the pivot as source of truth and expose the resolved tenant id on the authenticated user payload.

## Finding 3 — Delta between the 2026-09-27 spec and the standing implementation

| Spec item | Standing implementation | Delta |
| --- | --- | --- |
| CRUD for tenant data, renamed **"business unit"** | Full CRUD + status transitions at `/platform/tenants` (no hard delete by decision) | Terminology only: rename surface, routes may keep `tenants` slug or move to `business-units` — grill to decide |
| Switch via tenant dropdown | Sidebar switcher (superadmin: all active tenants; users: memberships) | None — keep |
| Bootstrap entry for `superadmin@appdutamall` to enter tenant data first | Allowlist seed only promotes at SAML login; env allowlist currently empty → account can reach nothing | **Real gap**: needs an environment-independent bootstrap guarantee for the named account |
| Tenant ID set into user data at login | Membership pivot + session pointer; no `users.tenant_id` column | Expose resolved tenant id in the shared auth payload (or physical column if grill overrides) |

## Sources

- https://tenancyforlaravel.com/ (package overview, modes)
- https://tenancyforlaravel.com/docs/v3/single-database-tenancy/ (traits, fail-open caveat, composite uniques, validation scoping, withoutTenancy, custom column name, BelongsToPrimaryModel)
- In-repo: `config/tenancy.php`, `app/Tenancy/*`, `app/Concerns/BelongsToTenant.php`, `app/Models/*`, `app/Http/{Controllers,Middleware,Requests}/*`, `resources/js/components/sidebar-tenant-switcher.tsx`, `tests/Feature/Tenancy/*`, `docs/memory/obsidian-memory.md`
- Live tinker audit of the dev database (users/tenants/memberships/config, 2026-09-27)
