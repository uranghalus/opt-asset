# PRD — Asset Management System v2 (Extend: Multi-Tenant, SSO, Barcode, AI Depreciation)

**Status:** Draft — living document **Stack:** Laravel + Inertia React + MySQL **Basis:** Extend dari PRD AMS existing (CRUD asset, klasifikasi berantai, mutasi, disposal, RBAC, audit trail)

---

## 1. Problem Statement

Perusahaan yang menaungi beberapa entitas/anak perusahaan saat ini mengelola data aset secara manual atau tersebar per sistem/instalasi berbeda. Ini merugikan tiga pihak:

- **Staf aset & manajemen keuangan** — tidak ada satu sumber data yang mencatat klasifikasi, status, mutasi, dan riwayat penyusutan aset secara konsisten lintas entitas perusahaan, sehingga rekonsiliasi nilai buku aset (akuntansi) rawan salah dan lambat.
- **Petugas lapangan** — identifikasi fisik aset masih manual (cek label/dokumen kertas), memperlambat proses audit, mutasi, dan verifikasi saat stock opname.
- **Tim IT & keamanan** — setiap entitas/tenant butuh instance terpisah atau akun login terpisah dari sistem SSO korporat, menambah beban maintenance dan risiko shadow credential (akun lokal di luar SSO korporat).

Extend ini menambahkan: multi-tenancy (banyak perusahaan dalam satu instance), autentikasi wajib via SSO SAML korporat, barcode (scan & generate batch) untuk percepatan identifikasi fisik, dan kalkulasi/prediksi penyusutan aset.

---

## 2. Target User — 2 Persona

**Persona 1 — Staf Aset (Asset Officer), per tenant**

- Bertanggung jawab atas klasifikasi (golongan → kategori → kelompok → sub kelompok), input & coding aset, mutasi antar lokasi, proses disposal, cetak/tempel barcode fisik.
- Kebutuhan utama: input cepat, scan barcode untuk lookup instan, generate barcode batch saat terima banyak aset baru sekaligus, tidak perlu login terpisah dari akun korporat (SSO).

**Persona 2 — System/Tenant Admin (IT Korporat)**

- Mengelola provisioning tenant baru (perusahaan/entitas baru masuk sistem), konfigurasi SSO SAML per tenant, RBAC, dan memantau isolasi data antar tenant.
- Kebutuhan utama: onboarding tenant baru tanpa deploy ulang, jaminan tidak ada data bocor antar tenant, audit trail lintas tenant untuk kepatuhan.

---

## 3. Goals & Non-Goals

**Goals**

- Satu instance aplikasi melayani banyak perusahaan (tenant) dengan isolasi data penuh pada level query (single database, `tenant_id` scoping).
- Login 100% via SSO SAML korporat — tidak ada auth lokal Laravel default untuk user tenant.
- Barcode: generate (single & batch) dan scan untuk lookup detail aset dalam \< 2 detik.
- Penyusutan aset dihitung otomatis dengan formula akuntansi standar (baseline dari FR eksisting: nilai perolehan, masa manfaat, metode & akumulasi penyusutan, nilai buku).
- Menjaga baseline fitur existing: CRUD asset & klasifikasi berantai (kode berjenjang), mutasi, disposal, histori, RBAC, audit trail.

**Non-Goals (fase ini)**

- Fitur AI predictive (di luar formula akuntansi standar) — **lihat Open Question #2**, belum masuk MVP karena requirement belum jelas.
- Integrasi ERP/HRIS, IoT/RFID, procurement, maintenance management, loan/return system, native mobile app.
- Digital signature, notifikasi otomatis (email/push) — tetap out of scope kecuali dinyatakan lain.
- Billing/subscription antar tenant (multi-tenant di sini murni isolasi data operasional, bukan model SaaS berbayar) — **lihat Open Question #3**.

---

## 4. User Stories

1. Sebagai **Staf Aset**, saya ingin login otomatis via SSO SAML korporat, supaya saya tidak perlu akun/password terpisah dan akses langsung sesuai role saya.
2. Sebagai **Staf Aset**, saya ingin men-scan barcode aset, supaya saya bisa langsung melihat detail data aset tanpa mencari manual di sistem.
3. Sebagai **Staf Aset**, saya ingin generate barcode secara batch untuk banyak aset sekaligus (dengan opsi pilih aset mana saja), supaya saat penerimaan aset massal saya tidak perlu generate satu per satu.
4. Sebagai **Staf Aset**, saya ingin sistem menghitung nilai penyusutan aset otomatis berdasarkan metode akuntansi standar, supaya laporan nilai buku aset selalu akurat tanpa hitung manual.
5. Sebagai **System Admin**, saya ingin provisioning tenant (perusahaan) baru tanpa deploy ulang aplikasi, supaya onboarding entitas baru cepat.
6. Sebagai **System Admin**, saya ingin setiap query data aset otomatis ter-scope ke tenant yang login, supaya tidak ada risiko satu perusahaan melihat data perusahaan lain.
7. Sebagai **System Admin**, saya ingin mengatur konfigurasi SSO SAML per tenant (karena tiap perusahaan bisa punya IdP berbeda), supaya proses login tetap terisolasi per entitas.
8. Sebagai **Staf Aset**, saya ingin kode aset tetap auto-generate dari rantai golongan→kategori→kelompok→sub kelompok (baseline existing), supaya konsistensi coding tetap terjaga meski sistem sekarang multi-tenant.
9. Sebagai **Manajemen/Auditor**, saya ingin melihat riwayat & audit trail aset per tenant, supaya proses audit keuangan tetap valid secara terpisah per entitas.

---

## 5. Daftar Fitur — MVP / v2 / Nanti

### MVP (fase extend ini)

| # | Fitur | Catatan |
| --- | --- | --- |
| 1 | Multi-tenant (single database, `tenant_id` scoping) | Perusahaan berbeda dalam 1 instance |
| 2 | SSO SAML login (menggantikan auth Laravel default) | IdP sudah tersedia, tinggal integrasi |
| 3 | Generate barcode — single & batch (dengan pemilihan aset) |  |
| 4 | Scan barcode → detail data aset |  |
| 5 | Kalkulasi penyusutan otomatis (formula akuntansi standar) | Baseline dari FR-13 existing, dipastikan tenant-aware |
| 6 | Baseline existing: CRUD asset, klasifikasi berantai + kode berjenjang, CRUD item, mutasi, disposal, histori, RBAC, audit trail, dashboard/reporting | Dipastikan seluruhnya tenant-aware |

### v2

| # | Fitur | Catatan |
| --- | --- | --- |
| 1 | Prediksi kapan aset harus dimusnahkan berbasis data historis (true predictive, bukan sekadar formula) | Butuh data historis kondisi/perbaikan aset — belum ada di MVP |
| 2 | Tenant-level branding/kustomisasi ringan (logo, penamaan lokasi) |  |
| 3 | Self-service tenant provisioning UI untuk System Admin | MVP bisa manual/seed, v2 baru self-service |

### Nanti (belum diprioritaskan)

- Fitur AI lain yang disebut user tapi belum terdefinisi (lihat Open Question #2)
- Notifikasi otomatis (reminder masa manfaat habis, dsb.)
- Billing/subscription antar tenant jika model bisnis berubah jadi SaaS berbayar

---

## 6. Functional Requirements — Detail per Fitur MVP

**FR-14. Multi-Tenancy (single database)**

- Setiap tabel domain (assets, asset_groups/categories/clusters/sub_clusters, items, mutations, disposals, histories, barcodes) wajib memiliki kolom `tenant_id`.
- Global scope Eloquent otomatis menambahkan filter `tenant_id` pada setiap query berdasarkan tenant user yang sedang login — tidak boleh ada query domain yang lolos tanpa scope ini (wajib code review/test khusus).
- Kode aset (auto-generate dari rantai klasifikasi) tetap unik **per tenant**, bukan global — dua tenant boleh punya kode aset yang identik.
- Klasifikasi (golongan/kategori/kelompok/sub kelompok) di-setup terpisah per tenant — tidak ada data klasifikasi bersama antar tenant, kecuali dinyatakan lain (lihat Open Question #4).

**FR-15. SSO SAML Login**

- Auth Laravel default (email/password lokal) dinonaktifkan untuk user tenant; login hanya via SAML assertion dari IdP korporat.
- Mapping atribut SAML (NameID/email, role/group) ke user & role internal aplikasi — attribute mapping harus dikonfirmasi dengan detail IdP (lihat Open Question #5).
- Provisioning user: opsi Just-In-Time (JIT) — user baru otomatis dibuat saat login SAML pertama kali, dengan role default minimal (least privilege), lalu di-assign role sebenarnya oleh Admin.
- Setiap tenant bisa punya konfigurasi IdP SAML berbeda (multi-IdP) — disimpan di tabel `sso_configurations` per `tenant_id`.
- Session tetap tenant-scoped; user tidak bisa switch tenant tanpa re-autentikasi (kecuali role super-admin lintas tenant, jika ada — lihat Open Question #6).

**FR-16. Generate Barcode (Single & Batch)**

- Generate barcode untuk 1 aset (single) dari halaman detail aset.
- Generate barcode batch: user memilih beberapa aset (checkbox/filter) lalu generate sekaligus, output berupa file cetak (PDF layout label) berisi barcode + kode aset + nama aset.
- Format barcode: Code128 atau QR (mengandung `tenant_id` + `asset_id` atau kode aset unik per tenant) — perlu konfirmasi format (lihat Open Question #7).
- Barcode disimpan referensinya di tabel `barcodes`, unik per tenant.

**FR-17. Scan Barcode → Detail Aset**

- Endpoint/halaman scan (input dari kamera device atau scanner hardware sebagai keyboard input) menerima kode barcode, mem-parsing `tenant_id` (implisit dari sesi login) + kode aset, lalu redirect/tampilkan halaman detail aset.
- Jika kode tidak ditemukan dalam tenant yang sedang login → tampilkan error jelas ("Aset tidak ditemukan di tenant ini"), bukan generic error.

**FR-18. Kalkulasi Penyusutan (Formula Akuntansi Standar)**

- Berlaku untuk aset bertipe "Aktiva Tetap" (baseline dari FR-13 existing): field nilai perolehan, masa manfaat, metode penyusutan (garis lurus sebagai default — metode lain jika dikonfirmasi), akumulasi penyusutan, nilai buku.
- Kalkulasi berjalan otomatis (scheduled job bulanan/tahunan — perlu konfirmasi periode, lihat Open Question #8) dan tercatat sebagai histori, tenant-aware.
- Nilai buku tidak boleh negatif — floor di nilai residu (default 0, atau field residu jika ada).

---

## 7. Sketsa Data Model (Entitas + Field Kunci)

**tenants** `id, name, code, status, created_at`

**sso_configurations** `id, tenant_id, idp_entity_id, idp_sso_url, idp_x509_cert, attribute_mapping (json), created_at`

**users** `id, tenant_id, name, email, saml_name_id, role_id, status, last_login_at`

**roles / permissions** (RBAC — baseline existing) `roles: id, tenant_id, name` · `permissions: id, name` · `role_permission: role_id, permission_id`

**asset_groups (golongan)** `id, tenant_id, code, name`

**asset_categories (kategori)** `id, tenant_id, asset_group_id, code, name`

**asset_clusters (kelompok asset)** `id, tenant_id, asset_category_id, code, name`

**asset_sub_clusters (sub kelompok asset)** `id, tenant_id, asset_cluster_id, code, name`

**items** `id, tenant_id, asset_sub_cluster_id (nullable jika auto-create tanpa kategori), name`

**assets** `id, tenant_id, item_id, kode_asset (unik per tenant), asset_type (aktiva_tetap|peralatan), nilai_perolehan, masa_manfaat_bulan, metode_penyusutan, akumulasi_penyusutan, nilai_buku, status, lokasi_id, created_at`

**barcodes** `id, tenant_id, asset_id, barcode_value (unik per tenant), format (code128|qr), generated_at, generated_by`

**asset_mutations** `id, tenant_id, asset_id, from_location_id, to_location_id, mutated_by, mutated_at, note`

**asset_disposals** `id, tenant_id, asset_id, disposal_date, reason, approved_by`

**asset_histories** `id, tenant_id, asset_id, event_type (created|mutated|disposed|depreciation_run|...), payload (json), created_at`

**audit_logs** `id, tenant_id, user_id, action, model, model_id, changes (json), created_at`

---

## 8. Edge & Failure States

- **Query tanpa tenant scope** — bug paling kritis; wajib automated test yang memastikan setiap query model domain selalu ter-filter `tenant_id`. Kegagalan di sini = data leak antar perusahaan.
- **SSO SAML gagal/IdP down** — perlu fallback plan (mis. akses darurat khusus Super Admin) karena tanpa auth lokal, kegagalan IdP = seluruh tenant tidak bisa login. Perlu diputuskan (lihat Open Question #6).
- **Attribute mapping SAML tidak sesuai** (mis. email berubah/duplikat) — user gagal ter-mapping ke akun existing; perlu strategi re-linking manual oleh Admin.
- **Generate barcode batch dengan volume besar** (ratusan/ribuan aset sekaligus) — perlu queue/job asinkron, bukan proses sinkron yang bisa timeout.
- **Barcode duplikat / kode aset duplikat** — validasi unik per tenant harus dicek di level database (unique constraint composite `tenant_id + kode_asset`), bukan hanya di aplikasi.
- **Scan barcode dari tenant lain** (mis. user salah scan barcode fisik milik entitas lain, kalau ada campur fisik aset) — harus ditolak dengan pesan jelas, bukan menampilkan data tenant lain.
- **Disposal sebelum masa manfaat habis** — nilai buku sisa harus tetap tercatat di histori sebagai kerugian pelepasan (write-off), bukan hilang begitu saja.
- **Import Excel lintas tenant** — proses import (fitur existing) harus eksplisit tenant-scoped; file import tidak boleh membawa `tenant_id` dari luar sesi login user yang sedang aktif.
- **Provisioning tenant baru tanpa data klasifikasi awal** — perlu keputusan apakah tenant baru mulai dari kosong atau ada template klasifikasi default yang bisa di-copy (lihat Open Question #4).

---

## 9. Success Metrics

- 0 insiden data bocor antar tenant (diverifikasi via automated test tenant-isolation, target 100% coverage query domain).
- ≥ 95% login user berhasil via SSO SAML tanpa intervensi manual Admin.
- Waktu rata-rata scan barcode → tampil detail aset \< 2 detik.
- ≥ 90% aset baru memiliki barcode ter-generate dalam 24 jam sejak input data.
- Selisih nilai buku hasil kalkulasi otomatis vs rekonsiliasi akuntansi manual = 0 (akurasi 100% pada sample audit bulanan).
- Waktu onboarding tenant baru (dari provisioning sampai user pertama bisa login) ≤ target yang disepakati (lihat Open Question #9).

---

## 10. Open Questions

1. **Poin 1 (relasi dengan PRD lama) belum lengkap** — jawaban terpotong ("...dan penjelasan lebih lanjut mengenai projek saya.."). Perlu klarifikasi: apakah ada detail tambahan proyek yang belum tersampaikan?
2. **Fitur "AI" yang dimaksud di luar penyusutan standar** — disebutkan ingin "ada fitur yg berhubungan dengan ai" tapi belum spesifik. Belum dimasukkan ke MVP karena requirement tidak jelas. Perlu didefinisikan: AI untuk apa persis (prediksi pemusnahan berbasis kondisi fisik? anomaly detection mutasi? chatbot pencarian aset)?
3. **Model bisnis multi-tenant** — "beberapa perusahaan berbeda pakai 1 instance": apakah ini murni internal grup perusahaan (holding & anak usaha), atau berpotensi jadi produk SaaS ke pihak eksternal (butuh billing, SLA per tenant, dll)?
4. **Data klasifikasi antar tenant** — apakah golongan/kategori/kelompok/sub kelompok sepenuhnya independen per tenant, atau ada template/master data yang bisa dibagikan/di-copy saat tenant baru dibuat?
5. **Detail teknis SSO SAML** — nama IdP (Azure AD/Entra ID, Okta, Keycloak, dll), metadata/endpoint yang tersedia, dan attribute mapping (field apa yang membawa role/group user) belum diberikan.
6. **Fallback saat IdP down & role lintas tenant** — perlu mekanisme akses darurat, dan apakah ada role Super Admin yang bisa lihat/kelola lintas semua tenant?
7. **Format barcode** — Code128 (linear) atau QR? Apakah perlu human-readable text di label cetak, dan ukuran/label printer yang dipakai?
8. **Periode kalkulasi penyusutan otomatis** — bulanan, tahunan, atau saat aset diakses (on-demand)?
9. **Target waktu onboarding tenant baru** — belum ada angka target untuk metrik ini.