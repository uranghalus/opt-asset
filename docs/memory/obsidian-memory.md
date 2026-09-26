# Opti-Asset — Agent Memory

_Terakhir diperbarui: 2026-09-26 (T01 + T01b selesai — tenancy foundation & platform tenant CRUD)._

## Keputusan arsitektur & konvensi (binding)

- **Tenancy:** stancl/tenancy single-database mode + **fail-closed wrapper** — stock `BelongsToTenant` milik stancl terdokumentasi fail-open (unscoped saat tidak ada tenant context); wrapper + isolation tests wajib menutup celah itu. Tenant di-resolve dari user SSO via middleware. Composite unique `(tenant_id, kode_asset)` dll. di level DB untuk semua tabel domain.
- **Tenancy (terlaksana T01, 2026-09-26):** `stancl/tenancy` v3.10.1. `App\Tenancy\FailClosedTenantScope` (no context → `1=0`, tidak pernah unscoped) + `App\Concerns\BelongsToTenant` (scope fail-closed; create tanpa context → `TenantContextRequiredException`; `tenant_id` dari request selalu ditimpa). `App\Tenancy\TenantContext` satu pintu (initialize/end/check/id, `initializeFromUser()` menolak tenant non-`active`, mirror `Context::add('tenant.id')` untuk queue). Middleware alias `tenant` — JANGAN global web (SAML endpoints & logout harus di luar context); user tanpa tenant → 403. `tenants.id` = **ULID** (`App\Tenancy\UlidIdentifierGenerator`), tabel `tenants` wajib kolom `data` JSON (VirtualColumn) + override `getCustomColumns()` di model untuk kolom nyata (id/code/name/status). Config: Database/Cache/Filesystem bootstrapper OFF, Queue ON. Middleware chain penting: `auth` → `tenant` → `verified` → HandleInertia.
- **Barcode:** Code128 + kode_asset human-readable di bawah bars (picqer/php-barcode-generator → SVG → dompdf); batch via queue; `barcode_value` unik per tenant.
- **Depreciation:** bulanan (scheduled, idempotent per tenant+period), **straight-line only** MVP tapi service di belakang enum metode; floor di residu; on-demand recalc saat asset create/update.
- **Auth:** SSO SAML only (epic #1 selesai, Fortify dihapus total); JIT user dapat role default least-privilege (T02).
- **SSR wajib** + Inertia v3 partial reloads (`router.reload({ only: [...] })`) untuk pagination/filter/search; ledger solid-surface, chrome glass (rules §2); PHPUnit only.

## Status proyek (2026-09-26)

- **T01c (#24) SELESAI** — multi-membership + tenant switcher (grill 26-09): pivot `tenant_memberships` (user_id, tenant_id, is_default; SENGAJA tidak di-scope tenant — prasyarat switching), audit `tenant_switches`; `users.tenant_id` DIHAPUS (backfill ke membership default). Tenant aktif = session `tenant.active_id` (per-device), resolve: session → default membership → fail; suspend selalu gagal-closed. **Platform admin = 0 membership + email di `PLATFORM_ADMIN_EMAILS`** (config/platform.php) — menutup lubang lama "semua JIT user = platform admin"; `.env` lokal: superadmin@appdutamall.com. Switching: `POST tenant/switch` + switcher di user menu; setiap switch ter-audit (from = tenant efektif, bukan pointer mentah). T02 konsekuensi: role_id DI PIVOT membership (bukan user.role_id).
- **Data live (tinker, 26-09):** superadmin@appdutamall.com = member default DMB (bukan lagi platform admin — akses /platform/tenants hanya via email lain di allowlist). Migrasi paralel `nullify_superadmin_tenant_id` dihapus (usang, tak pernah di-commit).

- SAML SSO live di `feature/saml-sso` (epic #1, tiket #2–#7, PR #8 open, jangan merge tanpa approval).
- Dashboard shell acrux-style ada (masih mock data).
- **T01 (#9) SELESAI** di `feature/saml-sso`: Tenant model + fail-closed scoping + harness isolasi (12 test) — 45 test hijau, pint/phpstan bersih; komitmen per tiket dimulai dari tiket ini. UI skills (impeccable/ui-ux-pro-max, shadcn) mulai dipakai dari T03 (halaman klasifikasi pertama).
- **T01b (#23, baru) SELESAI** — Platform Tenant CRUD di `/platform/tenants` (grill 2026-09-26: full CRUD ditarik maju dari T09; area admin terpusat di luar middleware `tenant`; platform admin = akun bootstrap `tenant_id = null`; tanpa hard delete — transisi status saja). Konvensi baru: paginasi Laravel di props Inertia itu **flat** (`tenants.total`, bukan `meta.total`); `<Form>` Inertia wajib spread `{...action.form()}`; kedua middleware context (`tenant` & platform) diawali `TenantContext::end()` (aman Octane/worker/test in-process). Primitif shadcn `table` & `pagination` kini ada; halaman platform = pattern tabel solid + toolbar URL-state + dialog konfirmasi untuk aksi destruktif.
- **Frontier berikutnya:** T02 (#10) RBAC Skeleton — blocked oleh T01 saja → terbuka; T03 (#11) Classification Chain terbuka setelah T02. `gh` CLI berfungsi di environment ini (GitHub MCP **Bad credentials** — jangan pakai untuk repo ini). Screenshot UI perlu IdP lokal / keputusan backdoor dev — sengaja tidak dibuat (rules §1.2).
- Draft lokal tiket tetap di `.scratch/ams-v2/issues/` (sinkron konsep dengan #9–#22).

## Risiko & catatan sesi berikutnya

- Isolasi tenant = defect class tertinggi; setiap tiket yang menyentuh query domain wajib isolation test (rules §1.1).
- dompdf batch harus chunked di queue; progress via partial reload.
- PR #8 masih open — strategi merge/rebase T02/T09 konfirmasi dulu.
- Bila Obsidian MCP ter-connected nanti, konten `docs/memory/obsidian-memory.md` ini justru yang dipakai/di-extend ke vault (jangan duplikasi struktur).
