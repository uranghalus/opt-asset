---
version: "1.0"
name: "AMS Ledger Glass — Dual Mode"
description: "Glassmorphism enterprise theme untuk Asset Management System. Glass diterapkan pada chrome (nav, modal, card ringkasan); tabel data & form tetap surface solid demi keterbacaan angka."
base_reference: "Diadaptasi dari template AURORA — After-Dark Sign-In (designmd.app), disesuaikan untuk konteks enterprise data-dense"
colors:
  light:
    bg-base-start: "#E8ECF8"
    bg-base-end: "#F3F0FF"
    surface-glass: "rgba(255,255,255,0.55)"
    surface-solid: "#FFFFFF"
    surface-solid-alt: "#F5F6FA"
    border-glass: "rgba(255,255,255,0.6)"
    border-solid: "#DDE1EC"
    text-primary: "#131B2E"
    text-secondary: "#5B6478"
    accent-primary: "#8A6CFF"
    accent-teal: "#12B597"
    success: "#1F9D6F"
    warning: "#B5721F"
    danger: "#C23A3A"
  dark:
    bg-base-start: "#060918"
    bg-base-end: "#0D1230"
    surface-glass: "rgba(255,255,255,0.06)"
    surface-solid: "#12172B"
    surface-solid-alt: "#191F38"
    border-glass: "rgba(255,255,255,0.12)"
    border-solid: "#2A3152"
    text-primary: "#E8ECF8"
    text-secondary: "#8B93B5"
    accent-primary: "#9B87FF"
    accent-teal: "#3EE6C4"
    success: "#34D399"
    warning: "#F2B84B"
    danger: "#FF6B6B"
typography:
  display:
    fontFamily: "IBM Plex Sans"
    fontSize: 1.75rem
    fontWeight: 600
  h2:
    fontFamily: "IBM Plex Sans"
    fontSize: 1.375rem
    fontWeight: 600
  h3:
    fontFamily: "IBM Plex Sans"
    fontSize: 1.125rem
    fontWeight: 500
  body-md:
    fontFamily: "IBM Plex Sans"
    fontSize: 0.875rem
    fontWeight: 400
  body-strong:
    fontFamily: "IBM Plex Sans"
    fontSize: 0.875rem
    fontWeight: 500
  small:
    fontFamily: "IBM Plex Sans"
    fontSize: 0.78rem
    fontWeight: 400
  mono-data:
    fontFamily: "IBM Plex Mono"
    fontSize: 0.8125rem
    fontWeight: 400
rounded:
  sm: 8px
  md: 16px
  lg: 24px
  pill: 999px
  table: 4px
spacing:
  xs: 0.25rem
  sm: 0.5rem
  md: 1rem
  lg: 1.5rem
  xl: 2rem
  2xl: 3rem
  3xl: 4rem
glass:
  blur-panel: 20px
  blur-nested: 10px
  opacity-light: 0.55
  opacity-dark: 0.06
  shadow-light: "0 8px 32px rgba(19,27,46,0.12)"
  shadow-dark: "0 8px 32px rgba(0,0,0,0.45)"
  glow-accent-hover: "0 0 24px rgba(138,108,255,0.35)"
---

## Overview

Tema visual AMS beralih dari arah ledger-industrial (flat/solid) ke **glassmorphism dual-mode**, sesuai preferensi eksplisit. Karakter tetap enterprise — bukan glass generik ala landing page — dengan aturan pemisahan lapisan yang eksplisit di bawah.

- **Style:** Glassmorphism, hybrid dengan solid-surface untuk area data
- **Keywords:** aurora, glass, ledger, dual-mode
- **Light/Dark:** ✓ Light · ✓ Dark (wajib keduanya, switch via `data-theme`)
- **Density:** 6/10 (lebih padat dari template sign-in asal, karena ini dashboard kerja bukan halaman promosi)
- **Motion:** 4/10 — animasi transisi tema & hover saja, tidak animasi dekoratif berlebihan (lihat bagian Motion)

## Colors

**Light**
| Token | Hex/Value | Peran |
|---|---|---|
| `bg-base-start → bg-base-end` | `#E8ECF8 → #F3F0FF` | Gradient background utama (aurora wash) |
| `surface-glass` | `rgba(255,255,255,0.55)` | Panel chrome: nav, modal, card ringkasan |
| `surface-solid` | `#FFFFFF` | Tabel data, form input |
| `text-primary` / `text-secondary` | `#131B2E` / `#5B6478` | |
| `accent-primary` (violet) | `#8A6CFF` | CTA utama, link, elemen brand |
| `accent-teal` | `#12B597` | Aksi scan/verifikasi berhasil |
| `success` / `warning` / `danger` | `#1F9D6F` / `#B5721F` / `#C23A3A` | Status aset (aktif/menunggu/disposal) |

**Dark**
| Token | Hex/Value | Peran |
|---|---|---|
| `bg-base-start → bg-base-end` | `#060918 → #0D1230` | Gradient dasar AURORA asli, dipertahankan |
| `surface-glass` | `rgba(255,255,255,0.06)` | Panel chrome |
| `surface-solid` | `#12172B` | Tabel data, form input |
| `text-primary` / `text-secondary` | `#E8ECF8` / `#8B93B5` | Dari AURORA secondary/tertiary asli |
| `accent-primary` (violet) | `#9B87FF` | Dinaikkan lightness dari mode terang agar tetap ≥4.5:1 di atas `#060918` |
| `accent-teal` | `#3EE6C4` | Sama seperti AURORA asli — sudah tinggi kontras di dark |
| `success` / `warning` / `danger` | `#34D399` / `#F2B84B` / `#FF6B6B` | Dinaikkan saturasi untuk tetap terbaca di atas glass gelap |

## Typography

- `IBM Plex Sans` — UI, heading, body.
- `IBM Plex Mono` — kode aset, nilai barcode, kolom nominal/penyusutan di tabel (tabular figures, disambiguasi 0/O dan 1/l tetap krusial meski tema berubah).
- Skala tetap 14px basis untuk body (bukan 16px default web) — menjaga densitas dashboard.

## Layout & Effects

- **Fitur:** CSS Grid, Flexbox, Backdrop Filter, Gradients (background only), Box Shadow (soft/ambient), Border Radius bertingkat, Transitions.
- **Aturan pemisahan lapisan (wajib):**
  - **Glass** → top nav, sidebar, modal/dialog, metric card ringkasan di Dashboard, Barcode Label preview panel.
  - **Solid** → semua Data Table (Daftar Aset, Riwayat, Audit Log), semua form input, Attribute Mapping table (SSO config). Alasan: blur di belakang ratusan baris angka merusak kecepatan baca, dan kontras teks-di-atas-glass tidak stabil untuk data yang harus akurat dibaca (nilai buku, kode aset).
  - Solid surface tetap ditaruh **di atas** background gradient aurora, jadi nuansa tema tetap terasa di seluruh halaman meski tabelnya sendiri opaque.
- **Radius bertingkat:** `sm` (8px) untuk button/input, `md` (16px) untuk card, `lg` (24px) untuk modal, `table` (4px, tetap kecil) khusus container tabel data — supaya tabel tidak ikut kesan "lembek" ala glass.
- **Shadow:** ambient soft shadow (`glass.shadow-light` / `glass.shadow-dark`) hanya di elemen glass; solid surface pakai border 1px, bukan shadow, supaya tabel tetap tegas/presisi (konsisten dengan brief sebelumnya).

## Effects

- Glass/Blur pada layer chrome sesuai `glass.blur-panel` (20px) dan `glass.blur-nested` (10px, untuk elemen di dalam elemen glass lain — hindari blur bertumpuk berat).
- Glow aksen (`glass.glow-accent-hover`) hanya pada hover tombol primary — bukan default state, supaya tidak berkedip ramai saat scroll list panjang.
- **Tidak dipakai:** neumorphism, 3D/parallax, keyframe animation dekoratif berjalan terus-menerus. Ini alat kerja, animasi hanya untuk transisi state (buka modal, ganti tema, hasil scan) — sesuai prinsip "motion menjawab aksi user", bukan hiasan latar.

## Dark/Light Mode

- Toggle via `data-theme="light|dark"` di root, tersimpan di preferensi user (bukan hanya `prefers-color-scheme`, karena staf shift malam di gudang mungkin perlu override manual terlepas dari OS setting).
- Semua token di atas didefinisikan dua kali (light/dark) — komponen tidak boleh hardcode warna, hanya referensi token.
- Transisi ganti tema: fade 150ms pada `background` dan `color`, tanpa animasi pada blur (blur transition mahal secara performa, ganti instan).

## Accessibility — catatan khusus untuk tema glass ini

- **Kontras dihitung terhadap background solid akhir, bukan terhadap apa pun di belakang blur.** Karena `surface-glass` translucent, base gradient (`bg-base-start/end`) dianggap sebagai "worst case" background saat validasi kontras teks di atas glass — bukan diasumsikan selalu di atas warna gelap pekat.
- Teks di atas `accent-primary` (tombol) light mode pakai `#FFFFFF`; dark mode juga `#FFFFFF` — divalidasi ≥4.5:1 terhadap `#9B87FF`.
- Hormati `prefers-reduced-transparency`: saat aktif, `surface-glass` fallback ke `surface-solid` penuh (blur di-nonaktifkan, bukan sekadar dikurangi).
- Hormati `prefers-reduced-motion`: transisi tema & glow hover dihilangkan, hanya instant state change.
- Status (badge aktif/disposal/dll) tetap wajib disertai teks, tidak pernah hanya warna — aturan ini tidak berubah dari brief sebelumnya.

## Use Case

Dashboard kerja enterprise data-dense (asset tracking, barcode, multi-tenant) — bukan landing page atau halaman promosi. Glass dipakai selektif sebagai identitas visual, bukan menyelimuti seluruh permukaan aplikasi.

<!-- Diadaptasi dari: AURORA — After-Dark Sign-In, https://designmd.app/library/aurora-glass-signin -->
