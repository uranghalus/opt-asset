# 07: Barcode — Single & Batch Generate with Label Preview + PDF (Code128)

**What to build:** An officer can generate a Code128 barcode for one asset from its detail page, or select many assets (checkbox selection + current filters) in the ledger and batch-generate a print sheet: a real downloadable PDF of label cards (asset code in IBM Plex Mono, asset name, Code128 image) rendered server-side via dompdf. Batch runs on the queue with progress feedback, and barcode references are stored tenant-unique.

**Blocked by:** 04: Asset Core; 06: Depreciation Engine (queue worker is configured in 06)

**Status:** ready-for-agent

- [ ] `barcodes` table (tenant_id, asset_id, barcode_value unique per tenant, format=code128, generated_by) + `picqer/php-barcode-generator` dependency
- [ ] Single generation from asset detail; preview label card (radius 0px, print-realistic)
- [ ] Batch: bulk selection in ledger → queued job → dompdf PDF label sheet download; progress + failure states
- [ ] Human-readable text under the barcode (kode_asset + asset name); label layout matches print reality
- [ ] Tests: payload validity, unique-per-tenant values, queue job renders PDF, tenant isolation
