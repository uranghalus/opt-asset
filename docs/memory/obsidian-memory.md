# Opti-Asset — Agent Memory

_Terakhir diperbarui: 2026-09-25 (sesi perencanaan AMS v2 — research, grill, to-tickets)._

## Keputusan arsitektur & konvensi (binding)

- **Tenancy:** stancl/tenancy single-database mode + **fail-closed wrapper** — stock `BelongsToTenant` milik stancl terdokumentasi fail-open (unscoped saat tidak ada tenant context); wrapper + isolation tests wajib menutup celah itu. Tenant di-resolve dari user SSO via middleware. Composite unique `(tenant_id, kode_asset)` dll. di level DB untuk semua tabel domain.
- **Barcode:** Code128 + kode_asset human-readable di bawah bars (picqer/php-barcode-generator → SVG → dompdf); batch via queue; `barcode_value` unik per tenant.
- **Depreciation:** bulanan (scheduled, idempotent per tenant+period), **straight-line only** MVP tapi service di belakang enum metode; floor di residu; on-demand recalc saat asset create/update.
- **Auth:** SSO SAML only (epic #1 selesai, Fortify dihapus total); JIT user dapat role default least-privilege (T02).
- **SSR wajib** + Inertia v3 partial reloads (`router.reload({ only: [...] })`) untuk pagination/filter/search; ledger solid-surface, chrome glass (rules §2); PHPUnit only.

## Status proyek (2026-09-25)

- SAML SSO live di `feature/saml-sso` (epic #1, tiket #2–#7, PR #8 open, jangan merge tanpa approval).
- Dashboard shell acrux-style ada (masih mock data); domain layer kosong (hanya model `User`).
- **Plan & tiket:** `docs/PROJECT-PLAN.md` aktif; 14 tiket T01–T14 terpublikasi ke GitHub **#9–#22** (`ready-for-agent`), blocking edges di body `Blocked by` (native dependency API tidak reachable dari environment — `gh` absent, MCP GitHub tak punya tool dependency; fallback sesuai `docs/agents/issue-tracker.md`).
- **Frontier:** T01 (#9) Tenancy Foundation — belum mulai; user memilih tidak implement di sesi ini.
- Draft lokal tiket tetap di `.scratch/ams-v2/issues/` (sinkron konsep dengan #9–#22).

## Risiko & catatan sesi berikutnya

- Isolasi tenant = defect class tertinggi; setiap tiket yang menyentuh query domain wajib isolation test (rules §1.1).
- dompdf batch harus chunked di queue; progress via partial reload.
- PR #8 masih open — strategi merge/rebase T02/T09 konfirmasi dulu.
- Bila Obsidian MCP ter-connected nanti, konten `docs/memory/obsidian-memory.md` ini justru yang dipakai/di-extend ke vault (jangan duplikasi struktur).
