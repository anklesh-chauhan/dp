# Templates & Approvals

Reusable **Document Templates** define structure, sections, and variables for controlled documents. Templates must be reviewed, approved, and published before they are used to generate masters.

## Create and publish a template

1. Open **DMS → Document Templates**.
2. Create a template with name, document type, category, department, and regulation tags.
3. Add a draft version, sections, and variables.
4. Select **Submit for Review** and enter an attributable reason.
5. DocuPharma selects the active workflow for the template’s department, falling back to the active global SOP workflow.
6. Complete each step from **DMS → Template Approval Queue** (or **My Approval Queue** when the step is assigned to you).
7. After every mandatory step is approved, a user with publish permission selects **Publish Approved Version**.

Template editing is frozen after submission. A rejected or returned version becomes editable and may be corrected and resubmitted.

## Approval rules that always apply

- The template author or submitter cannot decide any approval step.
- Every step in a submission cycle must be decided by a different user.
- Approvals, rejections, and returns receive electronic-signature metadata and append-only audit history.
- The actor must hold the step’s assigned role and department access.

## Controlled-document approvals

1. From a **Draft** controlled document, select **Submit for Approval**.
2. The document becomes **Under review** and editing is locked.
3. Assigned reviewers act from **DMS → Approval Queue** / **My Approval Queue**.
4. A reviewer may:
   - **Approve** — advance the path; status stays Under review until every mandatory step is approved.
   - **Return for Correction** — send the document back to Draft.
   - **Reject Submission** — place the document in Rejected status.
5. When all mandatory steps are approved, the document becomes **Effective**. A prior effective version in the same series becomes **Superseded**.

## Separation of duties

- A user may be blocked from approving their own submission.
- A user who completed an earlier step cannot decide another step in the same approval cycle, even when that user holds multiple workflow roles.
- Independent verification, supervisor review, and QA disposition on execution records also require different users where configured.

## Checklist: template ready to publish

- Draft version, sections, and variables are complete.
- Template submitted and every mandatory approval step decided by distinct users.
- Author/submitter did not decide any step.
- **Publish Approved Version** completed by an authorized publisher.
