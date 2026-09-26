# Rincian Pekerjaan Harian — 26 September 2026

**Proyek:** Asset Management System v2 (Multi-Tenant) — branch `feature/saml-sso`
**Ringkasan satu kalimat:** Hari ini kita membangun fondasi sistem multi-tenant (satu aplikasi untuk banyak perusahaan/mall) dari nol, termasuk halaman kelola tenant dan fitur pindah-pindah tenant — semuanya selesai dan lolos semua pemeriksaan kualitas.

---

## 1. Fondasi pemisahan data antar tenant (pagi, ± 10:55)

**Apa yang dikerjakan:** Setiap tenant (misalnya Dutamall Banjarmasin vs Dutamall Palangkaraya) sekarang punya "pagar" datanya sendiri. Data milik tenant A **mustahil** terlihat oleh tenant B — dicek otomatis oleh sistem, bukan mengandalkan kehati-hatian programmer.

**Kenapa penting:** Ini mencegah kebocoran data antar perusahaan — kategori kesalahan paling berbahaya di aplikasi seperti ini.

**Bukti berhasil:** 12 test khusus yang membuktikan: login sebagai tenant A tidak akan pernah menampilkan data tenant B, dan kalau sistem bingung, hasilnya "data kosong" — bukan "semua data kelihatan".

**Tiket:** T01 (GitHub #9) — commit `94b84b3`

## 2. Halaman admin untuk mengelola tenant (siang, ± 13:40)

**Apa yang dikerjakan:** Halaman khusus untuk **membuat, melihat, mengubah, dan menonaktifkan** tenant — lengkap dengan pencarian, filter status, dan tombol halaman berikutnya. Menangguhkan tenant langsung mengunci semua penggunanya (ada dialog konfirmasi dulu supaya tidak salah tekan).

**Siapa yang boleh:** Hanya "platform admin". User tenant biasa bahkan tidak tahu halaman itu ada.

**Tiket:** T01b (GitHub #23) — commit `488a36b`

## 3. Pemeriksaan silang kualitas kode (sore, ± 15:10)

**Apa yang dikerjakan:** Meninjau ulang hasil kerja nomor 1 dan 2. Hasilnya 3 perbaikan:
- Menutup 1 pintu (URL) bawaan pustaka yang tidak kita pakai — tidak perlu, jadi dikunci.
- Mempercepat halaman daftar tenant: sekarang server hanya mengirim data yang berubah, bukan memuat ulang seluruh halaman.
- Menyeragamkan warna komponen tabel dengan design system resmi (mode terang & gelap).

**Commit:** `65aad49` (+ `25334f9` untuk log)

## 4. Perbaikan akses superadmin (bug yang dilaporkan, ± 16:05)

**Masalah:** Akun superadmin tidak bisa masuk dashboard.
**Penyebab:** Akun itu belum terhubung ke tenant mana pun, dan sistem memang mengunci akun tanpa tenant.
**Solusi:** Akun dihubungkan ke tenant DMB → dashboard bisa diakses.

**Commit:** `84dd892`

## 5. Satu akun bisa jadi anggota banyak tenant (± 17:30)

**Apa yang dikerjakan:** Satu orang (misalnya staf regional yang mengurus beberapa mall) kini bisa punya akses ke banyak tenant dengan **satu akun SSO** — tanpa buat akun terpisah. Setiap perpindahan tenant **tercatat** (siapa, pindah dari mana, ke mana).

**Tiket:** T01c (GitHub #24) — commit `813ecbe`

## 6. Hak superadmin + tombol pindah tenant (malam, ± 19:15)

**Masalah yang dilaporkan:** CRUD tenant tidak bisa diakses, dan tombol pindah tenant kurang nyaman.
**Solusi:**
- Status "superadmin" sekarang tersimpan **di database** (bukan di file konfigurasi) — memberi/mencabut akses tidak perlu lagi ubah file di server.
- Superadmin bisa masuk **semua tenant aktif** secara otomatis — tenant baru dibuat, langsung bisa dimasuki tanpa diatur satu-satu.
- Tombol pindah tenant berupa **dropdown di sidebar** yang selalu terlihat: menampilkan tenant yang sedang aktif + daftar untuk berpindah.

**Tiket:** T01d (GitHub #25) — commit `6602f43`

---

## Angka-angka hari ini

| Hal | Hasil |
|---|---|
| Test otomatis lulus | **80 buah** (pagi masih 33) |
| Commit ke git | 6 |
| Tiket GitHub selesai | 4 (#9, #23, #24, #25) |
| Pemeriksaan kualitas (format, analisis statis, TypeScript, build) | Semua bersih ✅ |

## Catatan untuk besok

- **Kalau deploy ke server baru:** wajib isi `PLATFORM_ADMIN_EMAILS` di pengaturan server, kalau tidak ada yang bisa buka halaman admin.
- **Pekerjaan berikutnya:** Tiket T02 (hak akses/role per pengguna). Catatan penting: karena satu orang bisa jadi anggota banyak tenant, role akan ditempel **per keanggotaan tenant** — jadi seseorang bisa jadi admin di mall A tapi staf biasa di mall B.
