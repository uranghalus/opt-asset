# Research — App shell (`app-sidebar-layout`) vs. primary-source contracts

Investigated against primary sources (official docs + installed package source). Every claim cites its owner. Workspace-only facts are marked as such (source = this repo's code).

## Sources

1. **shadcn/ui Sidebar docs** — https://ui.shadcn.com/docs/components/sidebar — "If you use the inset variant, remember to wrap your main content in a `SidebarInset` component."
2. **shadcn/ui upstream discussion #6057** — https://github.com/shadcn-ui/ui/discussions/6057 — known nested-`<main>` problem when a page component also renders `<main>` inside `<SidebarInset>`.
3. **Inertia shared data** — https://inertiajs.com/docs/v3/data-props/shared-data — shared props from `HandleInertiaRequests::share()` merge into every page's props and are read via `usePage()`.
4. **Inertia persistent layouts** — https://www.vincentschmalbach.com/mastering-inertia-js-persistent-layouts/ (secondary summary of official behavior; primary contract in `app.tsx` `layout()` resolver) — layout state (e.g. sidebar open state) persists across navigations when the same layout component instance is reused.
5. **Radix UI Tooltip** — https://www.radix-ui.com/primitives/docs/components/tooltip — "the Tooltip needs `TooltipProvider` wrapped around" it; provider set once at app root.
6. **Tailwind CSS v4** — https://tailwindcss.com/blog/tailwindcss-v4 — CSS-first configuration; `@theme` maps CSS variables to utility namespaces; arbitrary values like `backdrop-blur-[20px]` are generated on demand.
7. **Workspace fact** — `resources/js/hooks/use-appearance.tsx` applies theme via a `.dark` **class** on `documentElement` (localStorage key `appearance`, cookie for SSR), *not* via `data-theme` attribute.
8. **Workspace fact** — `app/Http/Middleware/HandleInertiaRequests.php` shares `name`, `auth.user`, and `sidebarOpen` (derived from the `sidebar_state` cookie).

## Findings mapped to the current shell

| # | Finding | Primary source | Impact |
|---|---------|----------------|--------|
| F1 | The shell renders `SidebarInset` (via `AppContent`) *and* `dashboard.tsx` renders its own `<main>` — two nested `<main>` landmarks | shadcn docs + discussion #6057 | A11y: screen readers see two main landmarks. Fix: pages should render `<div>` under the inset, or the inset should not be `main`. |
| F2 | Sidebar uses `variant="inset"` + `AppContent variant="sidebar"` → `SidebarInset`, which is the documented correct pairing; the custom glass styling rides on top via classNames, not forks of the primitive | shadcn docs | Good — keep the primitive contract, restyle with tokens. |
| F3 | `SidebarProvider defaultOpen={sidebarOpen}` seeds the sidebar state from a shared prop so SSR/first paint matches the cookie; the cookie is written by the shadcn primitive | Inertia shared-data docs + HandleInertiaRequests | Correct pattern; a revamp must keep `defaultOpen` wired or collapse state flashes on reload. |
| F4 | `app-sidebar-header.tsx` imports `Breadcrumbs` but never renders it — the breadcrumb trail is dead code even though layouts pass breadcrumbs in | Workspace (`app-sidebar-header.tsx`, `app-layout.tsx`) | UX regression: users lose location context inside the app; the `app-header.tsx` variant proves the intended usage. |
| F5 | TooltipProvider is mounted once in `app.tsx` with `delayDuration={0}`, so sidebar collapse tooltips need no per-component provider | Radix docs + `app.tsx` | Revamp must not add nested providers (harmless but redundant) and may rely on the root one. |
| F6 | The `.dark` class is the actual dark-mode switch; DESIGN.md documents `data-theme` | use-appearance.tsx vs DESIGN.md §Dark/Light | Doc drift: `app.css` also carries a `[data-theme="dark"]` selector that nothing sets. Either set both in `applyTheme` or update DESIGN.md. Ticketed separately (theme-toggle ticket). |
| F7 | Tailwind v4 `@theme` tokens (`--surface-glass`, `--shadow-light`, …) make glass styling pure-utility; arbitrary blur values are valid and compile on demand | Tailwind v4 blog + `resources/css/app.css` | The glass treatment (blur 20px panel / 10px nested) can be applied via utilities without new CSS. |
| F8 | Layout persistence: as long as `app-layout.tsx` → `AppLayoutTemplate` stays the same component reference for all pages, `SidebarProvider` state survives navigation | Inertia persistent-layout behavior | The revamp must not swap layout identity between pages or sidebar/search state will reset per navigation. |

## Practical implications for the revamp

1. Keep `SidebarProvider` / `Sidebar` / `SidebarInset` primitive contracts intact (F2, F3, F8); the revamp is a styling + composition pass, not a primitive fork.
2. Render breadcrumbs in the header to restore location context (F4) — `breadcrumbs` prop already arrives.
3. Avoid the double-`<main>` smell when touching page composition (F1) — at minimum flag it; fixing page components is out of scope for the shell ticket but the shell can keep `SidebarInset` as the single `main`.
4. Glass chrome classes can be composed from existing tokens (F7); no new CSS variables are required for the revamp itself.
5. The `data-theme` vs `.dark` divergence is documentation drift, not a blocker (F6) — handled as its own small ticket.

## Verification status (post-implementation)

- Verified programmatically: `tsc --noEmit`, `npm run build`, scoped `vp check` (format+lint, 0 warnings), impeccable detector (5 advisory, 0 defects), grep audits (no hex colors, no `neutral-*`/dead strings in touched files).
- NOT verified in a live browser: the dashboard route requires SSO authentication and no local login exists in this environment. Visual parity (light/dark, 375px) is reasoned from tokens and global CSS rules but unexercised — the audit's CORRECTNESS cap stands until a human or an authenticated session runs the dev server.
