# 01: Prefactor — remove dead starter-kit header variant

**What to build:**
The codebase has a single app-shell variant again. The unused starter-kit header layout (component + layout file + the `variant="header"` branches in the shell primitives) is deleted, and the dead breadcrumb import in the sidebar header is removed. No visible behavior changes; the sidebar shell keeps working identically.

**Blocked by:** None (can start immediately).

**Status:** ready-for-agent

- [x] `app-header.tsx` and `app-header-layout.tsx` deleted; no dangling imports remain anywhere
- [x] `AppShell` and `AppContent` no longer carry a `variant` prop or dead branches; `AppVariant` type removed from shared types
- [x] Dead `Breadcrumbs` import removed from `app-sidebar-header.tsx` (breadcrumbs are re-introduced properly by ticket 02)
- [x] `npm run types:check` passes and `npm run build` succeeds
