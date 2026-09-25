# 06c: Pelacakan target — 4-row goal tracker with progress bars

**What to build:**
Third card in the 3-card bottom row: "Pelacakan target". "+ Tambah target" pill button top-right. Four goal rows listed vertically. Each row: icon tile on the left, goal title next to it, mono-data progress label (e.g. `700 aset / 1.000 aset`) on the right, a gradient progress bar under the title, and a "Tersisa X bulan" style meta text beneath the bar.

**Blocked by:** 05 (appends into the JSX tree beneath 05's row — serial order avoids file-conflict; parallel with 06a/06b OK)

**Status:** ready-for-agent

- [x] Glass card frame, title "Pelacakan target", "+ Tambah target" pill button top-right
- [x] 4 goal rows, each with a distinct icon tile (document, suitcase/wallet, car, building) inside a rounded square chip with light tinted AURORA background
- [x] Each row: bold title, right-aligned mono-data `current / target` label
- [x] Progress bar beneath the title: gradient fill (same AURORA tone as icon chip) showing the current percentage
- [x] "Tersisa X bulan" / "Left to save 6 months" style gray meta line beneath each bar
- [x] AMS-appropriate goal names: Cadangan audit, Pemutaran inventory, Kendaraan baru, Real-estate cabang
- [x] Fits in 1/3 of 3-card bottom row on ≥1280px; full-width on mobile
- [x] TypeScript: `npm run types:check` passes on touched files
