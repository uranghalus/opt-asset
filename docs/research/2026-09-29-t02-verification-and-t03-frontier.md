# T02 Verification & Frontier Analysis — what's next after RBAC

**Date:** 2026-09-29 · **Question:** "Check the results of my latest work and see what I can work on next."
**Status:** Verified against primary sources (local suite run, source code, GitHub API, ticket bodies). Research performed inline (no background-agent tool available in this environment); findings cite each claim's source.

## Context read before research

- `docs/memory/obsidian-memory.md` (status 2026-09-28), `docs/log-dev/2026-09-28.md`, `docs/PROJECT-PLAN.md`
- `.ai/rules.md` (the operative rules file — note: root `rules.md` referenced by AGENTS.md **does not exist**)
- GitHub: issues #1–#25, PR #8 via GitHub MCP (`uranghalus/opt-asset`)

## Finding 1 — T02 (RBAC skeleton) is verifiably done and green

Ran locally today on `feature/rbac-t02` (HEAD `904a9a3`):

- `php artisan test --compact` → **113 passed, 363 assertions** (matches the dev-log claim exactly).
- Working tree clean apart from noise (`.obsidian/workspace.json`, `package-lock.json`).
- Source audit confirms every settled decision from ticket #10 landed in code:
  - `app/Enums/Permission.php` — 10 locked `domain.action` strings + `values()`.
  - `app/Concerns/BelongsToTenant.php` — fail-closed scope (T01) intact, untouched by T02.
  - Provisioner/JIT/context per ticket body: `RbacProvisioner` clone-on-first-use via query builder (bypassing spatie's `RoleAlreadyExists` app-check that also matches global roles), `SamlController` JIT default-role assignment, `TenantContext` `setPermissionsTeamId` bind/reset/rebind, `Gate::before` superadmin.

**Sources:** local test run 2026-09-29; `git log` `871c77a`/`43c54b8`/`904a9a3`; file reads above; issue #10 body.

## Finding 2 — RBAC is not consumed by any HTTP surface yet

`routes/web.php` and `routes/settings.php` register **no `permission:`/`role:` middleware on any route**. The T02 machinery is enforced only in tests; the first real consumer will be T03's classification pages (`classifications.manage`). This is by design (T02 is a skeleton) but means the "gated action actually blocks a user" AC is currently proven at the Gate level, not end-to-end over HTTP.

**Source:** `routes/web.php`, `routes/settings.php` read 2026-09-29.

## Finding 3 — Tracker & doc drift (housekeeping candidates)

1. **Issue #10 still open** although T02 work is complete (no PR exists for `feature/rbac-t02` — memory records the deliberate "stacked, retarget after PR #8 merges" strategy, so closure is pending merge logistics).
2. **PR #8 (SAML epic) still open** — the only open PR; its 17 comments are bot audit noise (ecc-tools), no human review verdict. `feature/rbac-t02` and all subsequent work are stacked behind it.
3. **Tickets #2–#7 still open** although delivered inside PR #8 — dead weight on the tracker.
4. **AGENTS.md** mandates reading root `rules.md` — that file doesn't exist; the real file is `.ai/rules.md`.
5. `.ai/rules.md` §4 still lists the obsolete dashboard ticket set (01–08), superseded by `docs/PROJECT-PLAN.md` (T01–T14).
6. **PRD §7 data model is stale in three spots:** `users` sketch still shows `tenant_id` (dropped in T01c) and `role_id` (superseded by spatie `model_has_roles` in T02 — the section note below it is correct, the sketch isn't).
7. **Spec gap found: `locations` entity is referenced but never defined.** `assets.lokasi_id` and `asset_mutations.from_location_id/to_location_id` (PRD §7) point at a `locations` table that appears nowhere in the data model or any ticket. Must be resolved before/at T04 (asset core) — candidate: define a minimal `locations` table in T04 or add a ticket.

**Sources:** GitHub MCP list_issues/list_pull_requests/get_pull_request_comments; AGENTS.md; `.ai/rules.md` §4; `docs/PRD.md` §7.

## Finding 4 — The frontier is exactly one ticket: T03

Dependency graph from ticket bodies: T03 (#11) is blocked only by #9 (closed) and #10 (work done, awaiting merge logistics). **T04–T14 all transitively depend on T03** — it is the only unblocked work. Nothing else can start (T11 needs T04 patterns; T09 needs T03 for onboarding classification; T12–T13 need T05/T06).

T03's binding constraints (issue #11 + rules §1.3 + PRD §7):

- Tables: `asset_groups → asset_categories → asset_clusters → asset_sub_clusters` + `items` (nullable sub-cluster FK for auto-created imports).
- Per level: `tenant_id` + parent FK + composite unique `(tenant_id, code)` at DB level.
- Established pattern to replicate (from T01/T02): PK ULID (`HasUlids`) + `use BelongsToTenant` + isolation tests against the `Machine` harness convention (`tests/Feature/Tenancy/TenantIsolationTest.php`).
- UI: 4-level cascading drill-down, levels locked until parent chosen; solid surfaces (no glass on data lists); status badges with text; both themes; SSR-safe.
- Permission consumer decision: `classifications.manage` gates the CRUD (see grill Round 1).

## Recommendation (next work, in order)

1. Resolve merge logistics for the stacked branches (grill Q1): merge PR #8 → open T02 PR → close #10.
2. Housekeeping sweep (grill Q4): close #2–#7, fix AGENTS.md/rules pointer, refresh `.ai/rules.md` §4, correct PRD §7 `users` sketch, record the `locations` spec gap.
3. Grill T03 open decisions (codes immutability, permission gating, UI shape) → settled decisions into issue #11.
4. Implement T03 TDD: migration → models+trait → isolation tests → CRUD+gates → cascading UI (first `impeccable` UI pass) → full DoD.

## Sources

- Local: `php artisan test --compact` (2026-09-29, 113/363), `git log/status`, source files listed above.
- GitHub API (MCP): issues #1–#25 states and bodies; PR #8 metadata + comments.
- Repo docs: `docs/PROJECT-PLAN.md`, `docs/PRD.md` §7–§10, `docs/memory/obsidian-memory.md`, `docs/log-dev/2026-09-28.md`, `.ai/rules.md`, `AGENTS.md`.
