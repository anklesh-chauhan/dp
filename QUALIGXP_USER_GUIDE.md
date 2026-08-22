# QualiGxP User Guide

QualiGxP is a pharmaceutical document and quality management application. It is **designed to support** customer compliance with 21 CFR Part 11, EU GMP Annex 11, cGMP record-keeping, and ICH Q10 processes. It is **not certified**, **not validated out of the box**, and **not itself** cGMP- or ICH Q10-compliant. The customer remains responsible for validated intended use, site procedures, and inspection claims.

This guide matches the current administration panel. For the DMS document-type matrix, BMR/BPR execution path, and validation-message resolutions, see the [DMS Process Workflow Guide](./DMS_PROCESS_WORKFLOW_GUIDE.md). In-app how-to pages live under **DMS · Help & Knowledge → Knowledge Library**.

## Contents

1. [Purpose and modules](#1-purpose-and-modules)
2. [Signing in](#2-signing-in)
3. [First-time local setup](#3-first-time-local-setup)
4. [Module configuration and licences](#4-module-configuration-and-licences)
5. [Navigation map](#5-navigation-map)
6. [Users, roles, and permissions](#6-users-roles-and-permissions)
7. [Core · Identity and Access](#7-core--identity-and-access)
8. [Electronic signatures and audit history](#8-electronic-signatures-and-audit-history)
9. [DMS](#9-dms)
10. [QMS](#10-qms)
11. [AI module](#11-ai-module)
12. [Table actions](#12-table-actions-and-pinned-action-menus)
13. [Audit and data-integrity practices](#13-audit-and-data-integrity-practices)
14. [Troubleshooting](#14-troubleshooting)
15. [Daily checklists](#15-quick-daily-checklists)

## 1. Purpose and modules

Menus depend on enabled modules **and** the permissions on the signed-in user's role.


| Module | Purpose | Dependency |
| ------ | ------- | ---------- |
| DMS | Controlled documents, templates, approvals, issuance, GMP execution, audit history, and retention | Required core |
| QMS | Quality events, CAPA, change control, complaints, audits, risk, suppliers, PQR, recall, laboratory OOS, equipment, competency, Schedule M tools, and computer system validation | Requires DMS |
| AI | Assisted template generation, document draft assistant, and AI execution monitoring | Requires DMS |


QMS and AI cannot be enabled without DMS.

## 2. Signing in

Open the QualiGxP administration panel:

```text
http://qualigxp.test/admin
```

Local development administrator (change before any non-local use):

```text
Email: admin@example.com
Password: password
```

After signing in:

1. Complete or skip the **App Guide** tour (restart it later from the user menu).
2. Open **My Profile** to change your password or enroll an authenticator app when MFA is required (`GXP_MFA_REQUIRED=true`).
3. Use the left navigation for DMS, QMS, AI, and Core areas available to your role.

Idle sessions expire after 20 minutes by default (`SESSION_LIFETIME` / `GXP_SESSION_LIFETIME`). Electronic-signature timestamps are stored in UTC and displayed in the organization timezone.

## 3. First-time local setup

Windows PC setup (Herd, PHP 8.4, PostgreSQL, Docker, Gotenberg, queue worker, first login) is in [WINDOWS_INSTALLATION.md](./WINDOWS_INSTALLATION.md).

Start Gotenberg for PDFs:

```powershell
docker compose up -d
```

After install or new migrations:

```powershell
php artisan migrate --no-interaction
php artisan db:seed --no-interaction
php artisan optimize:clear
```

Keep a queue worker running for backups, imports, PDF generation, and AI jobs:

```powershell
php artisan queue:work --timeout=3600
```

Or use `composer run dev`.

Refresh QMS permissions only:

```powershell
php artisan db:seed --class=QmsModuleSeeder --no-interaction
php artisan optimize:clear
```

Sign out and sign back in after changing role permissions.

## 4. Module configuration and licences

### Local and single-install configuration

In `.env`:

```env
QUALIGXP_ENTITLEMENT_SOURCE=environment
QUALIGXP_MODULES=dms
```

```env
QUALIGXP_ENTITLEMENT_SOURCE=environment
QUALIGXP_MODULES=dms,qms
```

```env
QUALIGXP_ENTITLEMENT_SOURCE=environment
QUALIGXP_MODULES=dms,qms,ai
```

Then:

```powershell
php artisan optimize:clear
php artisan db:seed --no-interaction
```

### Signed production licences

**Core · Identity & Access → Product Licenses** is read-only. It shows licence state, enabled modules, issuer key, activation dates, expiry, grace period, and audit events.

Do not add or edit product-licence rows by hand. Invalid, altered, expired, revoked, or dependency-incomplete licences are rejected.

## 5. Navigation map

Items appear only when the module is enabled and the user has permission.

### DMS Dashboard

Home page. Document counts by status, creation trend, pending-approval preview, and recent audit activity.

### DMS


| Screen | Use for |
| ------ | ------- |
| **Controlled Documents** | Author and manage controlled-document masters. |
| **Document Templates** | Create, revise, and publish reusable templates. |
| **Template Approval Queue** | Decide pending template approval steps. |
| **Approval Queue** | Decide pending controlled-document (and licensed QMS) approval steps. |
| **My Approval Queue** | Combined queue of currently actionable document, template, and QMS approvals assigned to you. |
| **Issuable Documents** | Effective issuable masters; issue controlled copies. |
| **Issuance Register** | Find issued copies; open execution records; view, print, recall, or destroy copies. |
| **GMP Execution Records** | Begin, complete, review, and close writable execution copies. |
| **SOP Workflows** | Department and global approval workflows. |
| **Import Batches** | Uploaded files converted into controlled-document drafts. |


### DMS · Reports

**Report Library**, **Document Register**, **SOP Where-Used**, **Periodic Review**, **Pending Approvals**, **Issuance Register**, **GMP Executions**.

### DMS · Help & Knowledge

**Knowledge Library** (published how-to guides for enabled modules) and **Lessons Learned**.

### DMS · Settings

Opened from **DMS Settings**:

- **Document & Template Management:** categories, types, document statuses, template statuses, print and report templates.
- **Issuance & Workflow Approvals:** approval decisions, step types, SOP roles, issuance statuses.
- **Numbering & Identification:** number series and defaults.
- **System & Security Configuration:** regulation tags, variable data types, PDF access policies.

### Core · Identity & Access

**Organization Profile**, **Users**, **Departments**, **Designations**, **Product Licenses**, **Security Audit**, **System backup**. Roles (permission sets) are managed through Shield.

### QMS

See [section 10](#10-qms). All of the following are complete Filament workspaces when QMS is licensed: Deviations, Investigations, CAPAs, Change Controls, Complaints, Internal Audits, Audit Findings, Risk Assessments, Supplier Qualifications, Management Reviews, Quality Metrics, Product Quality Reviews, Product Recalls, Product Returns, Laboratory OOS/OOT, Validation Master Plans, CSV Validation Projects, Equipment Assets, Equipment Qualifications, Calibrations, Preventive Maintenance, Schedule M Gap Assessments, Site Master Files, Competency curricula, User competencies, Batch Release, IT / CSV Incidents, and Schedule M Readiness.

### AI Management

**Document Draft Assistant** and **AI Executions**.

## 6. Users, roles, and permissions

The name “Super Admin” does not grant unrestricted access. Access comes from assigned roles and permissions.

For the local administrator:

1. Open **Core · Identity & Access → Users**.
2. Open `admin@example.com`.
3. Confirm the `sop administrator` role.
4. Save, sign out, and sign back in.

Module seeders grant their permissions to `sop administrator`.

Typical DMS roles: SOP Maker, SOP Checker / Approver, Document Controller, GMP Record Executor / Log Maker, Production Supervisor, QA Reviewer, SOP Administrator.

Example permissions:

| Permission | Capability |
| ---------- | ---------- |
| `ViewAny:SopDocument` | See the controlled-document list |
| `Submit:SopDocument` | Submit a draft document for approval |
| `Publish:SopTemplate` | Publish an approved template version |
| `ViewAny:Deviation` | See QMS deviations |
| `Investigate:Deviation` | Progress a deviation investigation |
| `Approve:ChangeControl` | Approve a change control |
| `VerifyEffectiveness:Capa` | Record whether a CAPA was effective |
| `View:QualityMetrics` | Open Quality Metrics and Schedule M Readiness |
| `Release:CsvValidationProject` | Make the independent, signed QA release decision |

Use different named accounts for authoring, checking, approving, execution, production review, and QA release when separation of duties applies.

## 7. Core · Identity and Access

### 7.1 Organization profile

Configure legal name, timezone (default `Asia/Kolkata`), date / date-time / time display formats, address, contact, regulatory identifiers, and logo.

Calendar dates such as effective dates use the organization timezone. Changing display formats changes presentation only; stored timestamps are not rewritten.

### 7.2 Users

Create named accounts with department, designation, and roles.

- **Reset password** — temporary credential; the user must change it.
- **Deactivate / Reactivate** — deactivate unused accounts. Do not delete GxP users.
- **Unlock** — clear a lockout with a signed reason.

### 7.3 System backup

**System backup** writes SHA-256 checked archives of the PostgreSQL database and the `local` / `public` file disks (scheduled and on demand). Create and restore run on the queue worker. Restore is a signed full overwrite of this instance (password re-entry plus reason).

This **supports** recoverability. Keep off-site copies; prefer restore drills on a non-production host. Encryption at rest of backup media remains a site control.

### 7.4 Security audit

Append-only register of login, lockout, password, role, electronic-signature challenge, and backup events.

## 8. Electronic signatures and audit history

Consequential lifecycle decisions require an attributable reason. Approvals, rejections, returns, and many QMS closures also capture a canonical electronic signature: signer, meaning, UTC timestamp, reason, IP address, user agent, and a SHA-256 integrity hash. The current password is the second identification component when a signature challenge is presented.

Use your own named account. A hidden action is usually a permission, status, or separation-of-duties gate — not a UI error.

## 9. DMS

Everyday DMS workflow. Full document-type matrix and BMR/BPR checks: [DMS Process Workflow Guide](./DMS_PROCESS_WORKFLOW_GUIDE.md).

### 9.1 Configure master data

Before creating documents, configure organization, departments, users, roles, document categories and types, statuses, regulation tags, approval decisions and step types, number series, SOP roles and workflows, print templates, and PDF access policies (**Core · Identity & Access** and **DMS Settings**).

Example QA workflow:

```text
Workflow name: QA SOP Approval
Department: Quality Assurance (else the active global SOP workflow)
Steps:
  1. SOP Checker — Review — mandatory
  2. SOP Approver — Approve — mandatory
```

Each mandatory step in a submission cycle must be decided by a different user. The author or submitter cannot decide any step.

### 9.2 Create a template

1. Open **DMS → Document Templates**.
2. Create a template (or **Create with AI** when AI is enabled).
3. Enter name, document type, category, department, and regulation tags.
4. Add a draft version, sections, and variables.
5. Select **Submit for Review** with an attributable reason.
6. Complete steps from **Template Approval Queue** or **My Approval Queue**.
7. A user with publish permission selects **Publish Approved Version**.

Template editing is frozen after submission. Rejected or returned versions become editable. If AI is disabled, complete the template manually.

Published templates: **Published → Obsolete → Archived → Retention completed → Destroyed**.

#### Example: Deviation Management SOP template

```text
Name: Deviation Management SOP
Document type: SOP
Category: QMS Core
Department: Quality Assurance
Regulation tags: WHO GMP, US FDA 21 CFR 210/211
```

Suggested sections: Purpose, Scope, Responsibilities, Definitions, Procedure, Investigation and root-cause analysis, CAPA requirements, Records and retention.

### 9.3 Create a controlled document

1. Open **DMS → Controlled Documents**.
2. Create from a **published** template.
3. Enter title, owner, proposed dates, regulation tags, and variables.
4. For every type except SOP, Policy, and Manual, select an effective referenced SOP when required.
5. Complete controlled content. For tables and checklists, add **Execution fields** on the master; do not enter live execution data.
6. Use **Lock for Editing** when exclusive editing is required.
7. Select **Submit for Approval**.

#### Example

```text
Template: Deviation Management SOP
Title: Handling Manufacturing Deviations
Owner: QA Manager
Proposed effective date: 01-Aug-2026
Review date: 01-Aug-2028
```

### 9.4 Review and approve

1. Open **My Approval Queue** (or **Approval Queue** / **Template Approval Queue**).
2. Filter by **Module** and **Approval Type** if needed.
3. Open the pending item. **Review** opens the owning record; decisions stay on that module's signed workflow.
4. **Approve**, **Return for Correction**, or **Reject Submission** with a reason.

Every workflow step in the same cycle must be decided by a different user.

When all mandatory steps are approved, the document becomes **Approved**. It is not yet available for operational use.

### 9.5 Training and Make Effective

SOP, Policy, and Manual require training before effectiveness by default (configurable on **Document Types**).

1. Open the **Approved** document.
2. Document Control assigns trainees on **Required training**.
3. Each trainee completes a read-and-understand acknowledgement.
4. Document Control selects **Make Effective**, confirms the effective date (not earlier than today in the organization timezone), and records a release reason.
5. If the date is today, status becomes **Effective** immediately. A future date stays Approved until that date.
6. A prior effective version in the same series becomes **Superseded**.

Controlled copies can be issued only after the document is Effective.

### 9.6 Revise

1. Open an Approved, Effective, or Obsolete document.
2. Select **Create Revision** and enter a reason.
3. Edit the new draft and repeat approval, training, and Make Effective.

Only one draft revision is allowed in a series at a time. Approved QMS change controls that require a document revision should use **Create Draft Revision** on the change-control document impact, then continue this same DMS path.

### 9.7 Print, issue, and control copies

- Direct **Print / PDF** is only for approved or effective documents that do **not** require controlled-copy issuance.
- For issuable masters: **Issuable Documents** → **Issue Controlled Copy**.
- Copy type: **Read-only reference copy**, or **Writable GMP execution record** (Form, Log, Checklist, BMR, BPR).
- Enter recipient, optional location. For BMR/BPR enter batch and product. For logs enter frequency, period, and supervisor.
- Print or view from **Issuance Register** (**Print Copy** / **View Controlled Copy**) on an **Active** issuance.
- Recalled or destroyed copies cannot be printed or viewed as controlled copies.
- **Recall** or **Destroy** with a recorded reason: **Active → Recalled**; **Active/Recalled → Destroyed**.

### 9.8 Formats and execution


| Document type | Format | Issued copy |
| ------------- | ------ | ----------- |
| SOP, Policy, Manual | Text | Reference |
| Report, Protocol, Specification, Validation | Structured table | Reference |
| Form, BMR, BPR | Controlled form | Reference or writable execution |
| Log | Repeating log | Reference or writable execution |
| Checklist | Checklist | Reference or writable execution |
| Annexure | Attachment package | Reference |


The approved master is reusable. Execution data is never entered on the master. A writable issuance snapshots the approved version into a separate execution record that later master revisions cannot alter.

Checklist, BMR, and BPR required items need independent verification (verifier ≠ completer). `N/A` needs an explanation. Logs and configured checklists go through supervisor review. BMR/BPR go through production review then independent QA **Release batch** / **Reject batch**.

Execution evidence/annexures are uploaded on the execution record (private, SHA-256). Missing or tampered evidence must be resolved before the record is relied upon.

Master lifecycle:

```text
Draft → Under review → Approved → Training (SOP, Policy, Manual by default)
→ Effective → Revision/Superseded or Obsolete → Archived → Retention completed → Destroyed
```

### 9.9 Execution-record procedure

1. Open **GMP Execution Records** or **Open Execution Record** from Issuance Register.
2. **Begin execution**. Enter contemporaneous responses.
3. Mark sections Completed or validly Not applicable.
4. Independent verification where required.
5. **Complete and submit**. The notification lists any incomplete item.
6. Supervisor **Complete supervisor review** (must not be the executor).
7. BMR/BPR: QA disposition; QA must differ from executor and production reviewer.

### 9.10 Retention

**Mark Obsolete** → **Archive** → **Complete Retention** → **Destroy** (mandatory reason). Document Controller (or equivalent) permission required.

### 9.11 Import batches

From **Controlled Documents**, **Import documents** or **Import document ZIP**. Track status on **Import Batches**, then **Create controlled document** (or bulk) and complete the normal draft approval path.

### 9.12 Reports and knowledge

Use **DMS · Reports** for register, where-used, periodic review, pending approvals, issuance, and GMP executions.

**Knowledge Library** lists published guides. From a closed CAPA, change control, or PQR, **Capture lesson** writes to **Lessons Learned**.

### 9.13 Completion checks

Before submitting a writable master: at least one section; execution fields on every required table/checklist section; no live execution responses on the master.

Before **Complete and submit**: all required responses; sections Completed or justified N/A; independent verification where required.

## 10. QMS

Available when `QUALIGXP_MODULES` includes `qms` and the user has QMS permissions. Every consequential transition requires an attributable reason and append-only history. Many closures also receive canonical electronic signatures.

These are **complete Filament workspaces**, not dormant backend-only records. If a QMS menu is missing, check licence, permissions, and that you signed in again after seeding — not whether the UI was built.

### How records connect

```text
Complaint ──► Deviation ──► Investigation ──► CAPA
     │              ▲
     └──► Product Recall
Internal Audit ──► Audit Finding ──► CAPA
Laboratory OOS ──► Deviation
Change Control ──► DMS document revision
Calibration out of tolerance ──► Deviation
PQR / Management Review ──► CAPA or Change Control
```

Use handoff actions (for example **Open Deviation** on a complaint) instead of duplicating facts.

### 10.1 Deviations

**QMS → Deviations**. Unplanned departure from an approved instruction, process, specification, or expected condition. Severity: Minor, Major, Critical.

```text
Draft → Open → Under Investigation → Investigation Complete
→ CAPA Required or Effectiveness Review → Closed
```

Reject from Open. Cancel from Draft, Open, or Under Investigation.

1. Create the draft with contemporaneous facts (what, when, where, immediate action).
2. **Submit** → Open.
3. **Begin Investigation**. Link an **Investigation** for root-cause work.
4. **Complete Investigation** (blocked while required linked investigations are incomplete).
5. **Require CAPA** when actions are necessary.
6. **Begin Effectiveness Review**, then **Close**.

#### Example

```text
Title: Granulation temperature exceeded approved range
Severity: Major
Occurred at: 26-Jul-2026 10:15
Discovered at: 26-Jul-2026 10:30
Department: Production
Description: Product temperature reached 32°C against 25–30°C for about eight minutes.
Immediate action: Process paused, material segregated, QA notified.
```

Record facts here; keep conclusions on the investigation.

### 10.2 Investigations

**QMS → Investigations**.

```text
Draft → In Progress → Pending Review → Completed
```

**Begin Investigation** → record root cause and conclusion → **Submit for Review** → **Complete** or **Return to Investigation**.

#### Example

```text
Deviation: Granulation temperature exceeded approved range
Methodology: 5 Whys and alarm-history review
Lead investigator: Production Engineering Lead
Root cause: Temperature probe calibration drift delayed cooling response.
```

### 10.3 CAPAs

**QMS → CAPAs**. Type: Corrective, Preventive, or Corrective and Preventive.

```text
Draft → Planned → In Progress → Pending Effectiveness → Effective → Closed
```

**Approve Plan** → **Begin Implementation** → **Complete Implementation** → **Mark Effective** or **Mark Ineffective**. Ineffective returns to implementation. **Close** only after Effective. Optionally **Capture lesson**.

#### Example

```text
Type: Corrective and Preventive
Source: Granulation temperature deviation
Action plan:
1. Replace and recalibrate the temperature probe.
2. Add monthly probe-drift verification.
3. Retrain granulation operators on alarm escalation.
Effectiveness check: Three consecutive batches and the next three monthly records.
```

### 10.4 Change controls

**QMS → Change Controls**. Planned change to a process, system, facility, or controlled document.

```text
Draft → Submitted → Under Review → Approved → Implementing
→ Effectiveness Review → Closed
```

1. Create the draft. While Draft, add **Document impacts** (Create, Revise, Retire, No change).
2. **Submit for Review** → **Begin Review** → **Approve** or **Reject**.
3. On an approved **Revise** impact, **Create Draft Revision** (creates one traced DMS draft and moves the change control to Implementing).
4. **Begin Effectiveness Review**, then **Close**.
5. Optionally **Print Investigation** (change-control report template) or **Capture lesson**.

#### Example

```text
Title: Reduce deviation reporting time to 24 hours
Department: Quality Assurance
Reason: Internal audit IA-2026-014 identified delayed escalation.
Document impact: Revise SOP Handling Manufacturing Deviations
```

### 10.5 Complaints

**QMS → Complaints**. Sources: patient, healthcare professional, distributor, regulator, internal, other. Types: product quality, adverse event, medical information, distribution, other.

```text
Draft → Received → Under Assessment → Under Investigation
→ Response Pending → Closed
```

**Mark Received** → **Begin Assessment**. For product-quality complaints, **Open Deviation** when a quality event is required. **Begin Investigation** or **Await Response**. **Return to Investigation** if the response is incomplete. **Close** from Response Pending. **Open Product Recall** for a market path — this is not DMS copy recall.

#### Example

```text
Source: Healthcare professional
Type: Product quality
Product: Paracetamol 500 mg tablets
Batch: PT-24018
Description: Two bottles from pharmacy stock contained broken tablets.
```

### 10.6 Internal audits and audit findings

**QMS → Internal Audits** and **Audit Findings**. Audit types: internal, process, system, product, supplier, regulatory readiness.

Audit:

```text
Draft → Scheduled → In Progress → Reporting → Follow-up → Closed
```

Finding disposition:

```text
Open → Response Pending → Under Verification → Accepted → Closed
```

**Schedule** → **Begin Audit** → raise findings → **Request Response** / **Submit Response** / **Accept Response** or **Return for Rework**. **Open CAPA** for actionable nonconformities. **Begin Reporting** → **Begin Follow-up** if needed. **Close** the audit only when every finding is closed, rejected, or cancelled.

#### Example

```text
Audit: IA-2026-014 Warehouse temperature mapping
Finding: Escalation from warehouse to QA exceeded 48 hours
Severity: Major
Handoff: CAPA plus Change Control on the deviation SOP
```

### 10.7 Risk assessments

**QMS → Risk Assessments**. Types: process, product, equipment, supplier, change, computerized system, other.

```text
Draft → In Review → Approved → Mitigation In Progress → Monitoring → Closed
```

**Submit for Review** → **Approve** / **Reject** / **Return to Draft** → **Begin Mitigation** → **Begin Monitoring** (residual scores; residual RPN must not exceed initial) → **Close**. Link assessments to change controls, deviations, and related quality records where required.

#### Example

```text
Type: Equipment
Hazard: Temperature probe drift is not detected before a batch excursion.
Initial severity/probability/detectability: 5 / 3 / 3
Mitigation: Monthly probe-drift verification and alarm-response retraining.
Residual: 5 / 1 / 1
```

### 10.8 Supplier qualifications

**QMS → Supplier Qualifications**. Categories include raw material, packaging, contract manufacturer, laboratory, logistics, service provider, software/technology.

```text
Draft → Under Assessment → Audit Required (when needed)
→ Qualified or Conditionally Qualified
→ Suspended, Expired, or Disqualified
```

**Begin Assessment** → **Require Audit** if needed → **Qualify** or **Conditionally Qualify**. **Suspend**, **Mark Expired**, or **Disqualify** when no longer valid. Re-enter assessment from Qualified, Conditional, Suspended, or Expired.

### 10.9 Management reviews

**QMS → Management Reviews**. Types: annual, semi-annual, quarterly, site, product, ad hoc.

```text
Draft → Scheduled → In Progress → Minutes Pending → Actions Pending → Completed
```

**Assemble suggested inputs** pulls quality-system inputs. **Schedule** → **Begin Review** → **Prepare Minutes** → **Issue Minutes** → **Complete**. **Return to Minutes** if the action summary is incomplete. **Download pack** for the meeting evidence package.

### 10.10 Quality metrics

**QMS → Quality Metrics** (`View:QualityMetrics`). Lifecycle counts and overdue open work across deviations, investigations, CAPAs, complaints, audits, findings, risk, suppliers, management reviews, PQRs, recalls, returns, laboratory OOS, VMPs, equipment qualification/calibration/maintenance, Schedule M gap assessments, and site master files. A weekly snapshot — not a substitute for record-level review.

### 10.11 Product quality reviews (PQR)

**QMS → Product Quality Reviews**.

```text
Draft → In Progress → Under Review → Approved → Closed
```

**Begin Review** → capture period inputs → **Submit for Review** → **Approve** (conclusions required) → **Close**. Spawn CAPA or change control when the PQS requires it. **Capture lesson** if useful.

#### Example

```text
Product: Paracetamol 500 mg tablets
Period: 01-Jan-2026 to 31-Dec-2026
Inputs: 48 commercial batches, 3 minor deviations, 1 complaint, 0 recalls
Conclusion: Process remains in a state of control.
```

### 10.12 Product recalls

**QMS → Product Recalls**. Distinct from DMS controlled-copy recall. Types: market, mock, simulated. Classifications: Class I, II, III, or not classified.

```text
Draft → Initiated → Risk Classified → Notification In Progress
→ Execution In Progress → Effectiveness Check → Closed
```

**Initiate** → **Classify Risk** → **Begin Notification** → **Begin Execution** → **Verify** → **Close**. Can be opened from a complaint.

### 10.13 Product returns

**QMS → Product Returns**.

```text
Draft → Received → Under Quarantine → Disposition Pending → Closed
```

Dispositions: quarantine, rework, destroy, release to stock, pending. **Mark Received** → **Place in Quarantine** → **Dispose** → **Close**.

### 10.14 Laboratory OOS / OOT

**QMS → Laboratory OOS/OOT**.

```text
Draft → Phase I → Phase II or Invalidation Proposed → Confirmed → Closed
```

**Begin Phase I** (record phase outcome) → **Begin Phase II**, **Propose Invalidation**, or **Confirm OOS/OOT**. **Open Deviation** when a confirmed failure requires a quality event. **Close** when investigation and deviation gates are satisfied.

#### Example

```text
Type: OOS
Test: Assay
Specification: 95.0–105.0%
Result: 93.8%
Phase I: Analyst error not identified; method and instrument checks passed.
Phase II: Confirmed; deviation opened for batch impact.
```

### 10.15 Validation master plans

**QMS → Validation Master Plans**.

```text
Draft → Active → Under Revision → Active again, or Retired
```

**Activate** → **Begin Revision** when the plan must change → **Activate** again, or **Retire**. Execute computerized-system work in CSV projects and equipment work in Equipment Qualifications.

### 10.16 Computer system validation

**QMS → CSV Validation Projects**. Demonstrate that a computerized system is fit for intended GxP use and remains in a controlled, validated state.

```text
Draft → GxP Assessment → Planning → Specification → Testing
→ Deviation Resolution (when required) → Validation Review → Released
→ Periodic Review → Released, Revalidation, or Retired
```

Use separate named accounts. Do not run the entire validation as `admin@example.com`.

| Responsibility | Example user | Activity |
| -------------- | ------------ | -------- |
| Business owner | `process.owner@example.com` | Intended use, requirements |
| System owner | `system.owner@example.com` | Boundary, version, specifications |
| Test executor | `validator@example.com` | Execute tests, record actual results |
| Test reviewer | `validation.reviewer@example.com` | Independent review of executions |
| Quality releaser | `qa.approver@example.com` | Signed QA release |

The reviewer cannot be the executor. The QA releaser cannot be the creator, business owner, or system owner.

#### Example project

```text
System identifier: QUALIGXP-QMS
System name: QualiGxP Quality Management System
System version: 1.0.0
GxP criticality: High
Intended use: Create, approve, issue, revise, and retain controlled
pharmaceutical documents and quality records with attributable audit history.
Regulatory scope: 21 CFR Part 11; EU GMP Annex 11; site data-integrity procedures
Electronic records / signatures: Yes
Planned release: 30-Sep-2026
Next periodic review: 30-Sep-2027
```

Procedure:

1. Create the project (identifier, name, version, intended use, owners, GxP criticality). Number example: `CSV-2026-4F8A91CD`.
2. **Complete GxP Assessment** → **Begin Validation Planning**. Record strategy, planned release date, and periodic-review frequency. Link the approved validation-plan controlled document where available.
3. Add versioned **Requirements**. Approve them. Every GxP, High, or Critical requirement must be linked to an approved test with a passing, independently reviewed execution before release.
4. Add **Specifications** and **Risks**. Residual risk must be documented, accepted, and no greater than initial risk.
5. Create IQ/OQ/PQ/UAT/security/backup-restore tests, approve them, and trace to requirements.
6. Execute numbered runs. Upload evidence. Independent **Review**. Failed or blocked completed runs must link a deviation; do not overwrite a failed run.
7. Record release baseline and validation summary. **Export validation report** for ALCOA+ traceability and a printable summary.
8. **Begin Validation Review**. Independent QA **QA Release** with a specific reason.

Release is blocked when strategy, baseline, summary, next review date, approved requirements/specifications, requirement-to-test links, passing reviewed executions, or residual-risk acceptance are incomplete, or when the releaser is not independent.

After release: process changes through Change Control; **Begin Periodic Review** when due; choose continue validated use, require revalidation, or retire. Never alter a reviewed execution to represent a later system state.

Requirement example:

```text
Identifier: URS-001
Statement: The system shall retain an attributable and append-only history
of consequential lifecycle decisions.
Acceptance criteria: History identifies record, signer, decision meaning,
timestamp, reason, IP address, and signature-integrity hash.
Criticality: Critical; GxP relevant: Yes
```

### 10.17 Equipment assets, qualification, calibration, and maintenance

**Equipment Assets** — categories: production, packaging, laboratory, utility, facility, other. **Activate** / **Deactivate** / **Decommission**.

**Equipment Qualifications** — DQ, IQ, OQ, PQ, process validation, cleaning validation.

```text
Draft → In Progress → Under Review → Approved
```

**Begin Execution** → **Submit for Review** → **Approve**, **Return to Execution**, or **Mark Failed** (linked deviation required).

**Calibrations**

```text
Scheduled → In Progress → Completed
```

**Begin Performance** → **Complete** or **Mark Out of Tolerance** (link a deviation) → independent **Verify**.

**Preventive Maintenance**

```text
Planned → In Progress → Completed
```

**Begin Work** → **Complete**.

Critical assets with overdue calibration can block BMR/BPR QA approval when the calibration gate is enabled.

#### Example

```text
Asset: GRAN-01 High-shear granulator
Qualification: OQ-GRAN-01-2026
Calibration: Temperature probe TP-014, due 01-Sep-2026
Maintenance: Monthly lubrication PM-GRAN-01
```

### 10.18 Schedule M gap assessments

**QMS → Schedule M Gap Assessments**. Structures Part I PQS readiness work for revised Schedule M / CDSCO expectations. This does **not** certify facility Premises or Plant readiness.

```text
Draft → In Progress → Under Review → Approved → Closed
```

Item statuses: not assessed, compliant, partial, gap, not applicable.

**Begin Assessment** → update each clause with notes and evidence → **Submit for Review** → **Approve** → **Close**. **Download Inspector Pack** (JSON evidence summary) when permitted.

### 10.19 Site master files

**QMS → Site Master Files**.

```text
Draft → In Review → Published → Retired
```

**Submit for Review** → **Publish** → **Retire** when replaced. Keep the published SMF aligned with the current gap assessment.

### 10.20 Competency curricula and user competencies

**Competency curricula** define role requirements (often linked SOPs). Assign users on **User competencies**.

Statuses: Assigned → Trained (after **Verify**) → Overdue / Expired. **Refresh** when retraining is due. This **supports** personnel evidence; it does not replace site training procedures.

### 10.21 Batch release

**QMS → Batch Release**. Independent quality / QP-style certification, separate from BMR/BPR execution QA disposition.

```text
Draft → Under Review → Released or Rejected
```

**Submit for independent release** → **Certify / release batch** or **Reject batch**. The releaser must differ from the people who executed and production-reviewed the BMR/BPR.

### 10.22 IT / CSV incidents

**QMS → IT / CSV Incidents**. Computerized-system / cybersecurity incidents (Annex 11.13 operational support).

```text
Open → Investigating → Resolved → Closed
```

**Begin investigation** → **Resolve** (signed) → **Close** (independent signed decision). Raise a Deviation or CAPA when product quality or data integrity is affected.

### 10.23 Schedule M readiness

**QMS → Schedule M Readiness** (`View:QualityMetrics`). Snapshot of latest gap-assessment compliance percentage, overdue calibrations, open critical findings, and open/overdue deviations and CAPAs. Not certification of Premises or Plant readiness.

### 10.24 Worked cross-module example

1. Warehouse delay → **Deviation DEV-2026-018** (Major).
2. **Investigation INV-2026-011** (probe drift).
3. **CAPA-2026-007** (replace probe, monthly verification, retrain).
4. **Internal audit IA-2026-014** finding; **Open CAPA** if a separate action is needed.
5. **Change Control CC-2026-004** with **Revise** impact on the deviation SOP → **Create Draft Revision**.
6. DMS SOP v2 is approved, trained, and **Make Effective**.
7. CAPA **Mark Effective** → **Close**. **Capture lesson**.
8. Next **PQR** and **Management Review** include the deviation, CAPA, and change as inputs.

## 11. AI module

When `QUALIGXP_MODULES` includes `ai`:

### 11.1 Template assistance

On **Document Templates**, **Create with AI** can start regulated-template generation. Section actions may polish, shorten, or assist content. Generated content must still be reviewed and approved by authorized personnel.

### 11.2 Document Draft Assistant

**AI Management → Document Draft Assistant**. Select a published template and owner, converse, preview sections, then create a controlled-document draft. The draft still follows the normal DMS approval workflow.

### 11.3 AI Executions

**AI Management → AI Executions**. Operational monitoring of job status, provider, use case, and attempts.

AI output is assistance, not an approval decision.

## 12. Table actions and pinned action menus

When a row has more than one action, they are grouped in a three-dot menu at the end of the row. The action column is pinned while you scroll wide tables.

Actions still depend on module, permission, record status, and separation of duties. If only one action is available, it may appear as a direct button.

After a local frontend change:

```powershell
npm.cmd run build
```

Then `Ctrl + F5`. Use `npm.cmd run dev` during continuous development.

## 13. Audit and data-integrity practices

- Enter a specific reason for every lifecycle decision.
- Never share accounts for approval, verification, or QA release.
- Do not edit database rows to bypass a workflow.
- Upload evidence on the relevant record so integrity hashes are captured.
- Use revisions instead of overwriting approved documents.
- Record facts in deviations; keep conclusions in investigations.
- Do not close CAPAs without documented effectiveness evidence.
- Keep the CSV release baseline aligned with the deployed application.
- Preserve failed validation runs; resolve them through linked deviations.
- Deactivate unused users; do not delete GxP accounts.
- Keep DMS copy recall separate from QMS market product recall.
- Site-owned controls remain outside the application: NTP, TLS, off-site backup copies, disaster-recovery drills, optional MFA.

## 14. Troubleshooting

### QMS or AI menus are missing

Confirm `.env`:

```env
QUALIGXP_ENTITLEMENT_SOURCE=environment
QUALIGXP_MODULES=dms,qms,ai
```

Then migrate, seed, `optimize:clear`, confirm the user's role, and sign out and back in.

### Product Licenses has no Add button

Expected. The screen is read-only. Local access is `.env`; production uses an issuer-signed payload.

### An action button is missing

Shown only when the module is enabled, the user has permission, the record status allows the action, and separation-of-duties / department rules are satisfied.

### Make Effective is disabled

Required training is incomplete, the user lacks Document Controller permission, or the document is not Approved.

### QA Release is blocked

Check validation strategy, baseline, summary, next review date, approved requirements and specifications, requirement-to-test links, latest passing independently reviewed executions, residual-risk acceptance, linked deviations for failed runs, and whether the current user is creator, business owner, or system owner. Do not bypass the gate in the database.

### Import, backup, PDF, or AI jobs do not finish

Queue worker must be running. Gotenberg must be running for PDF work.

### Frontend changes are not visible

`npm run dev` or `npm run build`, then hard-refresh.

## 15. Quick daily checklists

**Authors:** correct published template; complete required fields; verify owner, dates, department, tags; submit once ready; revise instead of editing effective content.

**Document Control:** publish templates; complete training then **Make Effective**; issue/print/recall/destroy only through issuance; drive retention with reasons.

**Quality reviewers:** complete attributable records; verify evidence and links; specific reasons; separation of duties; close only after gates.

**GMP executors / supervisors:** enter data on the issued execution, not the master; explain N/A; different users for verification and review; reconcile materials before QA disposition.

**Validation teams:** precise intended use and baseline; trace GxP/High/Critical requirements; preserve failed runs; separate executor, reviewer, and QA releaser; periodic review after release.

**Quality-system owners:** weekly **Quality Metrics** and **Schedule M Readiness**; close overdue deviations, CAPAs, calibrations, and findings; feed PQR and management review from live QMS records; keep the published Site Master File current.

## Related documents

- [DMS Process Workflow Guide](./DMS_PROCESS_WORKFLOW_GUIDE.md)
- [WINDOWS_INSTALLATION.md](./WINDOWS_INSTALLATION.md)
- In-app **Knowledge Library**
- [USER_GUIDE.md](./USER_GUIDE.md) — document category and type selection examples
