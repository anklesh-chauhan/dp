# Document Types & Classification

Use this guide when creating a **Document Template** or **Controlled Document** so you select the correct DocuPharma DMS document type and format profile.

Classification affects issuance options, whether a writable execution record is available, and which completion checks apply.

## How to choose

Ask three questions:

1. **Is this a governing text document?** Use SOP, Policy, or Manual.
2. **Is this blank data capture that must be filled when issued?** Use Form, Log, Checklist, BMR, or BPR.
3. **Is this controlled reference material?** Use Report, Protocol, Specification, Validation, or Annexure.

Except for SOP, Policy, and Manual, most configured types require an **effective referenced SOP** before an effective controlled copy can be issued.

## Document-type matrix

| Type | Code | Format | Issued copy | Writable execution |
| --- | --- | --- | --- | --- |
| Standard Operating Procedure | SOP | Text document | Reference | No |
| Policy | POLICY | Text document | Reference | No |
| Manual | MANUAL | Text document | Reference | No |
| Controlled Form | FORM | Controlled form | Reference or execution | Yes |
| Log Document | LOG | Repeating log | Reference or execution | Yes |
| Checklist | CHECKLIST | Checklist | Reference or execution | Yes |
| Batch Manufacturing Record | BMR | Controlled form | Reference or execution | Yes |
| Batch Packaging Record | BPR | Controlled form | Reference or execution | Yes |
| Report | REPORT | Structured table | Reference | No |
| Protocol | PROTOCOL | Structured table | Reference | No |
| Specification | SPEC | Structured table | Reference | No |
| Validation | VALIDATION | Structured table | Reference | No |
| Annexure | ANNEXURE | Attachment package | Reference | No |

A writable execution copy is available only when the type’s format requires an execution record: **FORM**, **LOG**, **CHECKLIST**, **BMR**, and **BPR**.

## Quick selection guidance

### SOP

Use for controlled procedural instructions. Recommended sections include purpose, scope, responsibilities, definitions, procedure, records, references, and revision history. Issue as a controlled **reference** copy.

### Policy / Manual

Use Policy for management intent and governance. Use Manual for a collection of related controlled topics (for example a quality manual). Both use the common approval and reference-copy workflow.

### Controlled Form

Use as a blank GMP data-capture master. Define execution fields on the master; enter values only on the issued writable copy.

### Log

Use for hourly, shift, or daily recurring entries. Logging frequency and period are selected when the writable copy is issued. Supervisor review is required.

### Checklist

Use for line clearance, cleaning, and inspection checks. Required responses, independent item verification, and supervisor review apply.

### BMR / BPR

Use for batch manufacturing or packaging execution. These require independent verification, supervisor review, material reconciliation review, and independent QA release/reject disposition.

### Report / Protocol / Specification / Validation

Use for structured controlled reference content such as acceptance criteria, protocols, reports, and validation documents. Issue as read-only reference copies.

### Annexure

Use for controlled supporting evidence packages (certificates, drawings, photographs). Maintain an attachment index and integrity information. Issue as a reference copy.

## Related QMS-named codes

Some controlled-document codes (for example Change Control, CAPA, Deviation, Audit) can exist as **DMS controlled masters** for document control. Their operational QMS lifecycles belong to the QMS module when that module is licensed. In a DMS-only installation, treat them as controlled text/reference documents unless their format profile is deliberately changed to a writable type.

## Before you save

- Confirm the document type matches how the record will be used on the floor.
- Confirm whether the site needs a **reference copy**, a **writable execution copy**, or both.
- Confirm an effective SOP reference is available when the type requires one.
- Confirm the department has an active approval workflow.
