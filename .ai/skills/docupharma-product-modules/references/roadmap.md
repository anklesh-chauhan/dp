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
- Focused seeder, entitlement, and document-version tests passed at the checkpoint.

Always verify these statements against the worktree before relying on them.

## Ordered phases

1. **Entitlement foundation — completed**
   Centralize DMS, QMS, and AI enablement with dependency enforcement.

2. **Runtime enforcement — completed**
   Protect current DMS and AI routes, Filament surfaces, actions, jobs, and widgets.

3. **Permission and seeder split — completed**
   Extract legacy DMS permissions and role assignment from `SopModuleSeeder` into Core and DMS seeders. Keep AI and future QMS permissions in their module seeders. Test DMS-only and DMS+AI seeding.

4. **Domain boundaries — in progress**
   Establish Shared, DMS, QMS, and AI namespaces incrementally. Avoid a large rename. Introduce generic controlled-document language before renaming persistent tables.
   - Slice 1 completed: Added the DMS `ControlledDocument` contract and adapted `SopDocument`.
   - Slice 2 completed: Moved document revision action and service ownership into the DMS domain while retaining legacy namespace compatibility.
   - Slice 3 completed: Moved document issuance lifecycle actions and service ownership into the DMS domain while retaining legacy namespace compatibility.
   - Slice 4 completed: Moved document and template retention lifecycle actions and service ownership into the DMS domain while retaining legacy namespace compatibility.
   - Slice 5 completed: Moved document locking actions and service ownership into the DMS domain while retaining legacy namespace compatibility.
   - Slice 6 completed: Moved template publishing action and service ownership into the DMS domain while retaining legacy namespace compatibility.
   - Slice 7 completed: Moved controlled-document generation action and service ownership into the DMS domain while retaining legacy namespace compatibility.
   - Slice 8 completed: Moved document numbering, SOP-reference resolution, and template-variable resolution into the DMS domain while retaining legacy namespace compatibility.

5. **Reusable approval workflow**
   Decouple approval subjects from `SopDocument` so controlled documents and future QMS records can share workflow infrastructure.

6. **Electronic signatures**
   Add a shared attributable signable record with meaning, signer, timestamp, hash, reason, IP address, and user agent.

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

Continue phase 4 by establishing the Shared audit boundary for reusable audit infrastructure, preserving the legacy SOP audit entry point and persistence.
