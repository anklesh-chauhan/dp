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
5. For BMR/BPR execution copies, enter batch and product information.
6. For logs, enter frequency, period, and supervisor as applicable.
7. Confirm issuance.

The system assigns a sequential copy number, **issuance number**, watermark code, issuer, and issue timestamp.

## After issuance

1. Open **DMS → Issuance Register**.
2. Select **View Controlled Copy** on an **Active** issuance to open the watermarked controlled viewer.
3. Select **Print Copy** on an active issuance when a controlled print/PDF is required.
4. Recalled or destroyed copies cannot be viewed or printed as controlled copies.
5. Use **Recall** or **Destroy** with a recorded reason. Statuses move **Active → Recalled** and **Active/Recalled → Destroyed**.

## Direct print versus controlled copy

- Issuable masters must be printed or viewed through an active controlled-copy issuance.
- Non-issuable masters may use direct **Print / PDF** when they are Approved or Effective, subject to PDF access policy.
- Draft/review print layout preview is for authoring review only and is not controlled printing.

Print templates can show issuance number in the header, footer, and body blocks when configured on the report template.

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
| Form | Writable execution; complete required fields/sections, then close. |
| Log | Writable execution; complete scheduled entries; supervisor review. |
| Checklist | Writable execution; complete and verify items; supervisor review. |
| BMR / BPR | Writable execution; verify items; supervisor review; reconcile materials; independent QA disposition. |

## Checklist: execution ready for submission

- All required responses entered.
- All sections completed or justified as N/A.
- N/A explanations recorded.
- Independent item verification complete where required.
- Correct supervisor assigned.
- Required execution evidence uploaded.
