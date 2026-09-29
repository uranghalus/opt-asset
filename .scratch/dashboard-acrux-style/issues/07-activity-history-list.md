# 07: Transaction/activity history list column (right column)

**What to build:**
A tall glass card filling the right column beneath ticket 04's asset panel (or spans that column height to match the reference). Lists 8 rows of "Riwayat aktivitas" — each row has a colored icon tile on the left, bold activity name + sub date, and a right-aligned mono-data positive/neutral delta amount with "Selesai" / "Dalam proses" status badge at the bottom of each row. Solid list surface inside the glass frame per DESIGN.md data-readability rule.

**Blocked by:** 04 (appends into the right column JSX beneath 04's asset card panel — serial order avoids same-file merge conflict; no hard technical dependency)

**Status:** ready-for-agent

- [x] Glass card frame, title "Riwayat aktivitas", top-right "7d" dropdown filter
- [x] 8 rows rendered as list items with subtle bottom-border separators; solid `surface-solid` background on the list inner container (data → solid rule)
- [x] Per-row: 40×40 rounded-square icon tile with category-colored tint background + category glyph (IT device / forklift-car / printer / building / etc.)
- [x] Per-row middle column: bold label (AMS domain — e.g. "Terima Laptop ThinkPad", "Audit Cabang Bandung", "Servis printer", "Mutasi mobil pool") + sub-line date e.g. "23 Feb 2026"
- [x] Per-row right column: mono-data tabular delta with + prefix and success color for incoming / neutral secondary color for maintenance, with small status badge below showing "Selesai" (success pill) or "Dalam proses" (warning pill)
- [x] Card supports vertical overflow scroll if rows exceed container height
- [x] TypeScript: `npm run types:check` passes on touched files
