---
name: docupharma-product-modules
description: Continue or review DocuPharma's incremental split into sellable DMS, QMS, and AI modules. Use when implementing product entitlements, module middleware, license-aware Filament resources, module permissions or seeders, domain boundaries, shared approvals or electronic signatures, QMS bounded contexts, or when resuming the modularization roadmap in a new chat.
---

# DocuPharma Product Modules

Continue the product split without destabilizing the existing pharmaceutical DMS. Keep one codebase and database schema; control commercial access with server-side entitlements.

## Resume safely

1. Read `AGENTS.md` completely.
2. Read [references/roadmap.md](references/roadmap.md).
3. Inspect `git status --short` and the relevant current files. Preserve unrelated user changes.
4. Inspect completed implementation rather than assuming the roadmap is current.
5. Use Laravel Boost `search-docs` before code changes. If unavailable, use version-matched official Laravel 13 and Filament 5 documentation.
6. Work on only the next incomplete phase or a narrower phase explicitly requested by the user.

## Maintain product boundaries

- Treat DMS as the required core.
- Treat QMS and AI as optional entitlements that depend on DMS.
- Keep document control, versioning, approvals, audit trails, issuance, and retention in DMS.
- Put deviations, investigations, CAPA, change control, complaints, audits, risk, supplier quality, and management review in QMS.
- Keep shared identity, authorization, workflow primitives, electronic signatures, and audit infrastructure reusable.
- Enforce entitlements on the server. Hiding Filament navigation alone is insufficient.
- Prefer all migrations installed with dormant tables over separate application branches.

## Implement each phase

1. State the phase and affected boundary.
2. Search version-specific documentation.
3. Inspect sibling conventions and existing tests.
4. Implement the smallest deployable slice.
5. Add focused Pest coverage for enabled, disabled, and direct-access behavior.
6. Run `vendor/bin/pint --dirty --format agent`.
7. Run focused tests, then relevant regressions.
8. Update the roadmap only after verification.

Do not mark a phase complete if enforcement covers only navigation but not routes, policies, actions, services, jobs, or scheduled commands relevant to that phase.

## Commercial configuration

Use `DOCUPHARMA_MODULES` for the current single-install entitlement source:

```env
DOCUPHARMA_MODULES=dms
DOCUPHARMA_MODULES=dms,ai
DOCUPHARMA_MODULES=dms,qms,ai
```

Keep consumers dependent on `ModuleManager`, not directly on environment variables, so signed-license or organization-based providers can replace configuration later.
