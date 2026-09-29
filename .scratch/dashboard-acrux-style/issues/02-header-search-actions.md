# 02: Add header top-bar with search + actions

**What to build:**
The glass top header in `app-sidebar-header.tsx` matches the reference header row: full-width quick search input on the left side, and a row of controls (notifications bell, settings cog, user avatar chip, add-widget pill) on the right. Header sits inside the existing glass header and respects solid-surface rules for the search input per DESIGN.md.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] Quick search input: renders with `surface-solid` background (solid form rule), magnifier icon prefix, "Quick search" placeholder, consistent left padding, placed between sidebar trigger and tenant name+breadcrumbs
- [ ] Right-side controls row (in order): notifications icon button, settings icon button, user avatar + name + email inline chip (reuses existing NavUser/UserMenuContent dropdown behavior), "+ Add widget" pill button with `+` icon on far right
- [ ] All icon buttons and avatar chip use ≥36px touch targets with pill or `sm` radius matching DESIGN.md tokens
- [ ] On mobile widths, search input collapses to a search icon-only button that opens an input
- [ ] All controls correctly swap light/dark tokens, no hardcoded colors
- [ ] Breadcrumbs + tenant name, SidebarTrigger, and the new elements all fit without wrapping on ≥1024px viewports
- [ ] TypeScript: `npm run types:check` passes on touched files
