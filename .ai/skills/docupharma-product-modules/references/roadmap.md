# DocuPharma product modularization roadmap

## Verified checkpoint

- Module enum, dependency rules, configuration, manager, exception, and `module:*` middleware exist.
- DMS protects the current Filament panel and document print route.
- AI observability, template controls, actions, and queued jobs are entitlement-aware.
- DMS-only template creation does not dispatch AI generation.
- Database seeding is module-aware.
- Core, DMS, AI, and QMS module seeders own their permission boundaries and grants.
- The legacy `SopDocument` model implements the DMS `ControlledDocument` contract without persistence renames.
- Document revision uses canonical DMS action and service namespaces with legacy compatibility entry points.
- Document issuance, recall, and controlled-copy destruction use canonical DMS namespaces with legacy compatibility entry points.
- Document and template retention lifecycle actions use canonical DMS namespaces with legacy compatibility entry points.
- Document locking uses canonical DMS namespaces across Filament and workflow consumers with legacy compatibility entry points.
- Template publishing uses canonical DMS namespaces with legacy compatibility entry points.
- Controlled-document generation uses canonical DMS namespaces for SOP and log creation with legacy compatibility entry points.
- Document numbering, SOP-reference resolution, and template-variable resolution use canonical DMS services with legacy compatibility entry points.
- Audit logging uses a canonical Shared service across DMS and workflow consumers with the legacy SOP entry point and persistence retained.
- Document activation uses the canonical DMS service from approval workflows with the legacy SOP entry point retained.
- `SopDocument` implements the Shared `ApprovableSubject` contract for reusable workflow identity and attribution without approval persistence changes.
- Workflow submission authorization consumes `ApprovableSubject`, allowing non-DMS subjects to use the existing permission and ownership rules without changing SOP approval persistence.
- Workflow selection consumes `ApprovableSubject` department metadata while retaining `SopWorkflow` as the existing persistence adapter and preserving department-specific precedence with global fallback.
- Shared workflow-definition and selector contracts now sit in front of the DMS `SopWorkflow` selection adapter, with the existing SOP engine return type and approval persistence retained.
- Workflow execution consumes Shared workflow-definition and step identity metadata while retaining `SopApproval` rows, status transitions, and submitted audit payloads.
- Approval-instance initialization is behind a Shared persistence contract, with the DMS adapter retaining `SopApproval` creation, resubmission reset behavior, and foreign keys.
- `SopApproval` implements the Shared approval-instance contract for reusable subject, workflow-step, decision, attribution, timestamp, and signature metadata without persistence changes.
- Approval decision entry points consume the Shared approval-instance contract, with an explicit SOP adapter guard retaining existing authorization and persistence behavior.
- Approval decision mutations are behind a Shared persistence contract, with the DMS adapter retaining `SopApproval` decision lookups, attribution, timestamps, comments, and signature persistence.
- Approval decision authorization is behind a Shared contract, with the DMS adapter retaining SOP permission, role, department, mandatory-step ordering, and separation-of-duties rules and the model method retained as a compatibility entry point.
- Approval decision outcomes are behind a Shared contract, with the DMS adapter retaining SOP status transitions, audit payloads, mandatory-completion checks, final activation, unlocking, and transactional rollback behavior.
- Workflow submission lifecycle handling is behind a Shared contract, with the DMS adapter retaining SOP draft validation, unlocking, under-review status, submitted audit payloads, and transactional rollback behavior.
- Workflow submission authorization is behind a Shared contract, with the DMS adapter retaining SOP permissions, administrator and maker roles, department scope, creator and owner rules, and the engine compatibility entry point.
- Workflow decision execution consumes and returns Shared approval instances without an engine-level `SopApproval` guard, while DMS adapters retain type validation and existing SOP actions retain their Filament-compatible return behavior.
- Workflow orchestration uses Shared approval decision-code vocabulary while retaining the existing DMS lookup codes, rows, foreign keys, model constants, and string adapter boundaries.
- Canonical workflow-engine ownership is in the Shared domain, with the legacy SOP service retained as a compatibility subclass and all product behavior supplied through Shared contracts and DMS adapters.
- Shared electronic-signature metadata now covers meaning, signer, timestamp, hash, reason, IP address, and user agent; `SopApproval` adapts its existing decision, approver, approval timestamp, signature hash, and comments while returning null for metadata not present in the existing approval schema.
- SOP approval decisions now persist request IP address and user agent through the Shared decision-persistence boundary, reset that context on workflow reinitialization, and expose it through the Shared electronic-signature metadata contract.
- Focused seeder, entitlement, and document-version tests passed at the checkpoint.

Always verify these statements against the worktree before relying on them.

## Ordered phases

1. **Entitlement foundation — completed**
   Centralize DMS, QMS, and AI enablement with dependency enforcement.

2. **Runtime enforcement — completed**
   Protect current DMS and AI routes, Filament surfaces, actions, jobs, and widgets.

3. **Permission and seeder split — completed**
   Extract legacy DMS permissions and role assignment from `SopModuleSeeder` into Core and DMS seeders. Keep AI and future QMS permissions in their module seeders. Test DMS-only and DMS+AI seeding.

4. **Domain boundaries — completed**
   Establish Shared, DMS, QMS, and AI namespaces incrementally. Avoid a large rename. Introduce generic controlled-document language before renaming persistent tables.
   - Slice 1 completed: Added the DMS `ControlledDocument` contract and adapted `SopDocument`.
   - Slice 2 completed: Moved document revision action and service ownership into the DMS domain while retaining legacy namespace compatibility.
   - Slice 3 completed: Moved document issuance lifecycle actions and service ownership into the DMS domain while retaining legacy namespace compatibility.
   - Slice 4 completed: Moved document and template retention lifecycle actions and service ownership into the DMS domain while retaining legacy namespace compatibility.
   - Slice 5 completed: Moved document locking actions and service ownership into the DMS domain while retaining legacy namespace compatibility.
   - Slice 6 completed: Moved template publishing action and service ownership into the DMS domain while retaining legacy namespace compatibility.
   - Slice 7 completed: Moved controlled-document generation action and service ownership into the DMS domain while retaining legacy namespace compatibility.
   - Slice 8 completed: Moved document numbering, SOP-reference resolution, and template-variable resolution into the DMS domain while retaining legacy namespace compatibility.
   - Slice 9 completed: Established canonical Shared ownership for audit logging while retaining the legacy SOP entry point and persistence.
   - Slice 10 completed: Moved approval-driven document activation into the DMS domain while retaining legacy namespace compatibility and supersession behavior.

5. **Reusable approval workflow — completed**
   Decouple approval subjects from `SopDocument` so controlled documents and future QMS records can share workflow infrastructure.
   - Slice 1 completed: Added the module-neutral Shared `ApprovableSubject` contract and adapted `SopDocument` without changing approval persistence.
   - Slice 2 completed: Decoupled workflow submission authorization from `SopDocument` by consuming `ApprovableSubject`, while retaining SOP workflow and approval persistence.
   - Slice 3 completed: Decoupled workflow selection from `SopDocument` by consuming `ApprovableSubject` department metadata, while retaining the existing SOP workflow persistence adapter.
   - Slice 4 completed: Added Shared workflow-definition and selector boundaries, adapted `SopWorkflow`, and moved department/global selection into the DMS adapter without changing SOP approval persistence.
   - Slice 5 completed: Adapted `SopWorkflowStep` to Shared step metadata and made workflow execution consume Shared workflow and step identities without changing `SopApproval`, document status, or audit persistence.
   - Slice 6 completed: Extracted approval-instance initialization behind a Shared persistence contract and retained `SopApproval` as the DMS adapter, including pending resets and duplicate prevention on resubmission.
   - Slice 7 completed: Adapted `SopApproval` to the Shared approval-instance contract without changing its table, relationships, authorization rules, decision methods, timestamp casts, or signature persistence.
   - Slice 8 completed: Made approve, reject, and return execution accept the Shared approval-instance contract while retaining an explicit `SopApproval` adapter guard and all existing decision behavior.
   - Slice 9 completed: Extracted approval decision mutations behind a Shared persistence contract while retaining `SopApproval` as the DMS adapter and preserving existing lookup, attribution, timestamp, comment, and signature behavior.
   - Slice 10 completed: Extracted approval decision authorization behind a Shared contract while retaining the DMS adapter's existing permission, role, department, ordering, and separation-of-duties behavior and the legacy model compatibility entry point.
   - Slice 11 completed: Extracted approval decision outcome orchestration behind a Shared contract while retaining SOP document statuses, audit events and payloads, mandatory completion checks, final activation, unlocking, and existing transaction boundaries.
   - Slice 12 completed: Extracted workflow submission lifecycle handling behind a Shared contract while retaining SOP draft validation, document unlocking, under-review status, submitted audit payloads, and the existing transaction boundary.
   - Slice 13 completed: Extracted workflow submission authorization behind a Shared contract while retaining SOP permissions, administrator and maker roles, department scope, creator and owner rules, and `canSubmit()` compatibility behavior.
   - Slice 14 completed: Removed the workflow engine's explicit `SopApproval` decision guard and return types, returning Shared approval instances while retaining DMS adapter validation and existing SOP action and Filament behavior.
   - Slice 15 completed: Introduced Shared approval decision-code vocabulary for workflow orchestration while retaining existing `approval_decisions` lookup rows, codes, foreign keys, DMS model constants, and string adapter boundaries.
   - Slice 16 completed: Moved canonical workflow-engine ownership into the Shared domain while retaining `App\Services\Sop\WorkflowEngineService` as a compatibility subclass and preserving existing action, Filament, and container-resolution behavior.

6. **Electronic signatures**
   Add a shared attributable signable record with meaning, signer, timestamp, hash, reason, IP address, and user agent.
   - Slice 1 completed: Added the module-neutral Shared electronic-signature metadata contract and adapted existing SOP approval signature metadata without persistence changes.
   - Slice 2 completed: Added IP address and user-agent fields to the original SOP approval schema, captured them for approval decisions through Shared orchestration, persisted them in the DMS adapter, and reset them on workflow reinitialization.

7. **Module-aware presentation**
   Separate DMS and QMS dashboards, settings, help, reports, navigation groups, and metrics.

8. **License lifecycle**
   Add signed activation, expiry, grace behavior, audit events, administrative visibility, and upgrade handling behind the existing entitlement contract.

9. **QMS Change Control**
   Build the first QMS bounded context and connect approved change controls to controlled document revisions.

10. **Deviation, Investigation, and CAPA**
    Build linked quality-event lifecycles using shared workflow, signatures, attachments, audit, and effectiveness checks.

11. **Remaining QMS and validation**
    Add complaints, audits, risk management, supplier quality, management review, reports, security regression tests, and release validation.

## Immediate next task

Continue phase 6 by extracting electronic-signature creation and canonical metadata hashing behind a Shared boundary while retaining existing approval behavior.
