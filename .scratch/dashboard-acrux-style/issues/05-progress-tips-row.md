# 05: Spending/progress bar + quick tips card (middle-left, 2-up)

**What to build:**
Two cards in the middle row immediately below the hero chart on the left (so below the wide hero card of ticket 03, in the left column): a loan/progress-style card showing "Limit aset dipinjamkan" with gradient progress bar, and a tips/education card with a corner grid pattern.

**Blocked by:** 03 (bento-grid shell — both cards append into the same JSX tree beneath the hero)

**Status:** ready-for-agent

- [x] Left glass card — "Limit aset dipinjamkan": pencil edit icon top-right of title; gradient progress bar (success → accent-teal) filling ~86% width, with mono-data start/end labels on either side showing e.g. `86` vs `100` aset
- [x] Right glass card — "Optimalkan manajemen aset": bold headline, 2 lines of short AMS copy (audit Q4 / depreciation saving), decorative 2×3 heatmap-style corner grid of mini cells with AURORA gradient tints in the top-right corner, and "Baca selengkapnya →" link colored `accent-primary`
- [x] Cards live in a 2-column grid on ≥1024px, stacked 1-column on mobile
- [x] Consistent inner padding, title typography, and radius with other glass cards
- [x] TypeScript: `npm run types:check` passes on touched files
