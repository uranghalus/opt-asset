# 06b: Kesehatan portofolio — semicircle donut gauge

**What to build:**
Middle card in the 3-card bottom row: "Kesehatan portofolio". Ships a net-new semicircle (180°) donut gauge painted as an SVG. Headline shows total value with % change arrow. Gauge shows target coverage percentage as a big centered number (e.g. 75%) with the semicircle ring filled from left to right using an AURORA gradient. Range dropdown (30d) top-right. Helper text beneath the gauge.

**Blocked by:** 05 (appends into the JSX tree beneath 05's row — serial order avoids file-conflict; parallel with 06a/06c OK)

**Status:** ready-for-agent

- [x] Glass card frame, title "Kesehatan portofolio", top-right "30d" dropdown
- [x] Headline big mono-data number with up/down arrow % change badge
- [x] Custom SVG semicircle donut gauge (180° arc, not full circle): outer track ring drawn first, inner fill ring drawn on top with gradient from yellow-warning at left → success-green at right
- [x] Percentage number (e.g. 75%) dead-center of the gauge in heavy display weight
- [x] Sub-copy beneath gauge: "Berdasarkan metrik gabungan 30 hari terakhir"
- [x] Gauge responds cleanly to container width changes (preserves aspect ratio via viewBox)
- [x] Fits in 1/3 of the 3-card bottom row on ≥1280px; full-width on mobile
- [x] TypeScript: `npm run types:check` passes on touched files
