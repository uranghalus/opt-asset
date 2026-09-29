# Opti-Asset

Multi-tenant asset management for a corporate group: every company (tenant) manages its fixed assets through SAML SSO, with classification-driven asset coding, depreciation, and barcodes. This is the glossary of the project's domain language — use these terms, avoid the listed synonyms.

## Language

### Tenancy & access

**Tenant**:
A company entity (holding group member) that owns an isolated slice of all domain data. Identified by a ULID and a unique URL-safe `code`.
_Avoid_: company, organization, workspace, team (except when discussing spatie teams internals)

**Acting tenant context**:
The tenant bound to the current request, job, or artisan process. Exactly one at a time; acquired via membership or superadmin switch. No context → no rows.
_Avoid_: current tenant, active tenant (in code/comments prefer "context")

**Fail-closed**:
The isolation policy: a missing tenant context or missing permission yields zero rows / denial — never unscoped data, never a permissive fallback.

**Membership**:
A user's attachment to one tenant (`tenant_memberships` pivot). One SSO account may hold many memberships; one is default. Roles attach per membership via spatie `model_has_roles`, not on the pivot.
_Avoid_: assignment, subscription

**Superadmin**:
A platform-level operator flagged in the database (`users.is_superadmin`). Sees tenant data only by switching into a tenant context — never via cross-tenant queries.
_Avoid_: platform admin (legacy term for the pre-T01d allowlist criterion), root

**JIT provisioning**:
Automatic creation (or re-link) of a user at first SAML login from IdP attributes. The user lands with the tenant's Default role.
_Avoid_: auto-registration, signup

**Default role**:
The team-scoped role named `default` in each tenant — the least-privilege landing role (view assets only), cloned from the global template on first use. Convention, not a column.
_Avoid_: basic role, viewer role

### Classification & assets

**Classification chain**:
The mandatory four-level hierarchy Golongan → Kategori → Kelompok → Sub Kelompok that drives asset coding and the drill-down UI. Order is fixed; levels are never skipped.
_Avoid_: category tree, taxonomy

**Golongan / Kategori / Kelompok / Sub Kelompok**:
The four chain levels (top → bottom), stored as `asset_groups`, `asset_categories`, `asset_clusters`, `asset_sub_clusters`. Canonical names are Indonesian; keep them in UI copy and issues.

**Chain code**:
The `code` attribute of a chain level. Unique per tenant per level. Concatenated in chain order (never user input) to build `kode_asset`. Immutable once a child level, item, or asset references it (ADR-0001).

**Item**:
A concrete article registered under a Sub Kelompok (nullable, for auto-created imports) that assets instantiate. Import never skips rows for unknown items — it auto-creates them uncategorized.
_Avoid_: product, article type

**kode_asset**:
The human-readable asset code, unique per tenant, generated from the chain codes plus a per-tenant sequence. Read-only everywhere.
_Avoid_: asset ID (the primary key), asset number

**Asset**:
An individual fixed-asset record owned by a tenant, typed `aktiva_tetap` or `peralatan` (threshold-based, independent of classification), carrying acquisition cost, useful life, and book value.
_Avoid_: equipment (for the type), item (an Asset references an Item)

**Book value (nilai buku)**:
Acquisition cost minus accumulated depreciation, floored at residual. The figure reconciled against manual accounting.
