# DocuPharma User Guide

## 1. Purpose

DocuPharma is a pharmaceutical document and quality management application. It is divided into three licensed modules:

| Module | Purpose | Dependency |
| --- | --- | --- |
| DMS | Controlled documents, templates, approvals, issuance, audit history, and retention | Required core |
| QMS | Change controls, deviations, investigations, and CAPAs | Requires DMS |
| AI | Assisted template generation and AI execution monitoring | Requires DMS |

The menus visible to a user depend on both the enabled modules and the permissions assigned to the user's role.

## 2. Signing In

Open the DocuPharma administration panel:

```text
http://docupharma.test/admin
```

For a local development installation, the default administrator is:

```text
Email: admin@example.com
Password: password
```

Change or remove development credentials before using the application outside a local environment.

After signing in, use the navigation menu to open the DMS, QMS, AI, and administration areas available to your role.

## 3. First-Time Local Setup

Run the following commands from the project directory after installing the application or pulling new migrations:

```powershell
php artisan migrate --no-interaction
php artisan db:seed --no-interaction
php artisan optimize:clear
```

If only the QMS permissions need to be installed or refreshed, run:

```powershell
php artisan db:seed --class=QmsModuleSeeder --no-interaction
php artisan optimize:clear
```

Sign out and sign back in after changing role permissions.

## 4. Module Configuration and Licences

### Local and single-install configuration

The current local entitlement source is configured in `.env`.

Enable DMS only:

```env
DOCUPHARMA_ENTITLEMENT_SOURCE=environment
DOCUPHARMA_MODULES=dms
```

Enable DMS and QMS:

```env
DOCUPHARMA_ENTITLEMENT_SOURCE=environment
DOCUPHARMA_MODULES=dms,qms
```

Enable all modules:

```env
DOCUPHARMA_ENTITLEMENT_SOURCE=environment
DOCUPHARMA_MODULES=dms,qms,ai
```

After changing these values, run:

```powershell
php artisan optimize:clear
php artisan db:seed --no-interaction
```

QMS and AI cannot be enabled without DMS.

### Signed production licences

The Product Licences screen is currently read-only. It shows installed licence state, enabled modules, issuer key, activation dates, expiry, grace period, and audit events.

A signed licence must be issued outside the application and cryptographically verified during activation. Do not manually add or edit rows in the product licence tables. The application will reject invalid, altered, expired, revoked, or dependency-incomplete licences.

## 5. Users, Roles, and Permissions

The name “Super Admin” does not by itself grant unrestricted access. Access is based on assigned roles and permissions.

For the local administrator:

1. Open **Core · Identity & Access → Users**.
2. Open `admin@example.com`.
3. Confirm that the user has the `sop administrator` role.
4. Save changes, sign out, and sign back in.

The module seeders grant their permissions to `sop administrator`.

Examples:

| Permission | Capability |
| --- | --- |
| `ViewAny:SopDocument` | See the controlled-document list |
| `Submit:SopDocument` | Submit a draft document for approval |
| `ViewAny:Deviation` | See QMS deviations |
| `Investigate:Deviation` | Progress a deviation investigation |
| `Approve:ChangeControl` | Approve a change control |
| `VerifyEffectiveness:Capa` | Record whether a CAPA was effective |

Grant only the permissions required for each job function. Use different accounts for maker, reviewer, and approver activities when separation of duties applies.

## 6. DMS Workflow

### 6.1 Configure master data

Before creating controlled documents, an administrator should configure:

- Departments
- Document categories and types
- Document statuses and template statuses
- Regulation tags
- Approval decisions and step types
- Number series
- SOP roles and workflows

These options are available under **Core · Organization**, **DMS Configuration**, and **DMS Settings**, subject to permission.

### 6.2 Create a template

1. Open **DMS · Document Control → SOP Templates**.
2. Select **New SOP Template**.
3. Enter the template name, document type, category, department, and applicable regulation tags.
4. Save the template.
5. Add a draft version, sections, and variables.
6. Select **Submit for Review** and enter an attributable reason.
7. DocuPharma selects the active workflow for the template's department, falling back to the active global SOP workflow.
8. Open **DMS · Document Control → Template Approval Queue**.
9. Complete each configured workflow step in order. The user must have the step's assigned role and department access.
10. A document controller or other user with `Publish:SopTemplate` selects **Publish Approved Version** after every mandatory step is approved.

If AI is enabled, the application can start regulatory template generation in the background. If AI is disabled, the template is saved normally and can be completed manually.

Template editing is frozen after submission. A rejected or returned version becomes editable and may be corrected and resubmitted. The author or submitter cannot decide any approval step, and every step in a submission cycle must be decided by a different user. Approvals, rejections, and returns all receive canonical electronic signatures.

#### Example: Deviation Management SOP template

```text
Name: Deviation Management SOP
Document type: SOP
Category: QMS Core
Department: Quality Assurance
Regulation tags: WHO GMP, US FDA 21 CFR 210/211
```

Suggested sections:

1. Purpose
2. Scope
3. Responsibilities
4. Definitions
5. Procedure
6. Investigation and root-cause analysis
7. CAPA requirements
8. Records and retention

### 6.3 Create a controlled document

1. Open **DMS · Document Control → SOP Documents**.
2. Select **New SOP Document**.
3. Choose an approved template and template version.
4. Enter the title, owner, dates, regulation tags, and requested variable values.
5. Save the document as a draft.
6. Review the rendered content.
7. Select **Submit for Approval**.

Submission locks editing and sends the document through its department approval workflow.

#### Example

```text
Template: Deviation Management SOP
Title: Handling Manufacturing Deviations
Owner: QA Manager
Effective date: 01-Aug-2026
Review date: 01-Aug-2028
Purpose: Define reporting, investigation, CAPA, and closure requirements.
```

### 6.4 Review and approve

1. Open **DMS · Document Control → My Approval Queue**.
2. Open the pending approval assigned to your role and department.
3. Review the document content and metadata.
4. Approve, reject, or return the record with an attributable reason.

The queue combines your currently actionable SOP document, SOP template, and licensed QMS approvals. Use the **Module** and **Approval Type** filters when you want to focus on one workflow. Selecting **Review** opens the owning document, template approval, or quality record; decisions continue to use that module's signed workflow.

Approval decisions create electronic-signature metadata and append-only audit history. A user may be prevented from approving their own submission when separation-of-duties rules apply.

Each SOP document workflow step must be decided by a different user. A user who completed an earlier step cannot approve, reject, or return another step for the same document approval cycle, even when that user holds multiple workflow roles.

### 6.5 Revise an approved document

1. Open the approved or effective document.
2. Select **Create Revision**.
3. Enter a reason, such as:

```text
Updated the deviation escalation timeline from 48 hours to 24 hours
following internal audit observation IA-2026-014.
```

4. Edit the newly created draft revision.
5. Submit it through approval again.

The previous version remains traceable.

### 6.6 Print, issue, and control copies

- Use **Print / PDF** from the document view to render a controlled document.
- Use **DMS · Issuance → Issuance Register** to track issued copies.
- Use **Log Documents** for controlled operational logs.
- Recall or destroy issued copies only through the available lifecycle actions so the audit history is preserved.

## 7. QMS Workflow

The currently exposed QMS workspaces are:

- Change Controls
- Deviations
- Investigations
- CAPAs

Complaints, internal audits, audit findings, risk assessments, supplier qualifications, management reviews, and aggregate quality metrics have backend foundations but are not currently exposed as complete Filament workspaces.

### 7.1 Change control example

Use a change control for a planned change to a process, system, facility, or controlled document.

Example:

```text
Title: Reduce deviation reporting time to 24 hours
Department: Quality Assurance
Owner: QA Manager
Reason: Internal audit IA-2026-014 identified delayed escalation.
Proposed change: Revise the deviation SOP and retrain affected employees.
```

Typical lifecycle:

```text
Draft → Submitted → Under Review → Approved → Implementing
→ Effectiveness Review → Closed
```

Procedure:

1. Open **QMS · Change Control → Change Controls**.
2. Create and save the draft.
3. While it is still a draft, add affected controlled documents under document impacts.
4. Select **Submit for Review**.
5. The reviewer selects **Begin Review** and then **Approve** or **Reject**.
6. For an approved “revise” document impact, create the traced DMS draft revision.
7. Implement the approved actions.
8. Select **Begin Effectiveness Review**.
9. Record the effectiveness reason and select **Close**.

Every consequential transition requires an attributable reason and is written to immutable audit history.

### 7.2 Deviation example

Use a deviation for an unplanned departure from an approved instruction, process, specification, or expected condition.

Example:

```text
Title: Granulation temperature exceeded approved range
Severity: Major
Occurred at: 26-Jul-2026 10:15
Discovered at: 26-Jul-2026 10:30
Department: Production
Description: Product temperature reached 32°C against the approved
operating range of 25–30°C for approximately eight minutes.
Immediate action: Process paused, material segregated, and QA notified.
```

Typical lifecycle:

```text
Draft → Open → Under Investigation → Investigation Complete
→ CAPA Required or Effectiveness Review → Closed
```

Procedure:

1. Open **QMS · Quality Events → Deviations**.
2. Create the draft and record factual, contemporaneous information.
3. Select **Submit** and enter the submission reason.
4. Select **Begin Investigation**.
5. Create a linked Investigation where detailed root-cause work is required.
6. After linked investigations are completed, select **Complete Investigation**.
7. Select **Require CAPA** when corrective or preventive actions are necessary.
8. Complete the effectiveness review before closing the deviation.

### 7.3 Investigation example

Example:

```text
Deviation: Granulation temperature exceeded approved range
Methodology: 5 Whys and equipment alarm-history review
Lead investigator: Production Engineering Lead
Root cause: Temperature probe calibration drift caused delayed cooling response.
Conclusion: Batch impact assessment found no product-quality failure,
but calibration controls require improvement.
```

Typical lifecycle:

```text
Draft → In Progress → Pending Review → Completed
```

The parent deviation cannot pass its investigation-completion gate while required linked investigations remain incomplete.

### 7.4 CAPA example

Example:

```text
Type: Corrective and Preventive
Source: Granulation temperature deviation
Owner: Engineering Manager
Action plan:
1. Replace and recalibrate the temperature probe.
2. Add a monthly probe-drift verification.
3. Retrain granulation operators on alarm escalation.
Effectiveness check: Review three consecutive batches and the next
three monthly verification records.
```

Typical lifecycle:

```text
Draft → Planned → In Progress → Pending Effectiveness
→ Effective → Closed
```

If the effectiveness result is unsuccessful, select **Mark Ineffective**, revise the action plan, and begin implementation again.

## 8. AI Module

When AI is enabled:

- Template creation may dispatch regulated-template generation.
- AI execution status and provider performance are available under **AI Management**.
- Generated content must still be reviewed and approved by authorized personnel.

AI output is assistance, not an approval decision or a replacement for qualified review.

## 9. Audit and Data-Integrity Practices

- Enter clear reasons for every lifecycle decision.
- Never share user accounts for approval activities.
- Do not edit database records to bypass a workflow.
- Upload evidence through the relevant record so its metadata and integrity hash are captured.
- Treat audit histories and electronic signatures as controlled records.
- Use revisions instead of overwriting approved documents.
- Record facts in deviations; keep conclusions in the investigation.
- Do not close CAPAs without documented effectiveness evidence.

## 10. Troubleshooting

### QMS menus are missing

Confirm `.env` contains:

```env
DOCUPHARMA_ENTITLEMENT_SOURCE=environment
DOCUPHARMA_MODULES=dms,qms,ai
```

Then run:

```powershell
php artisan migrate --no-interaction
php artisan db:seed --class=QmsModuleSeeder --no-interaction
php artisan optimize:clear
```

Confirm the user has the `sop administrator` role, then sign out and back in.

### Product Licences has no Add button

This is expected. The screen is intentionally read-only. Local module access is controlled through `.env`; production licences require an issuer-signed payload and a controlled activation path.

### An action button is missing

An action is displayed only when:

- Its module is enabled.
- The user has the required permission.
- The record is in a status from which that action is valid.
- Any separation-of-duties and department rules are satisfied.

Check the record status and the user's assigned role before treating this as a UI error.

### Frontend changes are not visible

Run one of:

```powershell
npm run dev
```

or:

```powershell
npm run build
```

Then refresh the browser.

## 11. Quick Daily Checklist

For document authors:

1. Use the correct published template.
2. Complete all required fields.
3. Verify owner, dates, department, and regulation tags.
4. Submit the draft once it is ready.
5. Do not edit approved content outside the revision process.

For quality reviewers:

1. Confirm the record is complete and attributable.
2. Verify evidence and linked records.
3. Enter a specific decision reason.
4. Observe separation-of-duties requirements.
5. Close records only after all lifecycle gates are satisfied.
