# 02: SAML Controller & Auth Logic

**What to build:** Integrate the `SamlController` from existing work, bringing in the SP-initiated authentication handles, and Identity Validation functions into `opt-asset`.

**Blocked by:** 01-core-saml-setup.md

**Status:** ready-for-agent

- [x] Copy `SamlController.php` from `opt-work` to `opt-asset`.
- [x] Modify references or User lookup strategies to match `opt-asset` User properties if they differ.
- [x] Resolve any trait or namespace conflicts.
