# 13: Reports — Depreciation & Audit Trail Pages

**What to build:** Management/auditors get two dedicated report surfaces: a depreciation report (book value, accumulated depreciation, per period — reconciliation to zero against manual accounting) and a cross-asset audit trail page (filterable log of every change, per tenant). Both are dense, printable, solid-surface data views with server-side pagination and filters.

**Blocked by:** 05: Asset Lifecycle (audit logs exist); 06: Depreciation Engine (depreciation runs exist)

**Status:** ready-for-agent

- [ ] Depreciation report: period selector, totals row, per-asset table (mono figures, right-aligned), CSV/PDF export via dompdf
- [ ] Audit trail page: filterable by user/action/model/date range, paginated, timeline-style entries
- [ ] Both pages follow solid-surface data rules (no glass on data tables) with mobile stacked-card fallback
- [ ] Tests: report totals match asset data exactly, audit filters, pagination contracts, tenant isolation
