# 04: Theme parity, a11y floor, mobile behavior

**What to build:**
The shell behaves correctly across themes, breakpoints, and input modes: the documented `data-theme` switch actually works (or the docs are corrected to `.dark`), keyboard and screen-reader users get a skip link and a single main landmark, mobile search collapses correctly, and the 375px + reduced-motion/reduced-transparency experience holds.

**Blocked by:** 02 and 03 (both surfaces must exist before parity/a11y sweep).

**Status:** ready-for-agent

- [x] `data-theme="light|dark"` is set alongside the `.dark` class in `use-appearance` (or DESIGN.md is corrected to the `.dark` convention) — theme toggle works in both reading directions
- [x] Skip-to-content link added before the sidebar; visible on focus
- [x] Single `main` landmark on dashboard (no nested mains with `SidebarInset`)
- [x] Mobile: search collapses to an icon button that expands an input; ≥44px touch targets on all header controls
- [x] 375px: no horizontal overflow; sidebar overlays via Sheet; promo card and header cluster degrade gracefully
- [x] `prefers-reduced-motion` and `prefers-reduced-transparency` verified (existing global rules still hold after restyling)
- [x] `npm run types:check` passes and `npm run build` succeeds
