# DocuPharma User Guide

## 1. Purpose

DocuPharma is a pharmaceutical document and quality management application. It is divided into three licensed modules:

| Module | Purpose | Dependency |
| --- | --- | --- |
| DMS | Controlled documents, templates, approvals, issuance, audit history, and retention | Required core |
| QMS | Change controls, quality events, CAPA, risk management, and computer system validation | Requires DMS |
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
| `ViewAny:CsvValidationProject` | See computer system validation projects |
| `Test:CsvValidationProject` | Progress validation testing and deviation resolution |
| `Release:CsvValidationProject` | Make the independent, signed QA release decision |
| `PeriodicReview:CsvValidationProject` | Start or complete a periodic validation review |

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

- Use **Print / PDF** from the document view only for approved or effective documents that do not require controlled-copy issuance.
- For an issuable controlled document, open **DMS · Issuance → Log Documents**, open the effective document, and select **Issue Controlled Copy**.
- Enter the receiving user, department, or location, then open the document's **Controlled Copy Issuance Register** and select **Print Copy** on the active issuance.
- Use **DMS · Issuance → Issuance Register** to find and print active issued copies.
- Each controlled copy is rendered with its issuance number and watermark. Recalled or destroyed copies cannot be printed.
- Recall or destroy issued copies only through the available lifecycle actions so the audit history is preserved.

## 7. QMS Workflow

The currently exposed QMS workspaces are:

- Change Controls
- Deviations
- Investigations
- CAPAs
- CSV Validation Projects

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

### 7.5 Computer System Validation example

Use **CSV Validation Projects** to demonstrate that a computerized system is fit for its intended GxP use and remains in a controlled, validated state.

Open:

**QMS · Validation → CSV Validation Projects**

The supported lifecycle is:

```text
Draft → GxP Assessment → Planning → Specification → Testing
→ Deviation Resolution (when required) → Validation Review → Released
→ Periodic Review → Released, Revalidation, or Retired
```

#### Recommended user separation

Use separate named accounts. Do not perform the entire validation with `admin@example.com`.

| Responsibility | Example user | Permitted activity |
| --- | --- | --- |
| Project creator/business owner | `process.owner@example.com` | Intended use, requirements, business acceptance criteria |
| System owner | `system.owner@example.com` | System boundary, version, configuration, technical specifications |
| Test executor | `validator@example.com` | Execute approved tests and record actual results |
| Test reviewer | `validation.reviewer@example.com` | Independently review completed execution evidence |
| Quality releaser | `qa.approver@example.com` | Make the final signed QA release decision |

The test reviewer must be different from the executor. The QA releaser must be different from the project creator, business owner, and system owner.

#### Example project

```text
System identifier: DOCUPHARMA-QMS
System name: DocuPharma Quality Management System
System version: 1.0.0
GxP criticality: High
Intended use:
Create, approve, issue, revise, and retain controlled pharmaceutical
documents and quality records with attributable audit history.

Regulatory scope:
- 21 CFR Part 11
- EU GMP Annex 11
- Applicable data-integrity procedures

Electronic records: Yes
Electronic signatures: Yes
Planned release date: 30-Sep-2026
Next periodic review date: 30-Sep-2027
```

#### Step 1: Create the project

1. Select **New CSV Validation Project**.
2. Enter the system identifier, name, version, intended use, and GxP criticality.
3. Add the applicable regulatory scope.
4. Identify the business owner, system owner, quality owner, and department.
5. Indicate whether the system uses electronic records and electronic signatures.
6. Save the project.

DocuPharma generates a project number such as:

```text
CSV-2026-4F8A91CD
```

#### Step 2: Complete the GxP assessment and plan

1. Confirm whether the system affects product quality, patient safety, record integrity, or regulated decisions.
2. Select **Complete GxP Assessment** and enter the reason.
3. Select **Begin Validation Planning**.
4. Enter the validation strategy.
5. Link the approved validation-plan controlled document where available.
6. Record the planned release date and periodic-review frequency.

Example strategy:

```text
Apply a risk-based lifecycle. Approve user requirements and functional
specifications before testing. Execute security, audit-trail, backup/restore,
and critical workflow OQ tests. Complete UAT for intended business use.
Independently review every executed protocol before QA release.
```

#### Step 3: Enter requirements

Open the project's **Requirements** section and create versioned requirements.

Example:

```text
Identifier: URS-001
Version: 1
Category: Data integrity
Statement:
The system shall retain an attributable and append-only history of
consequential lifecycle decisions.

Acceptance criteria:
The history identifies the record, signer, decision meaning, timestamp,
reason, IP address, and signature-integrity hash.

Criticality: Critical
GxP relevant: Yes
Data integrity relevant: Yes
Status: Approved
```

Every GxP, High, or Critical requirement must be linked to an approved test with a passing, independently reviewed execution before release.

#### Step 4: Enter specifications and risks

Create the functional, configuration, design, interface, security, or data specifications needed to explain how requirements are implemented.

Specification example:

```text
Identifier: FS-001
Type: Functional
Title: Signed lifecycle audit history
Description:
Consequential decisions create append-only events containing canonical
electronic-signature metadata.
Status: Approved
```

Risk example:

```text
Identifier: RA-001
Linked requirement: URS-001
Hazard: A regulated record is changed without detection.
Potential impact: Loss of record integrity and unreliable quality decisions.
Initial severity/probability/detectability: 5 / 3 / 3
Initial RPN: 45
Mitigation: Append-only signed events and permission-controlled actions.
Residual severity/probability/detectability: 5 / 1 / 1
Residual RPN: 5
Acceptance rationale: Residual risk is controlled and periodically reviewed.
```

Residual risk must be documented, accepted, and no greater than the initial risk before QA release.

#### Step 5: Create and trace test cases

Create IQ, OQ, PQ, UAT, security, data-migration, backup/restore, or disaster-recovery tests as applicable.

Example:

```text
Test identifier: OQ-001
Type: Operational Qualification
Title: Verify audit-history integrity
Objective: Demonstrate attributable, append-only signed decision events.
Linked requirement: URS-001
Criticality: Critical
Status: Approved

Step 1:
Perform an approval using the assigned reviewer account.

Expected result:
The application records the signer, decision, time, reason, client metadata,
and a verifiable signature hash.
```

Link every applicable requirement to its tests. The requirement and test counts on the project provide a quick readiness indication, but the signed release gate performs the authoritative completeness check.

#### Step 6: Execute and independently review tests

For each execution, record:

- Execution number
- Validation environment
- Exact application version
- Commit reference and configuration hash, when applicable
- Result for every protocol step
- Actual result and evidence reference
- Overall result
- Executor, reviewer, start/completion time, and review time

Example:

```text
Execution: OQ-001 / Run 1
Environment: Validation
Application version: 1.0.0
Configuration hash: 61e8...f204
Overall result: Passed
Executor: validator@example.com
Reviewer: validation.reviewer@example.com
Evidence summary: EV-001 screenshots and exported audit history reviewed.
```

Important controls:

- The reviewer cannot be the executor.
- A reviewed execution becomes immutable.
- A Failed or Blocked completed execution must be linked to a deviation.
- Correct the issue through deviation/change control, then execute a new numbered run. Do not overwrite the failed run.
- The latest relevant execution must be Passed and independently reviewed for release credit.

#### Step 7: Record the release baseline and validation summary

Before selecting **QA Release**, complete:

```text
Release baseline:
application_version = 1.0.0
source_commit = 8c41f7...
configuration = VAL-CONFIG-001 revision 1
database_migration = 2026-07-29 baseline

Validation summary:
All approved critical and GxP requirements are traced to approved tests.
All credited executions passed and were independently reviewed.
Residual risks were accepted. No unresolved release-blocking deviations remain.
```

Link the approved validation-summary controlled document where applicable.

#### Step 8: Perform validation review and QA release

1. From Testing, select **Begin Validation Review**.
2. Confirm that requirements, specifications, risks, traceability, executions, deviations, baseline, validation summary, and the next review date are complete.
3. Sign in as the independent QA releaser.
4. Select **QA Release**.
5. Enter a specific decision reason.

Example decision reason:

```text
QA reviewed the approved validation plan, requirement traceability,
specifications, accepted residual risks, executed evidence, deviations,
and validation summary. Baseline version 1.0.0 is released for intended
GxP use.
```

The release is rejected when:

- The validation strategy, release baseline, summary, or next review date is missing.
- No approved requirements exist.
- A requirement is not approved.
- A GxP, High, or Critical requirement lacks an approved and independently reviewed passing test.
- A specification is not approved.
- Residual risk is incomplete, unaccepted, or greater than initial risk.
- The QA releaser is also the creator, business owner, or system owner.

The successful release records the QA signer, timestamp, reason, IP address, user agent, and canonical signature hash in immutable project history.

#### Step 9: Maintain the validated state

After release:

1. Process system changes through Change Control.
2. Assess each change for validation impact.
3. Update affected requirements, specifications, risks, tests, and baseline using new versions or runs.
4. Select **Begin Periodic Review** when the review becomes due.
5. Record the review scope, findings, conclusion, and next review date.
6. Choose one outcome:
   - **Continue Validated Use**
   - **Require Revalidation**
   - **Retire System**

Never modify a reviewed test execution or released evidence to represent a later system state.

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
- Keep the CSV release baseline aligned with the deployed application and approved configuration.
- Preserve failed validation runs and resolve them through linked deviations.
- Use separate accounts for test execution, test review, and QA release.

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

### QA Release is blocked

Open the validation project and check:

1. Validation strategy, release baseline, validation summary, and next periodic-review date.
2. Requirement and specification approval status.
3. Requirement-to-test links.
4. Latest passing execution and independent reviewer for every GxP/High/Critical requirement.
5. Residual-risk scoring, acceptance rationale, acceptor, and acceptance time.
6. Failed or blocked runs and their linked deviations.
7. Whether the current user is also the project creator, business owner, or system owner.

Do not bypass the gate by editing the database. Correct the incomplete record or use the appropriate deviation/change-control process.

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

For validation teams:

1. Define intended use and the release baseline precisely.
2. Trace every GxP, High, and Critical requirement to approved tests.
3. Preserve actual results and failed runs.
4. Use different users for execution, review, and QA release.
5. Schedule periodic review and control every post-release change.
