# Issuance, Printing & Execution

Effective issuable masters are distributed as controlled copies. Writable types create a separate GMP execution record that snapshots the approved master.

## Issue a controlled copy

Only an **Effective**, issuable document with a valid effective SOP reference (when required) can be issued.

1. Open the effective document from **Controlled Documents** or **Issuable Documents**.
2. Select **Issue Controlled Copy**.
3. Select the copy type:
   - **Read-only reference copy** for controlled viewing and printing.
   - **Writable GMP execution record** for FORM, LOG, CHECKLIST, BMR, or BPR.
4. Select the recipient user and/or department, optional location, and notes.
5. Enter **Number of copies**. Each copy in the batch receives its own sequential copy number, issuance number, and watermark.
6. For BMR/BPR execution copies, enter batch and product information.
7. For logs, enter frequency, period, and supervisor as applicable.
8. Confirm issuance.

The system assigns sequential copy numbers, **issuance numbers**, watermark codes, issuer, and issue timestamp. Writable execution copies still create one execution record per copy.

## Print and fill on paper

Some forms, logs, and checklists are completed by hand. Do not use **Issue Controlled Copy** for that — that action still creates electronic reference or execution copies.

1. Open the effective document from **Controlled Documents** or **Issuable Documents**.
2. Select **Print and fill on paper**.
3. Select the recipient user and/or department, optional location, number of copies, and notes.
4. Confirm. The system records numbered paper copies in the issuance register. It does **not** create electronic GMP execution records.
5. **Print** opens with a page preview, like printing from Word. Choose a printer and print. Each copy starts on a new page with its own copy number. Large batches prepare in the background; keep the print window open until the pages appear.
6. Reprint from the Issuance Register with **Print** (one copy) or **Print all copies** (every copy issued together).

Filled paper stays in the physical archive. Recall or destroy unused numbered copies from the Issuance Register when they are no longer valid.

## After issuance

1. Open **DMS → Issuance Register**.
2. Select **View Controlled Copy** on an **Active** issuance to open the watermarked controlled viewer.
3. Select **Print** on an active issuance when a single controlled print is required. Print opens a preview, then the printer dialog.
4. To print many copies at once, stay on **DMS → Issuance Register**:
   - Use the **All copies**, **Reference copy**, **Writable execution record**, and **Paper copy** tabs to find the right records.
   - Select the copies and choose **Print**, or use **Print by copy number**. Paste a comma-separated list (`SOP-QA-00001-C01,SOP-QA-00001-C02`) or an inclusive range (`SOP-QA-00001-C01-SOP-QA-00001-C100`).
   - Print copies from one master document at a time. Each copy starts on a new page with its own copy number and watermark.
5. For paper copies issued together, select **Print all copies**.
6. Recalled or destroyed copies cannot be viewed or printed as controlled copies.
7. Use **Recall** or **Destroy** with a recorded reason. Statuses move **Active → Recalled** and **Active/Recalled → Destroyed**.

## Direct print versus controlled copy

- Issuable masters must be printed or viewed through an active controlled-copy issuance.
- Non-issuable masters may use **Print** when they are Approved or Effective, subject to PDF access policy. Print opens a preview, then the printer dialog.
- Draft/review print layout preview is for authoring review only and is not controlled printing.

Print templates can show issuance number in the header, footer, and body blocks when configured on the report template.

## Print templates (signature style)

**DMS Settings → Print and report templates**, Body Blocks → Approval Signatures → **Signature style**:

- **Electronic signatures** (default) — prints the recorded electronic-signature manifestation (signer identity, meaning, date/time).
- **Manual signature lines** — prints blank Sign & Date lines for wet-ink signing; name and designation still print.
- **Electronic, except blank lines on paper copies** — electronic on controlled/PDF copies; blank lines on paper copies.

In-app electronic signatures (password re-entry, hash, audit) stay the same whichever style you choose.

## Print audit trail

Controlled print, view, and download write append-only document audit entries (`printed`, `viewed`, `downloaded`). Open the document → **Audit**, or the dashboard **Recent Audit Activity**.

Each print record includes user, time, IP, and browser. A single-copy print also stores issuance number, watermark, template, and PDF checksum. A combined multi-copy print is logged when that file is first generated; opening the same print again later does not add another print row. Denied print/view/download is logged as `pdf_access_denied`. Draft print-layout preview is not a controlled print.

## Execution records

Writable issuance creates a `DocumentExecution` that snapshots the approved master. Later master revisions do not alter an already issued execution record.

### Start and enter data

1. Open **DMS → GMP Execution Records**, or choose **Open Execution Record** from the Issuance Register.
2. Select **Begin execution**.
3. Enter contemporaneous responses/readings and comments in each section.
4. Complete required section statuses (**Completed** or **Not applicable** with notes).
5. Use a different authorized user for independent verification where required.
6. Upload execution evidence/annexures against the execution record when required. These files are private, integrity-checked, and belong to the execution—not the approved master.

### Complete and submit

Select **Complete and submit** only when all required fields contain responses, every section is Completed or validly N/A, every N/A has an explanation, and required independent verification is complete.

### Supervisor review and QA disposition

- The supervisor selects **Complete supervisor review** and must not be the recorded executor.
- LOG and CHECKLIST executions normally close after successful supervisor review.
- BMR/BPR advance to QA review. QA selects **Release batch** or **Reject batch**, enters notes, and must be independent of execution and production review.

## Issued-copy workflow by type

| Document type | Normal issued-copy workflow |
| --- | --- |
| SOP, Policy, Manual | Controlled read-only reference copy. |
| Report, Protocol, Specification, Validation, Annexure | Controlled read-only reference copy. |
| Form | Writable execution; complete required fields/sections, then close. Use **Print and fill on paper** when the form is completed by hand. |
| Log | Writable execution; complete scheduled entries; supervisor review. Use **Print and fill on paper** when the log is completed by hand. |
| Checklist | Writable execution; complete and verify items; supervisor review. Use **Print and fill on paper** when the checklist is completed by hand. |
| BMR / BPR | Writable execution; verify items; supervisor review; reconcile materials; independent QA disposition. Use **Print and fill on paper** when the record is completed by hand. |

## Checklist: execution ready for submission

- All required responses entered.
- All sections completed or justified as N/A.
- N/A explanations recorded.
- Independent item verification complete where required.
- Correct supervisor assigned.
- Required execution evidence uploaded.
