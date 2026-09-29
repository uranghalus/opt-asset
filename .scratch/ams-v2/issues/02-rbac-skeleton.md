# 02: RBAC Skeleton — Roles, Permissions, Least-Privilege JIT Default

**What to build:** A tenant-scoped role/permission system exists so a newly SSO-provisioned user lands with the least-privilege role, and admin surfaces can gate actions by permission. End-to-end verifiable: an admin can create a role with permissions, assign it to a user, and a permission gate/middleware actually blocks an unauthorized action in a test.

**Blocked by:** 01: Tenancy Foundation

**Status:** ready-for-agent

- [ ] `roles` (tenant_id, name, is_default), `permissions`, `role_permission` tables + models scoped per tenant
- [ ] Global `roles` (is_default) available as JIT landing role for new tenants
- [ ] `user.role_id` + role relation; permission checks via Gate or middleware
- [ ] SAML JIT provisioning (`SamlController.loginUser`) assigns the tenant's default least-privilege role on first login and persists `saml_name_id`
- [ ] Test: JIT user gets the default role; user without permission is blocked from a gated action; roles are isolated per tenant
