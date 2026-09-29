# 03: Build bento-grid shell + hero chart + 3-column stat cards (top row)

**What to build:**
Dashboard page top row ships the exact bento proportions of the reference: left wide glass card with headline number + stacked 3-segment bar chart (7 days, Sen→Min), right narrow column with 3 stacked metric cards (Total / Verified / Maintenance). Uses existing AMS data domain and chart colors but changes the chart style to stacked bars with per-segment hover tooltips. This ticket lays down the grid shell that tickets 04–07 append into.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] CSS Grid bento shell rendered in `dashboard.tsx`: 2 columns on ≥1280px (≈62/38 split), single column below
- [ ] Hero wide glass card: headline `Rp 12,45 M` in display weight with mono-data tabular figures, subtitle "Ringkasan 7 hari terakhir", 3-swatch legend (Terdaftar / Diverifikasi / Nonaktif), stacked bar chart with 7 day-labels on x-axis and 3 stacked color segments per bar
- [ ] Hovering a stacked bar shows a floating tooltip with the value + label for each of the 3 segments
- [ ] Hero card top-right: pill-shaped filter group with active "7d" button + share icon button + download icon button
- [ ] Right narrow column: 3 stacked metric glass cards. Each has icon chip, title, big tabular mono number, and a `+5.1%` / `-15.5%` / `+20.7%` style change badge (up/down arrow + secondary color tone)
- [ ] All card chrome uses `surface-glass` + `backdrop-blur-[20px]` + consistent `shadow-light/dark` and `rounded-2xl`
- [ ] Responsive: on <1024px grid collapses 1 column with all cards in reading order
- [ ] TypeScript: `npm run types:check` passes; `npm run build` succeeds without vite manifest errors
