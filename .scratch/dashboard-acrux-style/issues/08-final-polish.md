# 08: Final polish — spacing, light/dark parity, reduced-motion, responsive QA

**What to build:**
One final QA and polish pass across all surfaces from tickets 01–07. Ensures consistent spacing and DESIGN.md token usage, parity between light and dark themes, respects reduced-transparency / reduced-motion OS settings, mobile responsive behavior, and runs build/type/lint checks to ship green.

**Blocked by:** 01, 02, 03, 04, 05, 06a, 06b, 06c, 07 (gates on every content-slice ticket being in the tree first)

**Status:** ready-for-agent

- [x] DESIGN.md radius tokens audited across all touched files: cards md=16px / inputs+buttons sm=8px / any solid data list 4px corners
- [x] Layer separation verified: chrome → `surface-glass` + `backdrop-blur-[20px]` + `shadow-light/dark`; data/lists/forms → `surface-solid` + 1px `border-solid` (no shadow)
- [x] Light/dark parity: toggling `data-theme="light|dark"` shows no low-contrast text. Hero numbers, body, legends, badges, tooltips all ≥4.5:1 against their effective backgrounds in both themes
- [x] `prefers-reduced-transparency: reduce`: all `surface-glass` correctly collapses to `surface-solid`; every backdrop-blur removed globally — visual audit against [app.css](file:///d:/Laravel%20Project/opt-asset/resources/css/app.css) existing rule
- [x] `prefers-reduced-motion: reduce`: chart / gauge / progress-bar transitions disabled; instant state changes only
- [x] 375px mobile view: sidebar closes behind trigger by default; entire dashboard bento collapses to single stacked scrollable column; no horizontal overflow; touch targets ≥44px
- [x] `npm run types:check` passes
- [x] `npm run build` succeeds (or `npm run dev` + reload if manifest error occurs — no ViteException errors in console)
- [x] If any PHP files were touched: run `vendor/bin/pint --dirty --format agent`
- [x] Run impeccable detector over changed targets: `.trae/skills/impeccable/scripts/impeccable.cmd detect --json <dashboard.tsx + all touched components>` and fix any surfaced defects in a single batch
