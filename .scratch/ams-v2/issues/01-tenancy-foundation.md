# 01: Tenancy Foundation — Tenant Model + BelongsToTenant Global Scope

**What to build:** From the user's perspective nothing changes visually yet, but every future feature is safe: a `Tenant` model (id, name, code, status) exists, the `users` table gains `tenant_id` + `saml_name_id` + `status`, and every domain model created later inherits a `BelongsToTenant` trait whose global scope automatically filters all queries by the acting user's tenant. Provable isolation: queries executed while acting as tenant A never return tenant B's rows, even when the data exists in the same tables.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] `tenants` table + model + factory; unique composite `code`
- [ ] `users.tenant_id` (nullable FK for bootstrap), `users.saml_name_id`, `users.status`; factory states for tenants
- [ ] `BelongsToTenant` trait: global scope adds `tenant_id` filter from authenticated user, fail-closed (no acting tenant → no rows, never unscoped)
- [ ] Creating a tenant-owned record stamps `tenant_id` automatically
- [ ] Two-tenant isolation test harness: seeded tenant A + tenant B; queries from A never see B (model-level + query-level)
- [ ] Jobs/Artisan context covered (scope works without HTTP session via explicit tenant binding)

# 02: RBAC Skeleton — Roles, Permissions, Least-Privilege JIT Default

**What to build:** A tenant-scoped role/permission system exists so a newly SSO-provisioned user lands with the least-privilege role, and admin surfaces can gate actions by permission. End-to-end verifiable: an admin can create a role with permissions, assign it to a user, and a permission gate/middleware actually blocks an unauthorized action in a test.

**Blocked by:** 01: Tenancy Foundation

**Status:** ready-for-agent

- [ ] `roles` (tenant_id, name, is_default), `permissions`, `role_permission` tables + models scoped per tenant
- [ ] Global `roles` (is_default) available as JIT landing role for new tenants
- [ ] `user.role_id` + role relation; permission checks via Gate or middleware
- [ ] SAML JIT provisioning (`SamlController.loginUser`) assigns the tenant's default least-privilege role on first login and persists `saml_name_id`
- [ ] Test: JIT user gets the default role; user without permission is blocked from a gated action; roles are isolated per tenant

# 03: Classification Chain — Golongan → Kategori → Kelompok → Sub Kelompok + Items

**What to build:** An asset officer can manage their tenant's four-level classification hierarchy and the items under it. End-to-end: create/edit each level in the chain with codes, drill down level by level in the UI (each level's options filtered by the chosen parent), and an item without classification can exist for auto-created imports. All classification data is tenant-isolated.

**Blocked by:** 01, 02

**Status:** ready-for-agent

- [ ] `asset_groups` → `asset_categories` → `asset_clusters` → `asset_sub_clusters` tables (tenant_id + parent FK each, composite unique `(tenant_id, code)` per level)
- [ ] `items` (tenant_id, nullable sub-cluster FK, name)
- [ ] CRUD API + Inertia pages: 4-level cascading selector pattern (levels locked until parent chosen, `aria-disabled` per DESIGN_BRIEF)
- [ ] Solid-surface data presentation (no glass on data lists per DESIGN.md layer rule)
- [ ] Tests: chain CRUD per tenant, cascade filtering, cross-tenant isolation of every level
