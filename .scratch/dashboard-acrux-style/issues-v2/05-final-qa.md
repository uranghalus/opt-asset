# 05: Final QA pass — checks, detector, visual verification

**What to build:**
One QA pass over the revamped shell: run the full check set on touched files, run the impeccable detector over the shell files and fix surfaced defects in a single batch, and verify visually where possible (dev server + screenshots in both themes) or document exactly why verification is limited.

**Blocked by:** 04.

**Status:** ready-for-agent

- [x] `npm run check` clean on all touched files (format + lint + types)
- [x] `npm run build` succeeds
- [x] Impeccable detector run over all shell files; no non-advisory findings
- [x] Grep audits: no hardcoded hex colors, no `neutral-*`/off-token classes in touched files
- [x] Visual verification in light + dark (screenshots via dev server if reachable; otherwise documented limitation noted in research file)
- [x] All ticket 01–04 acceptance boxes re-checked against the final state
