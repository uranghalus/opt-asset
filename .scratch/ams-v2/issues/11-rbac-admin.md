# 11: Admin — User & Role Management (RBAC UI)

**What to build:** A tenant admin can manage their own tenant's users and roles: list users (SSO-provisioned + manually invited placeholders), create/edit tenant roles with permission checkboxes, and assign roles. Permission changes take effect immediately for gated actions.

**Blocked by:** 02: RBAC Skeleton; 04: Asset Core (ledger patterns reused for user table)

**Status:** ready-for-agent

- [ ] User list per tenant (paginated, searchable) with role + last SSO login + status
- [ ] Role editor: name + permission matrix (checkbox grid), least-privilege defaults preserved
- [ ] Role assignment on user rows; guard: cannot remove the last admin-capable role assignment
- [ ] Tests: permission matrix round-trip, role gating takes effect, tenant isolation (admin of A cannot list/edit B's users)
