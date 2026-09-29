# 12: Dashboard — Real Tenant Aggregates in the Glass Shell

**What to build:** The existing acrux-style glass dashboard stops being a mock: metric cards, chart, activity list, and category analysis now read live data scoped to the acting tenant. A brand-new tenant sees its onboarding empty state ("Mulai dengan menambahkan aset pertama"); a failing aggregate degrades per-card ("—") without dropping the whole dashboard.

**Blocked by:** 05: Asset Lifecycle (history/activity data); 06: Depreciation Engine (book value trends); 08: Scan (mobile tab bar parity)

**Status:** ready-for-agent

- [ ] Replace mock chart/stat/activity data with Inertia props computed server-side (eager-loaded aggregates, no N+1)
- [ ] Deferred props + skeleton states for the heavy aggregates per Inertia v3 pattern
- [ ] Category analysis and gauge read real classification/asset distributions
- [ ] Empty state for zero-asset tenants; per-card failure degradation (independent "—" + retry)
- [ ] Tests: aggregate correctness per tenant, empty tenant state, cross-tenant isolation of every number on the page
