# 08: Scan Barcode — Fail-Closed Lookup Page (Mobile-First)

**What to build:** Field staff open the scan page (mobile-priority tab bar), point a hardware scanner or type the code: input auto-focuses, submit happens automatically on scan, the asset is looked up strictly within the acting tenant, and the browser goes straight to the asset detail. Unknown codes produce the exact tenant-named error with the scanned code echoed back; session scan history accumulates below.

**Blocked by:** 07: Barcode (barcode values must exist to scan)

**Status:** ready-for-agent

- [ ] Large auto-focused scan input (scanner keyboard-emulation) with auto-submit; lookup is async with a non-blocking indicator so consecutive scans aren't blocked
- [ ] Server lookup resolves code within acting tenant only; miss → explicit "Aset tidak ditemukan di [tenant]" with scanned code shown for re-verification
- [ ] Session scan history list + aria-live result region
- [ ] Success path navigates to detail; mobile tab bar entry (Scan / Aset / Dashboard)
- [ ] Tests: hit path, cross-tenant miss fail-closed, unknown code error copy, tenant isolation
