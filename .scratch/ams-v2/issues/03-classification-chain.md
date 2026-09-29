# 03: Classification Chain — Golongan → Kategori → Kelompok → Sub Kelompok + Items

**What to build:** An asset officer can manage their tenant's four-level classification hierarchy and the items under it. End-to-end: create/edit each level in the chain with codes, drill down level by level in the UI (each level's options filtered by the chosen parent), and an item without classification can exist for auto-created imports. All classification data is tenant-isolated.

**Blocked by:** 01: Tenancy Foundation; 02: RBAC Skeleton

**Status:** ready-for-agent

- [ ] `asset_groups` → `asset_categories` → `asset_clusters` → `asset_sub_clusters` tables (tenant_id + parent FK each, composite unique `(tenant_id, code)` per level)
- [ ] `items` (tenant_id, nullable sub-cluster FK, name)
- [ ] CRUD API + Inertia pages: 4-level cascading selector pattern (levels locked until parent chosen, `aria-disabled` per DESIGN_BRIEF)
- [ ] Solid-surface data presentation (no glass on data lists per DESIGN.md layer rule)
- [ ] Tests: chain CRUD per tenant, cascade filtering, cross-tenant isolation of every level
