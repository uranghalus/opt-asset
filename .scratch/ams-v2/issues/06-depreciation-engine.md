# 06: Depreciation Engine — Scheduled Monthly Run + On-Demand Recalculation

**What to build:** Book value is always current without manual math: a scheduled monthly command depreciates every aktiva_tetap asset of every tenant (straight-line, floored at residual), on-demand recalculation runs after asset edits, and every run is recorded in history. A depreciation report page summarizes acquisition cost, accumulated depreciation, and book value per period — matching manual reconciliation to zero.

**Blocked by:** 04: Asset Core (05 not required — report reads assets + histories)

**Status:** ready-for-agent

- [ ] Straight-line calculation service: monthly = (cost − residual) / life months; accumulated capped so nilai_buku never goes below residual
- [ ] Scheduled monthly Artisan command iterating tenants; per-tenant `depreciation_run` history events; queue-friendly batching
- [ ] On-demand recalculation service invoked on asset create/update (life/cost changes recompute from zero)
- [ ] Depreciation report page (period selector, totals, per-asset table, solid surface)
- [ ] Tests: exact worked-example amounts, floor-at-residual, idempotent runs, tenant-isolated aggregates
