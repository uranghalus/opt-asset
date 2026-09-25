# 03: Sidebar revamp — grouped nav, active state, promo, collapse

**What to build:**
The sidebar matches the Acrux reference: grouped navigation (Operasional / Laporan / Administrasi) with clear section labels, an active item treatment with left accent rail and tinted pill, consistent icon+label sizing, the enterprise promo card that collapses to a bolt icon when the sidebar collapses, and the bottom collapse affordance. Icon-collapsed mode keeps the shell usable (centered icons, tooltips).

**Blocked by:** 01 (prefactor). Parallel-safe with 02 once 01 lands.

**Status:** ready-for-agent

- [x] Group labels use the small/secondary treatment; nav items use consistent icon + label sizing with the active pill + left accent rail (accent-primary)
- [x] Active state follows the current URL via `useCurrentUrl`; Wayfinder route used for Dashboard; no hardcoded URLs
- [x] Promo card: bolt icon, "Upgrade untuk fitur enterprise" + 1-line subcopy, CTA in accent-primary; collapses to the bolt tile in icon mode
- [x] Collapse affordance at the bottom toggles the existing sidebar state; NavUser dropdown and AppLogo keep working
- [x] Icon-collapsed mode: centered icons, labels hidden, tooltips via the root TooltipProvider
- [x] Glass chrome uses `surface-glass` + blur 20px + `shadow-light/dark`; active/hover tints from tokens only
- [x] `npm run types:check` passes and `npm run build` succeeds
