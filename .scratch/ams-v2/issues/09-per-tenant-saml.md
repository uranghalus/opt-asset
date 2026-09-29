# 09: Per-Tenant SAML SSO — sso_configurations + Multi-IdP Driver + JIT with Role

**What to build:** The existing working SAML implementation goes multi-tenant: each tenant keeps its own IdP configuration in `sso_configurations`, the login entry resolves the acting tenant and builds the Saml2 Socialite driver with that tenant's settings, JIT-provisioned users get the tenant's default least-privilege role, and login is strictly tenant-scoped — a user of tenant A cannot authenticate through tenant B's IdP into tenant B's data.

**Blocked by:** 02: RBAC (default role for JIT); 03: Classification (tenant onboarding needs classification template)

**Status:** ready-for-agent

- [ ] `sso_configurations` table per PRD §7 (tenant_id, idp_entity_id, idp_sso_url, idp_x509_cert, attribute_mapping JSON)
- [ ] Tenant resolution at login (host/route parameter — decision recorded; subdomain routing deferred to v2)
- [ ] Saml2 driver built from per-tenant settings at runtime (override before redirect/acs); existing ACS/SLS/JIT logic preserved
- [ ] Admin SSO config UI: entity ID, SSO URL, cert upload, attribute-mapping editable table, Test Koneksi guard (cannot enable without passing test)
- [ ] JIT assigns tenant default role + persists saml_name_id (extends ticket 02)
- [ ] Tests: per-tenant driver config, JIT role assignment, cross-tenant login rejection, fake-IdP harness extended for multi-IdP
