# 04: Asset card panel + quick actions + assignee avatars (middle-right)

**What to build:**
A wide glass card in the right column (beneath the 3 stat cards of ticket 03) matching the reference's "My card + quick actions + quick payment" block, but fully adapted to AMS domain. Contains: a tab switcher, a gradient visual asset-card mock with masked mono-data asset code, a 5-button quick-action row, and a row of 6 assignee avatar chips.

**Blocked by:** 03 (bento-grid shell / right-column scaffold from ticket 03 — avoids merge conflicts appending to the same JSX parent)

**Status:** ready-for-agent

- [x] Tab switcher in card header: "Aset unggulan" active, "Semua aset" inactive; far right "+ Tambah aset" pill button
- [x] "Kartu Aset" visual: diagonal `accent-primary → accent-teal` gradient card (rounded-xl), with nama-pemilik top-left, chip logo top-right, and a mono-data tabular masked-asset-code row across the bottom (e.g. `AST-2024-••••-0091`) — no real barcode graphic here
- [x] Quick action row below asset card: 5 rounded-square icon buttons with text label beneath: `Tambah` (+) · `Transfer` (right-arrow) · `Pinjam` (hand-grab) · `Riwayat` (clock) · `Lainnya` (⋯)
- [x] "Penanggung jawab cepat" section below actions: 6 circular avatar chips (user initials or placeholder stock avatars) + 1 `+` more button, horizontally scrollable on narrow widths
- [x] Solid surface used for action-button tiles; glass on the frame only
- [x] No fintech wording (no "payment", no "top up", no VISA/etc); AMS-appropriate labels only
- [x] TypeScript: `npm run types:check` passes on touched files
