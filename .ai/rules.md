# AI Agent Rules ÔÇö Asset Management System (AMS) v2

Baca file ini SEBELUM menulis atau mengubah kode apa pun di repo ini. Ini bukan dokumen strategi ÔÇö ini aturan operasional yang mengikat setiap perubahan kode.

Dokumen strategi/bisnis (baca sekali untuk konteks, jangan diduplikasi di sini):

- `docs/PRD.md` ÔÇö problem statement, persona, goals/non-goals, FR lengkap, data model, open questions
- `docs/DESIGN-BRIEF.md` ÔÇö alasan desain (mood, principles, user flow, accessibility)
- `DESIGN.md` (root) ÔÇö token mesin-baca (warna light/dark, tipografi, radius, spacing, glass) ÔÇö **sumber kebenaran token, jangan hardcode nilai yang berbeda dari file ini**

---

## 1. Aturan Kritis ÔÇö Tidak Bisa Dinegosiasikan

Ini bukan preferensi, ini kegagalan yang setara dengan kebocoran data atau insiden keamanan kalau dilanggar.

### 1.1 Tenant scoping wajib di SETIAP query domain

- Setiap tabel domain (`assets`, `asset_groups`, `asset_categories`, `asset_clusters`, `asset_sub_clusters`, `items`, `barcodes`, `asset_mutations`, `asset_disposals`, `asset_histories`, `audit_logs`, `sso_configurations`) WAJIB punya kolom `tenant_id` dan WAJIB di-scope otomatis (global scope Eloquent, bukan filter manual per query).
- TIDAK ADA query Eloquent ke tabel domain yang boleh lolos tanpa scope tenant ÔÇö termasuk di job/queue, command Artisan, dan seeder non-testing.
- Kode aset (`kode_asset`) dan `barcode_value` unik **per tenant**, bukan global ÔÇö pakai unique constraint composite `(tenant_id, kode_asset)` / `(tenant_id, barcode_value)` di level database, bukan hanya validasi aplikasi.
- Setiap PR yang menyentuh model/query domain wajib menyertakan test yang membuktikan isolasi tenant (query dari tenant A tidak bisa mengembalikan data tenant B).

### 1.2 Autentikasi hanya via SSO SAML

- TIDAK ADA auth lokal Laravel default (email/password) untuk user tenant. Jangan tambahkan route/controller login manual kecuali eksplisit diminta.
- User baru dibuat via JIT provisioning saat login SAML pertama kali, dengan role default paling minim (least privilege) ÔÇö jangan auto-assign role tinggi.
- Konfigurasi IdP disimpan per tenant (`sso_configurations`), bukan satu konfigurasi global untuk semua tenant.

### 1.3 Rantai klasifikasi & kode aset

- Urutan berjenjang wajib: `asset_groups ÔåÆ asset_categories ÔåÆ asset_clusters ÔåÆ asset_sub_clusters`. Setiap level attribute `code` dipakai untuk membentuk `kode_asset` secara berurutan sesuai rantai ini ÔÇö jangan ubah urutan atau lewati level.
- `kode_asset` di-generate otomatis dari rantai kode + sequence, TIDAK PERNAH diambil langsung dari input/import user.
- Import Excel: jika nama Item tidak ditemukan, auto-create Item baru tanpa kategori (jangan skip baris), sesuai FR existing.
- Asset type (`aktiva_tetap` | `peralatan`) ditentukan otomatis dari threshold nilai/nominal, field ini independen dari struktur klasifikasi Group/Category/Cluster/Sub-cluster ÔÇö jangan gabungkan dua konsep ini.

---

## 2. Design System ÔÇö Aturan Teknis

Nilai token lengkap ada di `DESIGN.md`. Aturan di bawah ini yang WAJIB ditegakkan di kode, tidak cukup hanya "mirip":

- **Pemisahan glass vs solid bersifat wajib, bukan estetika:** komponen chrome (sidebar, header, modal, card ringkasan/metrik) pakai `surface-glass` + `backdrop-blur`. Semua Data Table, form input, dan daftar/list data pakai `surface-solid` ÔÇö TIDAK BOLEH diberi blur/translucency apa pun, alasan: keterbacaan angka padat (nilai buku, kode aset) tidak boleh terganggu efek visual.
- **Dua tema wajib ada** (`data-theme="light"` / `"dark"`), setiap komponen baru harus disediakan token untuk keduanya, tidak boleh hardcode warna yang hanya benar di satu tema.
- **Font data vs UI dipisah tegas:** `IBM Plex Mono` HANYA untuk kode aset, barcode value, nominal/angka tabular di tabel. `IBM Plex Sans` untuk semua UI/body lain. Jangan pakai Plex Mono untuk teks biasa atau Plex Sans untuk kolom angka di tabel.
- **Kontras wajib ÔëÑ4.5:1** untuk teks normal (ÔëÑ3:1 untuk teks besar), dihitung terhadap `surface-solid` sebagai referensi worst-case untuk teks di atas elemen glass ÔÇö jangan asumsikan kontras terhadap warna background di baliknya.
- **Radius berjenjang, jangan seragam:** button/input `8px`, card glass `16px`, modal `24px`, tabel/list solid `4px`, barcode label card `0px`. Radius seragam di semua elemen adalah tanda implementasi salah, bukan pilihan gaya.
- **Status tidak pernah hanya warna** ÔÇö setiap status badge wajib punya teks (`role="status"`), bukan sekadar dot berwarna.
- Hormati `prefers-reduced-transparency` (fallback semua glass ke solid) dan `prefers-reduced-motion` (matikan transisi tema/hover glow) ÔÇö ini bukan nice-to-have, ini requirement aksesibilitas dari brief.

---

## 3. Konvensi Kode ÔÇö Wajib

### 3.1 Konsistensi gaya kode

- Satu konvensi penamaan per bahasa, tidak boleh berubah-ubah di tengah jalan: PHP/DB = `snake_case`, JS/TS/React = `camelCase` untuk variabel/fungsi, `PascalCase` untuk komponen. Sekali dipilih di satu bagian codebase, wajib konsisten di seluruh bagian yang sama ÔÇö jangan campur gaya lama dan baru dalam satu modul.
- Format wajib lewat tool otomatis (`pint` untuk PHP, `prettier`/eslint config repo untuk JS/TS), bukan gaya manual per developer/agent.
- Jangan reformat atau restyle file yang tidak relevan dengan tiket yang sedang dikerjakan ÔÇö perubahan gaya di luar scope tiket menambah noise diff dan risiko konflik, bukan perbaikan.
- Pola arsitektur yang sudah dipakai di satu bagian (mis. cara fetch data, cara structure form, cara handle state) wajib diikuti di bagian baru yang serupa ÔÇö jangan perkenalkan pola alternatif untuk masalah yang sama tanpa alasan kuat & didiskusikan dulu.

### 3.2 Reusable component ÔÇö wajib jika memungkinkan

- Sebelum bikin komponen baru, cek dulu apakah pola visual/struktur yang sama sudah ada (mis. card glass, stat card, badge, tabel) ÔÇö extend lewat props/variant, jangan copy-paste markup ke file baru.
- Elemen yang muncul ÔëÑ2 kali dengan struktur sama (kartu metrik, badge status, tombol icon+label, progress bar) WAJIB jadi komponen reusable di `resources/js/Components`, bukan diduplikasi per halaman.
- Variant (glass/solid, warna status, ukuran) dikontrol lewat props/enum pada satu komponen, bukan lewat beberapa file komponen yang isinya nyaris identik.
- Pengecualian hanya untuk elemen yang benar-benar unik secara struktur/logika (satu kali pakai, tidak ada pola berulang) ÔÇö kalau ragu, buat reusable dulu.

### 3.3 Efisiensi ÔÇö wajib, tidak boleh mubazir

- **Query:** wajib eager-load relasi (`with()`) untuk mencegah N+1, terutama di Daftar Aset, Riwayat, dan Audit Log yang datanya besar. Query berat wajib dipaginasi di level database, tidak boleh load semua baris lalu dipotong di frontend.
- **Render:** jangan hitung ulang data turunan (derived data) di setiap render kalau bisa dihitung sekali di backend atau di-memoize (`useMemo`/`useCallback`) ÔÇö tapi jangan memoize semua hal secara membabi buta juga, hanya yang benar-benar mahal/sering re-render.
- **Fetch:** jangan fetch ulang data yang sudah tersedia dari props Inertia halaman saat ini ÔÇö pakai data yang sudah dikirim server, jangan tambahkan request klien terpisah untuk data yang bisa ikut nempel di response awal.
- **Bundle:** halaman baru wajib lazy/code-split per route Inertia (default Vite), jangan import komponen berat (chart, gauge SVG kompleks) di halaman yang tidak memakainya.
- Kalau sebuah fungsi/loop punya kompleksitas yang bisa disederhanakan (mis. iterasi berlapis yang bisa jadi satu query/satu pass), sederhanakan ÔÇö jangan biarkan "yang penting jalan" kalau ada cara yang jelas lebih ringan dengan effort implementasi setara.

### 3.4 Server-side rendering ÔÇö wajib

- Inertia SSR wajib aktif dan berjalan (`php artisan inertia:start-ssr` / build SSR bundle terpasang di deployment) ÔÇö bukan opsional, setiap halaman harus menghasilkan HTML bermakna dari server pada request pertama, bukan halaman kosong yang baru terisi setelah JS hydrate.
- Konten utama tiap halaman (data aset, tabel, angka dashboard) wajib dirender di server lewat props Inertia, bukan di-fetch dan dirender murni di client setelah mount.
- Interaktivitas yang memang harus client-only (mis. live scan barcode, chart hover tooltip, toggle tema) boleh client-side, tapi tidak boleh membuat konten utama halaman kosong sebelum hydration selesai ÔÇö pastikan SSR tetap menampilkan struktur & data awal yang benar untuk elemen-elemen ini juga.
- Setiap komponen baru yang berpotensi SSR-unsafe (pakai `window`, `document`, `localStorage` langsung di top-level render) wajib dibungkus guard/check `typeof window !== 'undefined'` atau di-defer ke `useEffect`, supaya tidak merusak SSR.

### 3.5 Dokumentasi fungsi ÔÇö wajib

- Setiap fungsi/method (PHP maupun JS/TS), kecuali getter/setter trivial satu baris, WAJIB punya docblock yang menjelaskan: tujuan fungsi, parameter (nama + tipe + arti), nilai kembali, dan efek samping penting (mis. menulis ke DB, memanggil API eksternal, bergantung pada tenant aktif).
- PHP: gunakan format PHPDoc standar (`/** ... @param ... @return ... */`). JS/TS: gunakan JSDoc (`/** ... */`) di atas fungsi, termasuk untuk komponen React (jelaskan props apa saja yang diterima dan kegunaannya).
- Dokumentasi wajib dijaga tetap akurat ÔÇö kalau logika fungsi berubah, docblock WAJIB ikut diperbarui di commit yang sama, bukan menyusul.

---

## 4. Workflow Tiket & Dependency

Status per tanggal terakhir dibahas ÔÇö update bagian ini setiap kali ada tiket baru/selesai.

### Daftar tiket

Tiket aktif ada di `docs/PROJECT-PLAN.md` (T01ÔÇôT14, terbit sebagai issue GitHub #9ÔÇô#22 plus follow-up #23ÔÇô#25) ÔÇö tabel & "Blocked by" di body issue adalah sumber kebenaran status dan dependency; jangan duplikasi daftar tiket di sini.

### Aturan kerja

- Status per 2026-09-29: T01, T01b, T01c, T01d, T02 selesai; **T03 (#11, Classification Chain) adalah frontier berikutnya**; T04ÔÇôT14 menunggu T03.
- Urutan & paralelisasi mengikuti "Blocked by" di body masing-masing issue, bukan daftar lokal.
- Setiap tiket yang menyentuh kode PHP: jalankan `vendor/bin/pint --dirty --format agent` sebelum dianggap selesai.
- Setiap tiket yang menyentuh frontend: jalankan `npm run types:check` dan pastikan `npm run build` (atau `npm run dev` bila build-manifest error) tidak error sebelum dianggap selesai.
- Promo card (tiket 01): copy tetap placeholder ("Upgrade untuk fitur enterprise") sampai model bisnis multi-tenant (internal grup vs SaaS eksternal) diputuskan ÔÇö jangan tulis copy tier/harga nyata tanpa konfirmasi.
- Kartu Aset (tiket 04): tampilkan kode aset dalam bentuk masked/mono (bukan gradient polos tanpa kode, dan bukan barcode scannable penuh ÔÇö itu peran halaman Generate Barcode terpisah).

---

## 5. Dev Log & Memory ÔÇö Wajib Setiap Selesai Tugas

### 5.1 Log pengerjaan (`docs/log-dev`)

- Setiap kali selesai mengerjakan satu tugas/tiket (atau sub-bagian tugas yang berarti), WAJIB menulis entri log di `docs/log-dev/`.
- Satu file per hari: `docs/log-dev/YYYY-MM-DD.md`. Kalau ada beberapa tugas selesai di hari yang sama, entri baru ditambahkan (append) ke file hari itu, bukan menimpa entri sebelumnya.
- Format entri wajib persis seperti ini (tidak boleh diringkas atau diubah strukturnya):

```markdown
## Tanggal Pengerjaan dan jam: DD-MM-YYYY HH:mm

- List Data Pekerjaan
    - <poin pekerjaan 1>
    - <poin pekerjaan 2>

- Kendala
    - <kendala yang ditemui, atau "Tidak ada kendala berarti">

- Hasil
    - <hasil akhir/output yang dihasilkan>
```

- "List Data Pekerjaan" berisi apa saja yang dikerjakan (file yang diubah, fitur yang dibangun) ÔÇö bukan cuma nama tiket.
- "Kendala" wajib diisi jujur, termasuk kalau tidak ada kendala berarti (jangan dikosongkan begitu saja).
- "Hasil" adalah output konkret: fitur berjalan, test yang lulus, atau kalau belum selesai, state saat ini.

### 5.2 Persistent Project Memory ÔÇö Obsidian Vault

- OpenCode memiliki akses ke vault Obsidian **`Opti-Asset`** melalui MCP tool **`obsidian`**.
- Agent WAJIB membaca **`Memory/opencode-memory.md`** pada awal sesi, sebelum mulai mengubah kode atau mengambil keputusan teknis, menggunakan operasi **`get_file_contents`**.
- Jika **`Memory/opencode-memory.md`** belum ada, agent diperbolehkan membuat file tersebut beserta folder **`Memory/`** terlebih dahulu, lalu mengisinya dengan ringkasan konteks proyek yang relevan.
- Struktur memory:
    - `Memory/opencode-memory.md` ÔÇö konteks proyek yang berlaku lintas sesi: keputusan arsitektur, konvensi, dependency penting, constraint, preferensi implementasi, dan hal-hal yang perlu diingat agent.
    - `Memory/logs/YYYY-MM-DD.md` ÔÇö catatan progres per sesi yang sifatnya lebih detail dan hanya dibuat jika relevan.
- Setelah satu tugas/tiket atau sub-bagian yang berarti selesai, agent WAJIB memperbarui memory persisten bila ada **keputusan penting, konvensi baru, perubahan arsitektur, dependency/constraint baru, atau progres yang penting untuk sesi berikutnya**.
- Gunakan **`append_content`** untuk menambahkan catatan baru dan **`patch_content`** bila perlu memperbaiki atau memperbarui informasi memory yang sudah ada.
- Memory harus **ringkas, terstruktur, dan berorientasi konteks**, bukan transkrip percakapan mentah.
- Setiap entri memory harus menjelaskan seperlunya:
    - konteks/perubahan yang dibuat,
    - keputusan atau alasan teknis penting,
    - file/area yang terdampak,
    - kendala atau risiko,
    - hasil/status akhir.
- Agent BOLEH membuat atau memperbarui file memory pendukung bila dibutuhkan untuk menjaga konteks lintas sesi, selama tetap mengikuti struktur `Memory/` dan tidak membuat duplikasi informasi yang tidak perlu.
- JANGAN PERNAH menyimpan credential, password, API key, access token, session token, secret, atau data sensitif lain ke memory.
- Bila tidak ada informasi baru yang bernilai untuk lintas sesi, jangan membuat entri memory yang hanya berisi "task selesai".

### 5.3 Memory Graphify

- Selain log markdown di atas, agent WAJIB juga menyimpan ringkasan pekerjaan yang sama (tugas dikerjakan, kendala, hasil) ke memory Graphify (knowledge-graph memory) begitu satu tugas selesai ÔÇö supaya konteks tetap tersambung lintas sesi, tidak hanya tersimpan sebagai file statis.
- Log markdown, memory Obsidian, dan entri memory Graphify harus konsisten isinya (sama-sama merujuk tugas & tanggal yang sama) ÔÇö jangan sampai salah satu berisi informasi yang berbeda dari yang lain.
- Jika tool/interface Graphify tidak tersedia di environment, agent tidak boleh mengarang nama tool atau format pemanggilannya. Tetap lakukan pencatatan ke `docs/log-dev/` dan memory Obsidian yang tersedia, lalu catat keterbatasan tersebut pada bagian "Kendala".

---

## 6. Kalau Ragu

- Requirement ambigu di kode yang sedang dikerjakan ÔåÆ cek `docs/PRD.md` section "Open Questions" dulu sebelum menebak. Kalau masih belum terjawab di sana, tandai dengan `// TODO(open-question): ...` di kode dan lanjutkan dengan asumsi paling aman (least privilege / fail closed), jangan block pekerjaan.
- Perubahan token desain (warna/radius/spacing) ÔåÆ ubah di `DESIGN.md` dulu, baru turunkan ke kode. Jangan ubah nilai visual langsung di komponen tanpa update token source.
