# Project Plan — Asset Management System v2 (Multi-Tenant, SSO, Barcode, Depreciation)

**Date:** 2026-09-25 · **Basis:** `docs/PRD.md` + `.ai/rules.md` + research `docs/research/2026-09-25-foundation-decisions-ams-v2.md`
**Status:** Active plan. Tickets **T01–T14 published to GitHub as issues #9–#22** (uranghalus/opt-asset), all labelled `ready-for-agent`, in dependency order (T01→#9, T02→#10, T03→#11, T04→#12, T05→#13, T06→#14, T07→#15, T08→#16, T09→#17, T10→#18, T11→#19, T12→#20, T13→#21, T14→#22). Blocking edges live in each issue body's `Blocked by` section — the native GitHub dependency API wasn't reachable from this environment (`gh` CLI absent, no generic API tool in the MCP server), so the documented fallback (body-text edges) is used. A draft copy of the same tickets remains in `.scratch/ams-v2/issues/`.

---

## 1. Current state (start of plan)

| Area | Status |
| --- | --- |
| Auth | ✅ SAML SSO only (epic #1, tickets #2–#7 done on `feature/saml-sso`, PR #8 open) |
| Design shell | ✅ Acrux-style glass shell + mock dashboard (sidebar, header, bento grid, stat cards, chart) — local ticket set `.scratch/dashboard-acrux-style` complete |
| Domain layer | ❌ Nothing: only `User` model exists; no tenants/assets/classifications/barcodes/depreciation |
| Multi-tenancy | ❌ Not started (decision: stancl/tenancy single-DB + fail-closed wrapper) |
| Tickets | Drafts exist in `.scratch/ams-v2/issues/01–14`, never published to GitHub |

## 2. Settled decisions (grill-me, 2026-09-25)

1. **Tenancy:** stancl/tenancy single-database mode, wrapped **fail-closed** (middleware resolves tenant from the SSO user; no tenant context → no rows; isolation tests enforce it).
2. **Barcode:** **Code128** with human-readable `kode_asset` under the bars (picqer/php-barcode-generator → SVG → dompdf label sheets).
3. **Depreciation period:** **monthly** scheduled run (idempotent per tenant-period) + on-demand recalculation on asset edit.
4. **Depreciation method:** **straight-line only** for MVP; service built behind a method enum so other methods can be added later without schema change.
5. Carried from earlier sessions: SSO-only auth; glass/solid layer split; IBM Plex Mono for codes/figures; SSR wajib; PHPUnit (no Pest).
6. **RBAC (grill 2026-09-28):** **spatie/laravel-permission v8 mandated** — teams permissions enabled (`team_foreign_key` = `tenant_id`, diset sebelum migrasi pertama), permissions global (10 locked `domain.action` strings: assets.view/create/update/delete, mutations.run, disposals.run, barcodes.generate, classifications.manage, reports.view, users.manage), `HasRoles` on User (NO `role_id` on `tenant_memberships` — role-per-membership via spatie `model_has_roles`), JIT landing role `default` view-only (assets.view saja) via **clone-on-first-use** from a global template (idempotent, transactional, race-safe by spatie unique index `(team_id, name, guard_name)`; template changes don't propagate to existing copies), seeded per tenant: `default` + `Admin Tenant` (all 10, not superadmin) with idempotent permission-sync mechanism, superadmin bypass via `Gate::before` (checks `users.is_superadmin` flag, returns true/null — never false), `setPermissionsTeamId` wired into the `tenant` middleware + T01c switch flow, permission cache reset in test setUp, wildcard OFF, T02 on stacked branch `feature/rbac-t02` (from `feature/saml-sso`, not main; retarget PR to main after PR #8 merges).

## 3. Workflow sequence (how work flows)

```
PRD + rules
   → /research  (verify against primary sources)            ✅ done
   → /grill-me  (settle open questions that gate the plan)  ✅ done
   → /to-tickets (tracer-bullet slices → GitHub, blocking edges)  ← we are here
   → /implement per ticket (TDD red→green, laravel-best-practices)
   → /code-review per epic (Standards + Spec axes)
   → dev log + memory update per ticket (rules.md §5)
```

- Work the **frontier**: any ticket whose blockers are all done. T01/T02/T03 can run in parallel (different files).
- Every ticket: failing test first at agreed seams (HTTP + domain-service), implement, pint + phpstan + phpunit + types:check green, then commit.
- Every epic closes with a `/code-review` pass and a demoable slice (no horizontal layer-only merges).

## 4. Phases

### Phase 1 — Foundation (P0, blocking everything)
| Ticket | Delivers |
| --- | --- |
| T01 | Tenancy foundation: Tenant model, fail-closed `tenant_id` scoping, isolation test harness |
| T02 | RBAC skeleton: roles/permissions, least-privilege JIT default role wired into existing SAML flow |
| T03 | Classification chain: golongan → kategori → kelompok → sub kelompok + items (CRUD + cascading UI) |

### Phase 2 — Asset core (P0)
| Ticket | Delivers |
| --- | --- |
| T04 | Asset core: auto-generated `kode_asset`, ledger index (server-side pagination, cascading filters, search, bulk bar) |
| T05 | Asset lifecycle: mutations, disposal (write-off guard), history timeline, audit trail |

### Phase 3 — Automation & identification (P0)
| Ticket | Delivers |
| --- | --- |
| T06 | Depreciation engine: monthly scheduled run, on-demand recalc, report page |
| T07 | Barcode: single + batch generate (queued), dompdf label sheets, print preview |
| T08 | Scan barcode: fail-closed lookup, mobile-first scan page |

### Phase 4 — Administration (P1)
| Ticket | Delivers |
| --- | --- |
| T09 | Per-tenant SAML: `sso_configurations`, runtime multi-IdP driver, admin config UI |
| T10 | Tenant provisioning (admin): onboard tenant + classification template copy |
| T11 | RBAC admin: user list, role editor with permission matrix, assignment |

### Phase 5 — Real data & reporting (P1)
| Ticket | Delivers |
| --- | --- |
| T12 | Dashboard aggregates: mock → live tenant-scoped props (deferred props + skeletons) |
| T13 | Reports: depreciation report + audit trail page (filterable, printable) |
| T14 | Final QA: responsive/a11y/theme parity sweep, SSR verification, full CI green |

## 5. Definition of done (every ticket)

- Failing test written first (PHPUnit), then implementation; suite green
- `vendor/bin/pint --dirty --format agent` + `vendor/bin/pint --test --parallel` clean
- `phpstan analyse` (level per repo config) clean
- `npm run types:check` + `npm run build` clean when frontend touched
- Tenant-isolation test added/updated whenever domain queries touched (rules §1.1)
- Dev log entry `docs/log-dev/YYYY-MM-DD.md` + memory update when a convention/decision lands
- SSR-safe markup (no `window`/`document` in top-level render paths)

## 6. Risks & watchpoints

- **Tenant-scope leaks** = highest-severity defect class; enforced by isolation tests per ticket + code review checklist.
- **stancl fail-open behavior** — wrapper + middleware must never allow an unscoped domain query; DB-level composite uniques as backstop.
- **dompdf on queue** — label sheets for big batches must chunk; progress feedback via Inertia partial reload.
- **PR #8 (SAML) still open** — T02/T09 build on that branch; merge or rebase strategy to confirm with user.
- **Open questions left to product** (PRD §10): AI scope (v2), SaaS billing (v2), onboarding time target — none block MVP.
