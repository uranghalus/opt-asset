# 14: Final QA Pass — Responsive, A11y, Light/Dark Parity, Full CI

**What to build:** The whole application passes the final polish gate: every data surface respects the glass/solid layer rule, both themes have full parity, reduced-transparency/motion fallbacks hold, mobile layouts use the stacked-card pattern with the Scan-priority tab bar, all touch targets are ≥44px, and the full CI pipeline (lint, format, types, PHPStan, tests, SSR build) is green.

**Blocked by:** 01–13 (all tickets — this is the final polish pass, not a partial one)

**Status:** ready-for-agent

- [ ] Responsive sweep at 375px / tablet / desktop for every page (stacked cards, no horizontal scroll)
- [ ] A11y: contrast ≥4.5:1 on both themes, status badges with text + role, aria-live scan results, keyboard nav on tables and cascading selectors, focus trap in dialogs
- [ ] Light/dark parity audit; reduced-transparency + reduced-motion fallbacks verified
- [ ] SSR verified for every page (meaningful first-paint HTML, no client-only content flash)
- [ ] Full `composer ci:check` + `npm run build:ssr` green; tenant-isolation test suite at 100% domain-query coverage
