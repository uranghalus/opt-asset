# 02: Shell revamp — layout + header per Acrux reference

**What to build:**
The app layout matches the Acrux reference composition: an aurora gradient base with a glass sidebar, a glass content panel that floats over the gradient with visible gutter and rounded corners, and a glass header row containing (in order) the sidebar trigger, the breadcrumb trail, a quick-search input, and the right-side control cluster (notifications, settings, user chip, primary pill). All colors from DESIGN.md tokens only; data areas inside the content panel stay solid per the layer-separation rule.

**Blocked by:** 01 (prefactor — single variant path, dead code gone).

**Status:** ready-for-agent

- [x] Aurora gradient base layer renders behind everything (fixed, `bg-base-start → bg-base-end` tokens, light/dark aware)
- [x] Content area is a glass panel: `surface-glass` + `backdrop-blur-[20px]` + `shadow-light/dark` + 16px radius, with a visible gradient gutter around it (not edge-to-edge chrome)
- [x] Scrollable content region inside the panel stays `surface-solid` (data-first rule), no blur behind tables
- [x] Header row (glass, inside the panel): SidebarTrigger → Breadcrumbs (restored, uses the layout's `breadcrumbs` prop) → quick search (flex-1, max-w) → control cluster (bell with unread dot, settings, user chip reusing `UserMenuContent`, primary pill)
- [x] "Add widget" placeholder pill replaced by a real primary action or removed per reference
- [x] No hardcoded colors anywhere; light and dark both use tokens; ⌘K chip either removed or wired to focus the search input
- [x] `npm run types:check` passes and `npm run build` succeeds
