# 04: Asset Core — Auto-Generated kode_asset + Ledger Index (Pagination, Filter, Search)

**What to build:** An asset officer can register an asset by picking the 4-level classification chain, see `kode_asset` generated automatically from the chain codes + sequence (read-only), and manage everything from the primary asset ledger: a dense solid-surface index with server-side pagination, cascading classification filters, status chips, text search, and a contextual bulk-action bar. On mobile the ledger degrades to stacked key-column cards, not a horizontal-scrolling table.

**Blocked by:** 03: Classification Chain

**Status:** ready-for-agent

- [ ] `assets` table per PRD §7 (tenant_id, item_id, kode_asset, asset_type enum aktiva_tetap|peralatan, nilai_perolehan, masa_manfaat_bulan, metode_penyusutan, akumulasi_penyusutan, nilai_buku, status, lokasi fields) with composite unique `(tenant_id, kode_asset)`
- [ ] `kode_asset` generator: chain codes in fixed order (group→category→cluster→sub-cluster) + per-tenant sequence — never taken from user input
- [ ] Asset CRUD with cascading classification selector; live depreciation preview on the form when type = aktiva_tetap
- [ ] Ledger index: server-side pagination + search (name/kode) + cascading filters via Inertia partial reloads; skeleton loading rows; empty state with tenant name
- [ ] Tenant Identity Bar (P2) permanently visible; mono font for codes/amounts, right-aligned tabular figures
- [ ] Tests: code generation per tenant, uniqueness conflict, pagination/filter/search contracts, tenant isolation
