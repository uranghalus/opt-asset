# 05: Asset Lifecycle — Mutations, Disposal, History Timeline, Audit Trail

**What to build:** An officer can move an asset between locations (mutation), dispose/retire it with approval info, and every one of those events lands in both the asset's history timeline and the tenant-wide audit trail. Disposal before end of useful life records remaining book value as a write-off in history rather than deleting it.

**Blocked by:** 04: Asset Core

**Status:** ready-for-agent

- [ ] `asset_mutations` (from/to location, mutated_by, note) + mutation action on detail page and bulk bar
- [ ] `asset_disposals` (date, reason, approved_by) with confirm dialog; guard: disposal of already-disposed asset rejected
- [ ] Write-off accounting: remaining `nilai_buku` recorded as write-off event on disposal
- [ ] `asset_histories` (event_type, payload JSON) written for created/mutated/disposed events; timeline UI on asset detail
- [ ] `audit_logs` (tenant_id, user_id, action, model, model_id, changes JSON) via model-observer; tenant-scoped audit page
- [ ] Tests: lifecycle state transitions, write-off value correctness, audit payload contents, tenant isolation of histories/logs
