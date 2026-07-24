# DocuPharma product modularization roadmap

## Verified checkpoint

- Module enum, dependency rules, configuration, manager, exception, and `module:*` middleware exist.
- DMS protects the current Filament panel and document print route.
- AI observability, template controls, actions, and queued jobs are entitlement-aware.
- DMS-only template creation does not dispatch AI generation.
- Database seeding is module-aware.
- AI and QMS extension seeders exist.
- Focused entitlement and document-version tests passed at the checkpoint.

Always verify these statements against the worktree before relying on them.

## Ordered phases

1. **Entitlement foundation — completed**
   Centralize DMS, QMS, and AI enablement with dependency enforcement.

2. **Runtime enforcement — completed**
   Protect current DMS and AI routes, Filament surfaces, actions, jobs, and widgets.

3. **Permission and seeder split — in progress**
   Extract legacy DMS permissions and role assignment from `SopModuleSeeder` into Core and DMS seeders. Keep AI and future QMS permissions in their module seeders. Test DMS-only and DMS+AI seeding.

4. **Domain boundaries**
   Establish Shared, DMS, QMS, and AI namespaces incrementally. Avoid a large rename. Introduce generic controlled-document language before renaming persistent tables.

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

Complete phase 3 by extracting Core and DMS permission definitions and role grants from `database/seeders/SopModuleSeeder.php`, while preserving existing permission names and customer behavior.
