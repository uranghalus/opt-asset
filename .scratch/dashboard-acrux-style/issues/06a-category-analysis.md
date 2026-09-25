# 06a: Analisis kategori (stacked horizontal bar + legend)

**What to build:**
First card in the 3-card bottom row of the left column: "Analisis kategori". Shows a total headline number (Rp 8,45 M in reference → adapted AMS value), a month selector dropdown, a single stacked horizontal bar composed of 7 colored segments (Perangkat IT, Kendaraan, Mesin, Fasilitas, Furniture, Investasi, Lainnya) painted from AURORA tokens, and a per-line legend beneath with swatch + label + percentage.

**Blocked by:** 05 (appends into the JSX tree beneath 05's row in `dashboard.tsx` — serial order avoids file-conflict with 05 and other 06a/b/c siblings; parallel with 06b/06c work OK if coordinating)

**Status:** ready-for-agent

- [x] Glass card frame, title "Analisis kategori", "Spending overview" sub → "Ringkasan sebaran" sub copy, top-right month dropdown defaulting to current month
- [x] Headline big mono-data number + sub-copy
- [x] Stacked horizontal bar (single bar across card width) with 7 segments, each using distinct AURORA token colors: `accent-primary` for IT, `accent-teal` for Kendaraan, `warning` for Mesin, success for Fasilitas, muted for Furniture, `accent-primary` tinted for Investasi, light-muted for Lainnya
- [x] Legend beneath bar: 7 lines, each with swatch · label · mono-data percentage aligned right
- [x] Fits in 1/3 of 3-column bottom row on ≥1280px; full-width on mobile
- [x] TypeScript: `npm run types:check` passes on touched files
