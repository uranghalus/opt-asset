# Design Brief — Asset Management System v2

**Basis:** PRD AMS v2 (Extend: Multi-Tenant, SSO SAML, Barcode, Penyusutan Otomatis) **Status:** Draft — direvisi ke tema glassmorphism dual-mode (light/dark), lihat `DESIGN.md` untuk token mesin-baca

---

## Konteks Subjek (dasar semua keputusan di bawah)

Ini bukan produk konsumen — ini alat kerja internal yang dipakai staf aset berjam-jam per hari di lapangan (scan barcode, sambil bawa scanner/device) dan oleh admin IT di meja kerja (setup SSO, RBAC, provisioning tenant). Dua dunia rujukan yang konkret dan relevan:

1. **Ledger akuntansi** — sistem ini menyimpan nilai perolehan, penyusutan, nilai buku. Presisi angka dan keterbacaan tabel adalah inti, bukan hiasan.
2. **Label/tag aset fisik** — barcode, kode aset berjenjang, kondisi fisik. Ini dunia gudang/inventaris, bukan dunia marketing SaaS.

Semua keputusan visual di bawah ditarik dari dua dunia ini, bukan dari template dashboard generik.

---

## 1. Design Principles (wajib dipatuhi)

**P1 — Densitas data menang atas keindahan kosong.** Staf aset & auditor butuh melihat banyak baris data sekaligus (daftar aset, riwayat, log audit). UI tidak boleh mengorbankan jumlah informasi per layar demi whitespace besar ala landing page. Tabel adalah komponen utama, bukan kartu.

**P2 — Setiap tenant harus terasa sebagai ruang terisolasi, bukan sekadar filter tersembunyi.** Karena kesalahan tenant-scoping = kebocoran data (risiko #1 di PRD), UI wajib menampilkan identitas tenant aktif secara permanen dan mencolok (nama tenant selalu terlihat di header) — bukan sekadar dropdown kecil yang bisa terlewat. Ini prinsip keamanan yang diterjemahkan ke visual, bukan gaya bebas.

**P3 — Barcode dan kode aset adalah data kritis, harus tampil dalam font tabular/monospace dan berukuran cukup besar untuk dibaca cepat oleh scanner/mata, di posisi paling menonjol pada kartu/baris aset — tidak boleh diperlakukan sebagai teks sekunder.**

---

## 2. Visual Direction

> **Revisi:** Arah awal brief ini adalah flat/ledger-industrial. Berdasarkan preferensi eksplisit, tema diganti ke **glassmorphism dual-mode** (light & dark, wajib dua-duanya), diadaptasi dari referensi AURORA. Token lengkap & mesin-baca ada di `DESIGN.md` — bagian ini menjelaskan alasan & batasannya.

**Mood:** Aurora-ledger. Glassmorphism tetap harus terasa seperti alat kerja, bukan halaman promosi — jadi glass dipakai sebagai identitas visual pada *chrome* (nav, modal, card ringkasan), sementara data (tabel, form, daftar) tetap solid/opaque. Ini bukan kompromi setengah hati; ini aturan wajib supaya P1 (densitas data menang) tidak dikorbankan demi tren visual.

**Referensi konkret:**

- AURORA — After-Dark Sign-In (glassmorphism, gradient aurora, dual light/dark) sebagai basis efek kaca, gradient, dan palet aksen violet/teal.
- Buku besar/ledger akuntansi klasik (kolom rapi, angka rata kanan) — tetap jadi acuan untuk SEMUA permukaan data (tabel, form, daftar riwayat), yang sengaja solid, bukan glass.
- Label pengiriman/inventaris gudang (kode tebal, monospace) — tetap jadi acuan untuk representasi kode aset/barcode di dalam kartu glass sekalipun (lihat P3).

**Aturan pemisahan lapisan (wajib, menggantikan poin "hindari shadow" versi lama):**

- **Glass** → sidebar, header, modal/dialog, card ringkasan/metrik di Dashboard, preview label barcode.
- **Solid** → semua Data Table, semua form input, Attribute Mapping (SSO). Blur di belakang ratusan baris angka merusak kecepatan baca — ini bukan gaya, ini keterbacaan.

**Yang tetap dihindari secara eksplisit (masih berlaku meski tema berganti):**

- Background krem hangat + serif kontras tinggi + aksen terracotta (\~#D97757) — ciri khas desain generik AI; gradient aurora yang dipakai di sini punya hue berbeda (violet/teal/navy) dan alasan tersendiri (lihat `DESIGN.md`), bukan default template.
- Glass diterapkan ke SEMUA elemen tanpa pandang bulu (tabel ikut blur, form ikut translucent) — ini generic glassmorphism tell yang justru merusak fungsi alat kerja; lihat aturan pemisahan lapisan di atas.
- Label ALL CAPS bertracking lebar di atas setiap heading, meta text dengan middle dot (·), tombol dengan "→" di akhir teks — semua chrome template ini tidak dipakai.
- Animasi dekoratif berjalan terus-menerus (parallax, keyframe loop) sebagai pengisi ruang kosong — motion glass di sini hanya untuk transisi state (buka modal, ganti tema, hasil scan), bukan hiasan latar.
- Neumorphism (soft-UI dengan shadow ganda cekung/cembung) — sering tercampur dengan glassmorphism di template generik, tapi tidak dipakai di sini; efek kaca di brief ini murni backdrop-blur + border tipis + ambient shadow, bukan neumorphic.

---

## 3. Design Tokens

> Sumber kebenaran mesin-baca (dipakai langsung oleh kode/dev tools) ada di `DESIGN.md`. Tabel di bawah adalah versi human-readable + alasan desainnya, disinkronkan dengan token di sana. Nama token lama (`graphite-900`, `ledger-600`, dst.) sudah diganti mengikuti `DESIGN.md`.

### Warna — Light & Dark (wajib dua-duanya)

| Token | Light | Dark | Peran | Alasan |
| --- | --- | --- | --- | --- |
| `bg-base` | `#E8ECF8 → #F3F0FF` (gradient) | `#060918 → #0D1230` (gradient) | Background utama | Gradient aurora — identitas glass theme, bukan solid flat lagi |
| `surface-glass` | `rgba(255,255,255,0.55)` | `rgba(255,255,255,0.06)` | Chrome: nav, modal, card ringkasan | Translucent + blur; TIDAK dipakai di tabel/form (lihat aturan pemisahan lapisan §2) |
| `surface-solid` | `#FFFFFF` | `#12172B` | Tabel data, form input | Tetap opaque penuh — menjaga P1 (densitas & keterbacaan angka) di tengah tema glass |
| `text-primary` / `text-secondary` | `#131B2E` / `#5B6478` | `#E8ECF8` / `#8B93B5` | Teks utama/meta |  |
| `accent-primary` (violet) | `#8A6CFF` | `#9B87FF` | CTA utama, link, brand | Diambil dari AURORA; violet dipilih sebagai warna brand baru (bukan hijau ledger lama) karena tema kini eksplisit "aurora glass", bukan ledger-flat |
| `accent-teal` | `#12B597` | `#3EE6C4` | Aksi scan/verifikasi berhasil | Dipertahankan dari fungsi lama "tag-amber untuk aksi fisik", tapi digeser ke teal (AURORA) — tetap satu warna khusus untuk aksi scan/verify supaya tidak tertukar dengan CTA umum |
| `success` / `warning` / `danger` | `#1F9D6F` / `#B5721F` / `#C23A3A` | `#34D399` / `#F2B84B` / `#FF6B6B` | Status aset (aktif/menunggu/disposal) | Peran sama seperti versi lama (ledger-600/tag-amber-600/rust-600), warnanya disesuaikan agar tetap kontras di atas glass terang maupun gelap |
| `border-glass` / `border-solid` | `rgba(255,255,255,0.6)` / `#DDE1EC` | `rgba(255,255,255,0.12)` / `#2A3152` | Garis panel glass / garis tabel-form | Dua jenis border terpisah — glass border harus translucent, solid border tetap opaque tegas seperti brief awal |

Status badge (derivasi dari palet di atas): Aktif = `success` tint; Dalam Mutasi = `accent-teal` tint; Disposal/Nonaktif = `danger` tint; Menunggu Sinkron SSO = `warning` tint. Badge selalu di atas `surface-solid` (bukan glass) supaya warnanya stabil dan kontrasnya terjamin — lihat §10 Accessibility.

### Tipografi

- **UI/body: IBM Plex Sans.** Dipilih karena punya karakter enterprise-teknis yang jujur (dirancang untuk software IBM), grotesque tapi hangat, beda dari default Inter/Roboto yang jadi pilihan otomatis kebanyakan dashboard. Cocok untuk staf yang bekerja lama membaca form & tabel.
- **Data/kode: IBM Plex Mono.** Dipakai KHUSUS untuk: kode aset, nilai barcode, angka mata uang/penyusutan di tabel (agar digit rata/tabular), dan log audit. Ini bukan gaya dekoratif — Plex Mono punya digit tabular native sehingga kolom angka di ledger sungguh rata, dan kode aset/barcode terbaca tanpa ambigu (0 vs O, 1 vs l dibedakan jelas — penting untuk barcode manual re-entry).
- Tidak pakai typeface ketiga untuk display — dua keluarga ini sudah cukup kontras (grotesque humanis vs monospace teknis).

**Skala tipografi** (basis 14px, rasio \~1.25, dioptimalkan untuk UI padat bukan halaman marketing):

| Level | Ukuran | Weight | Line-height | Pemakaian |
| --- | --- | --- | --- | --- |
| Display | 28px | Semibold | 1.2 | Judul halaman utama (mis. "Daftar Aset") |
| H2 | 22px | Semibold | 1.25 | Judul section dalam halaman |
| H3 | 18px | Medium | 1.3 | Sub-section, judul kartu |
| Body | 14px | Regular | 1.5 | Teks default, isi tabel |
| Body-strong | 14px | Medium | 1.5 | Label penting, nilai kunci |
| Small | 12.5px | Regular | 1.4 | Meta, timestamp, caption |
| Mono-data | 13px | Regular (Plex Mono) | 1.4 | Kode aset, barcode value, nominal |

### Skala Spacing

Basis 4px (bukan 8px murni) — supaya tabel padat bisa dapat jarak presisi tanpa boros ruang: `4, 8, 12, 16, 20, 24, 32, 40, 48, 64`.

### Radius

Tetap dibedakan sengaja per hierarki — glassmorphism justru butuh ini, karena radius seragam di semua elemen (glass maupun solid) akan membuat tabel data ikut terasa "lembek":

- Input, button: `8px` (naik dari versi flat lama, supaya harmonis dengan lengkung glass card, tapi masih jelas beda dari card)
- Card/panel glass: `16px`
- Modal, dialog: `24px`
- Badge/pill: `999px` (pill) — umum di glassmorphism, dipakai untuk status badge & filter chip
- Tabel & container data (list solid): `4px` — sengaja tetap kecil/tegas, menandai "ini area data serius" secara visual di tengah UI yang glass
- Barcode label card (representasi cetak fisik): `0px` — tidak berubah, tetap meniru bentuk label cetak sungguhan

### Shadow & Blur

- **Glass surfaces:** `backdrop-blur` 20px (panel) / 10px (elemen bersarang di dalam glass lain) + ambient shadow lembut — `0 8px 32px rgba(19,27,46,0.12)` (light) / `0 8px 32px rgba(0,0,0,0.45)` (dark). Ini shadow ber-fungsi (menegaskan elevasi kaca), bukan shadow dekoratif generik.
- **Solid surfaces (tabel, form):** TIDAK pakai blur maupun shadow tebal — cukup `border-solid` 1px, sama seperti prinsip flat versi awal. Ini yang menjaga tabel tetap tegas dan cepat dibaca.
- **Glow aksen:** hanya pada hover tombol primary (`0 0 24px rgba(138,108,255,0.35)`) — bukan default state, supaya tidak ramai berkedip saat scroll list panjang.
- `prefers-reduced-transparency` → semua `surface-glass` fallback ke `surface-solid` penuh (blur off total, bukan cuma dikurangi).

---

## 4. Screen Inventory

| Screen | Tujuan |
| --- | --- |
| Login (SSO Redirect) | Titik masuk tunggal — redirect ke IdP tenant, tidak ada form password lokal |
| Dashboard | Ringkasan kondisi aset tenant aktif: jumlah aset per status, nilai buku total, aset butuh perhatian |
| Daftar Aset | Tabel utama seluruh aset tenant — pencarian, filter klasifikasi, aksi massal |
| Detail Aset | Semua data 1 aset: klasifikasi, nilai/penyusutan, lokasi, riwayat, barcode |
| Form Tambah/Edit Aset | Input data aset baru/ubah, termasuk pilih klasifikasi berjenjang |
| Manajemen Klasifikasi | Kelola rantai golongan→kategori→kelompok→sub kelompok + kode masing-masing |
| Generate Barcode (Batch) | Pilih banyak aset, generate & preview label cetak sekaligus |
| Scan Barcode | Input scan cepat → langsung ke detail aset |
| Mutasi Aset | Pindahkan aset antar lokasi, dengan histori |
| Disposal Aset | Proses pemusnahan/pelepasan aset, dengan approval |
| Riwayat & Audit Trail | Log semua perubahan aset, per tenant |
| Laporan Penyusutan | Ringkasan nilai buku, akumulasi penyusutan per periode |
| Admin — Konfigurasi SSO | Setup IdP SAML per tenant (entity ID, ACS URL, cert, attribute mapping) |
| Admin — Provisioning Tenant | Buat tenant baru, set klasifikasi awal |
| Admin — User & Role (RBAC) | Kelola role & permission per tenant |

---

## 5. User Flow — Journey Utama

**Flow A — Login via SSO**

1. User buka URL aplikasi → deteksi tenant dari subdomain/kode tenant.
2. Redirect otomatis ke IdP SAML tenant tsb (tanpa form login lokal).
3. User autentikasi di IdP (di luar kendali UI kita).
4. Redirect kembali dengan SAML assertion → JIT provisioning jika user baru (role default minimal) → masuk Dashboard.
5. **Failure branch:** IdP gagal/timeout → halaman error eksplisit "Tidak bisa terhubung ke sistem login \[nama tenant\]" + kontak admin, bukan generic 500.

**Flow B — Tambah aset baru → generate barcode**

1. Dari Daftar Aset → tombol primer "Tambah Aset".
2. Isi form: pilih klasifikasi berjenjang (golongan → kategori → kelompok → sub kelompok, tiap level memfilter pilihan level berikutnya) → kode aset otomatis muncul (read-only, hasil rantai kode).
3. Isi nilai perolehan, masa manfaat (jika Aktiva Tetap) → sistem otomatis tampilkan preview jadwal penyusutan.
4. Simpan → redirect ke Detail Aset dengan banner sukses.
5. Dari Detail Aset atau Daftar Aset (checkbox multi-select) → "Generate Barcode" → preview label → cetak/unduh PDF batch.

**Flow C — Scan barcode di lapangan**

1. Staf buka halaman Scan (bisa jadi landing setelah login di perangkat mobile/scanner).
2. Input field auto-focus menerima input scanner (keyboard emulation) atau kamera.
3. Sistem parse kode → cari dalam tenant aktif.
4. Ditemukan → langsung ke Detail Aset. Tidak ditemukan → pesan jelas "Aset tidak ditemukan di \[nama tenant\]" + opsi scan ulang, bukan redirect diam-diam.
5. Dari Detail Aset, staf bisa langsung ambil aksi cepat: Mutasi atau lihat Riwayat.

**Flow D — Admin: onboarding tenant baru + setup SSO**

1. Admin (lintas tenant) → Provisioning Tenant → isi nama, kode tenant.
2. Pilih: mulai dari klasifikasi kosong atau salin template klasifikasi dari tenant lain.
3. Lanjut ke Konfigurasi SSO → isi entity ID, SSO URL, cert IdP, attribute mapping (role/email).
4. Test koneksi SSO (tombol eksplisit, hasil pass/fail ditampilkan sebelum disimpan aktif).
5. Aktifkan tenant → tenant baru siap menerima login user.

---

## 6. Layout per Screen (section, hierarki, primary action, komponen)

**Dashboard**

- Section: Header (nama tenant + identitas user) → Ringkasan angka (kartu metrik: total aset, nilai buku total, aset perlu perhatian) → Tabel "Aktivitas Terbaru" → Grafik ringkas penyusutan per bulan.
- Primary action: "Tambah Aset" (top-right, selalu terlihat).
- Komponen: Metric Card (bukan gaya SaaS bulat besar — flat, border tipis, angka mono besar), Data Table ringkas, Chart line sederhana.

**Daftar Aset**

- Section: Header + search bar + filter klasifikasi (dropdown berjenjang) → Toolbar aksi massal (muncul saat ada seleksi: Generate Barcode, Mutasi) → Tabel data (kode aset mono, nama, klasifikasi, status badge, nilai buku rata kanan mono).
- Primary action: "Tambah Aset"; aksi massal muncul kontekstual.
- Komponen: Data Table (sortable header, sticky), Filter Chip, Status Badge, Bulk Action Bar.

**Detail Aset**

- Section: Header (kode aset besar mono + nama + status) → Tab: Info Umum | Penyusutan | Riwayat | Barcode.
- Primary action: "Mutasi" dan "Generate Barcode" sebagai tombol sekunder di header; "Edit" ikon.
- Komponen: Tab Nav, Key-Value Info List, Barcode Preview Card, Timeline (untuk riwayat).

**Generate Barcode (Batch)**

- Section: Panel kiri = tabel pilih aset (checkbox) → Panel kanan = preview label real-time (grid label sesuai layout cetak).
- Primary action: "Generate & Unduh PDF" (disabled sampai ada seleksi ≥1).
- Komponen: Data Table dengan checkbox, Barcode Label Card (mono, radius 0px sesuai token), Counter seleksi.

**Scan Barcode**

- Section: Full-focus single input (besar, auto-focus) → area hasil scan terakhir di bawahnya (riwayat scan sesi berjalan).
- Primary action: implisit — submit terjadi otomatis saat scan masuk (tidak perlu klik tombol).
- Komponen: Scan Input (besar, kontras tinggi, live region ARIA), Result Card, Recent Scan List.

**Admin — Konfigurasi SSO**

- Section: Pilih tenant → Form field IdP (entity ID, SSO URL, cert upload, attribute mapping table) → Tombol "Test Koneksi" → Status hasil test → "Aktifkan".
- Primary action: "Test Koneksi" sebelum "Simpan & Aktifkan" bisa ditekan (guard eksplisit — tidak boleh aktif tanpa test).
- Komponen: Form Section, Attribute Mapping Table (key-value editable), Connection Test Status Badge.

---

## 7. Component Library

| Komponen | Variant | State |
| --- | --- | --- |
| **Button** | Primary (ledger-600), Secondary (outline), Destructive (rust-600), Tag-Action (tag-amber-600, khusus aksi barcode/fisik) | default, hover, active, disabled, loading (spinner inline) |
| **Data Table** | Compact (tabel utama), Nested (untuk klasifikasi berjenjang) | default, sorted-column, row-selected, loading (skeleton row), empty |
| **Status Badge** | Aktif, Mutasi, Disposal, Menunggu Sinkron | default only (warna tetap, tidak interaktif) |
| **Barcode Label Card** | Single, Batch-grid item | preview, print-ready, generated |
| **Scan Input** | Standalone (halaman scan), Inline (dalam form pencarian) | idle, listening (border ledger-600 aktif), success-flash, error-flash |
| **Classification Selector** | 4-level cascading dropdown | default, level-locked (belum pilih parent), loaded, empty (parent belum punya child) |
| **Form Field** | Text, Number (mono untuk nominal), Select, Cascading Select, File Upload (cert SSO) | default, focus, error (rust-600 border + pesan), disabled, readonly (untuk kode aset auto-generate) |
| **Metric Card** | Angka besar, Angka + trend kecil | default, loading (skeleton) |
| **Timeline/Audit Log Item** | Event standar, Event kritis (disposal/error) | default, expanded |
| **Attribute Mapping Row** (khusus SSO config) | — | default, unmapped (warning), mapped |
| **Toast/Alert** | Success, Error, Info, Warning | enter, visible, dismiss |
| **Modal/Dialog** | Confirm (mis. disposal), Form-in-modal | default, loading-action |
| **Tenant Identity Bar** | — (selalu tampil sesuai Principle P2) | default only |
| **Empty State** | Per-screen custom illustration teks (bukan generic icon+text template) | default |

---

## 8. State — Empty / Loading / Error / Success / Offline

**Daftar Aset**

- Empty: "Belum ada aset tercatat di \[tenant\]." + tombol "Tambah Aset" langsung di tengah area tabel (bukan generic empty icon).
- Loading: skeleton row (bentuk tabel dipertahankan, bukan spinner tengah layar).
- Error: banner di atas tabel "Gagal memuat daftar aset — coba lagi" + tombol retry.
- Success: (implisit, tabel terisi normal).
- Offline: banner sticky atas "Tidak ada koneksi — data terakhir mungkin belum terbaru" (tabel tetap tampil dari cache jika ada).

**Scan Barcode**

- Empty (belum ada scan): instruksi singkat "Arahkan scanner atau ketik kode aset".
- Loading (proses lookup): input menunjukkan indikator kecil di sisi kanan, tanpa memblokir input berikutnya.
- Error (kode tidak ditemukan): pesan merah tegas + kode yang di-scan ditampilkan ulang agar user bisa verifikasi salah scan.
- Success: flash border hijau singkat + auto-navigasi ke Detail Aset.
- Offline: scan tetap diterima, disimpan sebagai antrian lookup, banner "Akan diproses saat koneksi kembali".

**Dashboard**

- Empty (tenant baru, 0 aset): pesan onboarding "Mulai dengan menambahkan aset pertama" + CTA.
- Loading: skeleton pada metric card & chart.
- Error: metric card individual bisa gagal independen (tidak menjatuhkan seluruh dashboard) — tampilkan "—" + ikon retry kecil per card.
- Offline: banner global, data terakhir tetap ditampilkan dengan label "data per \[timestamp cache\]".

**Login SSO**

- Loading: layar redirect singkat dengan pesan "Menghubungkan ke \[nama tenant\]…", bukan blank screen.
- Error: IdP gagal → halaman penuh dengan pesan jelas + tombol "Coba lagi" + kontak admin tenant.
- Offline: deteksi sebelum redirect ke IdP — "Periksa koneksi internet Anda" (mencegah redirect gagal senyap).

---

## 9. Responsive Behaviour

Asumsi pemakaian: staf lapangan dominan **mobile/tablet** (scan, lihat detail, mutasi cepat); admin & input data berat dominan **desktop**.

- **Mobile (\< 640px):** Scan Barcode jadi prioritas akses tercepat (tab bar bawah: Scan, Daftar Aset, Dashboard). Data Table di layar sempit beralih ke stacked card per baris (bukan scroll horizontal tabel penuh) — hanya kolom kunci (kode, nama, status) yang tampil, detail lain masuk saat expand. Form multi-kolom jadi 1 kolom.
- **Tablet (640–1024px):** Tabel tetap dalam format tabel (bukan card) tapi kolom sekunder (mis. lokasi, tanggal update) disembunyikan di belakang toggle "Tampilkan lebih". Cocok untuk staf yang bawa tablet saat opname fisik.
- **Desktop (≥ 1024px):** Layout penuh — tabel lengkap semua kolom, panel ganda (mis. Generate Barcode: tabel pilih + preview berdampingan), sidebar navigasi persisten menggantikan tab bar bawah.
- Semua breakpoint: Tenant Identity Bar (P2) tetap terlihat di semua ukuran, tidak pernah disembunyikan ke dalam menu hamburger.

---

## 10. Accessibility

- **Kontras:** Semua pasangan teks/background wajib ≥ 4.5:1 (teks normal) dan ≥ 3:1 (teks besar ≥18px/bold ≥14px). Dicek eksplisit: `graphite-900` di atas `canvas-50` = kontras tinggi (aman); `ledger-600` di atas putih untuk teks tombol wajib pakai teks putih (`#FFFFFF`) bukan default, dan divalidasi ≥4.5:1; `tag-amber-600` sebagai background tombol wajib teks gelap (`graphite-900`) bukan putih, karena amber terlalu terang untuk kontras putih.
- **Focus order:** Mengikuti urutan DOM logis — Tenant Identity Bar tidak masuk tab order utama kecuali interaktif (dropdown switch tenant untuk admin lintas tenant). Form cascading classification: focus order golongan→kategori→kelompok→sub kelompok mengikuti urutan visual & logis, field yang masih ter-lock (parent belum dipilih) tetap ada di tab order tapi berstatus `aria-disabled` dengan penjelasan.
- **Keyboard nav:** Data Table mendukung navigasi panah (arrow key antar sel/baris) untuk efisiensi input staf yang sering kerja tanpa mouse (device scanner). Bulk select mendukung `Shift+Click`/`Shift+Arrow` dan checkbox individual full keyboard-accessible. Modal wajib trap focus dan kembali ke elemen pemicu saat ditutup (`Esc`).
- **ARIA khusus:**
  - Scan Input: `aria-live="polite"` pada area hasil scan, supaya screen reader mengumumkan hasil scan tanpa perlu pindah fokus manual.
  - Status Badge: `role="status"` dengan label teks penuh (bukan hanya warna) — status tidak boleh disampaikan lewat warna saja (mis. badge "Disposal" tetap ada teks "Disposal", bukan cuma titik merah).
  - Attribute Mapping Table (SSO): setiap baris punya `aria-describedby` yang menjelaskan field SAML mana yang di-mapping ke field aplikasi mana.
  - Toast/Alert: `role="alert"` untuk error, `role="status"` untuk info/success (urgensi berbeda untuk screen reader).
  - Offline banner: `aria-live="assertive"` karena mengubah keandalan data yang sedang dilihat user.