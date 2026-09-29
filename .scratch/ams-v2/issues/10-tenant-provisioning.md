# 10: Admin — Tenant Provisioning with Classification Template

**What to build:** A system admin can onboard a new company without a deploy: fill name + code, choose whether the tenant starts with an empty classification or copies a classification template from an existing tenant, and the tenant becomes ready to accept SSO-configured logins. The tenant appears in the admin's tenant list with status.

**Blocked by:** 03: Classification Chain (template source data); 09: Per-Tenant SAML SSO (tenant is only useful once its IdP can be configured)

**Status:** ready-for-agent

- [ ] Admin-only (permission-gated) tenant provisioning page: name, unique code, template source dropdown (existing tenant or "kosong")
- [ ] Copy path duplicates the full 4-level classification chain + items of the source tenant to the new tenant (no cross-tenant leakage — copies are values, not references)
- [ ] New tenant gets default roles from global role set (JIT landing role)
- [ ] Tenant list (admin) with status badges; tenant switching for admins stays out of scope (fail closed)
- [ ] Tests: provisioning creates isolated tenant, template copy completeness, permission gating, isolation after copy
