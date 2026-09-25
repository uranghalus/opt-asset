# Research: Foundation decisions for AMS v2 build

**Date:** 2026-09-25
**Question:** What are the first steps and verified technical foundations for building the AMS v2 PRD (multi-tenant, SAML SSO done, barcode, depreciation) on this repo's stack (Laravel 13 + Inertia v3 + React 19)?
**Status:** verified against primary sources — feeds the project plan and ticket breakdown

## Context read before research

- `docs/PRD.md` — FR-14..18, data model §7, edge states §8, open questions §10
- `.ai/rules.md` — binding rules: fail-closed tenant scoping, SSO-only auth, glass/solid design layers, SSR wajib
- Repo state: SAML SSO merged on `feature/saml-sso` (epic #1, tickets #2–#7, PR #8 open); **no domain models/migrations exist yet** (only `User`); dashboard is a mock shell
- Draft tickets exist in `.scratch/ams-v2/issues/01–14` but were **never published** to GitHub

## Finding 1 — Tenancy: stancl/tenancy single-database mode needs a fail-closed wrapper

The PRD names https://tenancyforlaravel.com/ (stancl/tenancy). Primary docs (https://tenancyforlaravel.com/docs/v3/single-database-tenancy/) confirm:

- Single-database mode = keep `DatabaseTenancyBootstrapper` disabled, no DB creation jobs; tenant context is set by explicitly initializing tenancy (`Tenancy::initialize($tenant)`) — for this app, from `Auth::user()->tenant_id` via middleware.
- `Stancl\Tenancy\Database\Concerns\BelongsToTenant` on primary models auto-fills `tenant_id` and global-scopes all queries; secondary models use `BelongsToPrimaryModel` (declares `getRelationshipToPrimaryModel()`).
- **Critical nuance:** the package's scope is *not* fail-closed — "not scoped at all when there's no current tenant (e.g. in a central admin panel)". Rules §1.1 demands fail-closed (no acting tenant → no rows, never unscoped). So either (a) wrap/override the trait to fail closed, or (b) guarantee via middleware that every user-facing request initializes tenancy before any domain query, and keep fail-closed tests as the enforcement net.
- Unique constraints must be composite at DB level: `unique(['tenant_id', 'kode_asset'])` etc. — matches PRD §8.
- Validation `unique`/`exists` rules are NOT auto-scoped (`Rule::unique('assets')->where('tenant_id', tenant('id'))` or `HasScopedValidationRules`).
- `DB::` facade queries bypass the scope — audit for raw queries in code review.
- `withoutTenancy()` is the only sanctioned escape hatch (tenant provisioning/admin code).

## Finding 2 — Pagination/filter/search: Inertia v3 partial reloads are the verified pattern

Primary docs (https://inertiajs.com/docs/v3/data-props/partial-reloads):

- Server-side pagination via Laravel paginator + Inertia props; filter/search state lives in the query string.
- Partial reloads: `router.reload({ only: ['assets'] })` re-fetches just the ledger prop on filter/pagination changes — other props (filters metadata, tenant info) are not re-evaluated server-side when wrapped in closures (`fn () => ...`) or `Inertia::optional()`.
- This satisfies the "SSR wajib" rule: initial page load renders the full ledger server-side; only subsequent filter/pagination interactions are partial XHR visits.

## Finding 3 — Barcode: picqer/php-barcode-generator + dompdf is the verified pairing

- https://github.com/picqer/php-barcode-generator: zero-dependency PHP lib producing SVG/PNG/JPG/HTML for Code128 — SVG embeds directly into dompdf templates.
- dompdf (barryvdh/laravel-dompdf) renders the label-sheet PDF server-side; batch generation runs on the queue (composer `dev` script already runs `queue:listen`).
- Label content per PRD FR-16: Code128 image + `kode_asset` (IBM Plex Mono) + asset name; `barcodes.barcode_value` unique per tenant.

## Finding 4 — Depreciation: scheduler + per-tenant iteration over a scoped chunk

- Laravel scheduler runs a monthly Artisan command; for all-tenant runs, iterate tenants and initialize tenancy per tenant before chunked queries (pattern for `tenants:run`-style execution; the command does this explicitly to stay queue-friendly and per-tenant-fail-safe).
- Straight-line math with residual floor: monthly = (nilai_perolehan − residu) / masa_manfaat_bulan; accumulated capped so nilai_buku ≥ residu; every run writes a `depreciation_run` history event (FR-18). Idempotency: run keyed by (tenant, period) — re-running a period is a no-op.
- On-demand recalculation reuses the same service on asset create/update (life/cost changes recompute from zero).

## Recommendation (first steps, in order)

1. Tenancy foundation (Tenant model, tenant_id scoping, fail-closed proof tests) — everything else compiles against it.
2. RBAC skeleton + JIT default role (SAML already provisions users; they need a landing role).
3. Classification chain → asset core (kode_asset generator) → lifecycle → depreciation → barcode → scan → admin surfaces → dashboard wiring → reports → final QA.
- The existing `.scratch/ams-v2/issues/01–14` draft breakdown matches this order and dependency graph; verify with the user, then publish to GitHub as the real tickets.

## Sources

- https://tenancyforlaravel.com/docs/v3/single-database-tenancy/ (BelongsToTenant, BelongsToPrimaryModel, composite uniques, validation scoping, withoutTenancy, fail-open caveat)
- https://inertiajs.com/docs/v3/data-props/partial-reloads (only/except, router.reload, closures, Inertia::optional, preserveErrors)
- https://github.com/picqer/php-barcode-generator (SVG/Code128, zero deps)
- https://laravel.com/framework/docs/11.x/scheduling (scheduler mechanics — same contract in L13)
- In-repo: `docs/PRD.md`, `.ai/rules.md`, `docs/research/2026-09-24-saml-sso-via-socialite-saml2.md`, GitHub issues #1–#8
