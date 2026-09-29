# 01: Refactor sidebar navigation + add promo card

**What to build:**
User opens the app and sees the left sidebar re-styled to match the reference's navigation look. Sidebar preserves existing Indonesian nav labels but updates visual hierarchy, adds a glass promo card, and adds a collapse affordance — all without breaking the existing sidebar layout shell in `app-sidebar-layout.tsx`.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] Operasional / Laporan / Administrasi nav groups rendered with clear section labels and a left-rail accent on the selected item (matches reference's "Dashboard" selected pill style using `accent-primary`)
- [ ] Each nav item uses consistent icon + label sizing; collapsed mode hides labels leaving only centered icons
- [ ] Glass promo card pinned between bottom of nav groups and NavUser: lightning/bolt icon, title "Upgrade untuk fitur enterprise", 1-line placeholder subcopy, CTA button in `accent-primary` — all rendered with `surface-glass`, backdrop-blur 20px, and AURORA tokens
- [ ] Promo card gracefully collapses to just the bolt icon when sidebar is icon-collapsed
- [ ] "Collapse sidebar" text link with double-chevron at the very bottom-left; clicking toggles existing sidebar collapsed state
- [ ] Existing NavUser dropdown, AppLogo, and SidebarProvider integration continue working after refactor
- [ ] TypeScript: `npm run types:check` passes on touched files
