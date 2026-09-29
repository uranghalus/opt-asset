# Minimal `locations` table lands inside T04, not as a separate ticket

PRD §7 references `assets.lokasi_id` and `asset_mutations.from/to_location_id`, but no `locations` entity is defined anywhere in the data model or tickets (gap found 2026-09-29). Decision: T04 (Asset Core) defines a minimal per-tenant `locations` table (`tenant_id`, `code`, `name`) at the point of first consumption — no new dependency chain, no free-text location fields.

## Considered options

- **Separate ticket before T04** — first-class entity from day one, but costs a planning cycle and inserts a blocker.
- **Free-text location field in MVP** — lightest, but mutations become free text and per-location book-value reporting (PRD §9 reconciliation) loses its backbone.
- **Minimal table inside T04 (chosen)** — solved where first consumed; fields can grow with T05 (mutations) needs.

## Consequences

- T04's grill/spec must fix the field set (`code`, `name`, composite unique `(tenant_id, code)`) and its CRUD placement.
- T05 mutation reporting must treat `locations` as the referenced entity, not ad-hoc strings.
