# Computerized System Operations

Use this guide for **Annex 11 operational** controls that QualiGxP supports in-product, plus site-owned items that must live in your validated procedures. QualiGxP **supports** 21 CFR Part 11, EU Annex 11, cGMP records, and ICH Q10 processes. It is **not certified** against those frameworks. The customer remains responsible for validated intended use.

## Where to work

| Screen | Use for |
| --- | --- |
| **IT / CSV Incidents** | Record computerized-system / cybersecurity incidents (Annex 11.13) with signed resolve, close, and cancel. |
| **Batch Release** | Independent quality / QP-style batch certification, separate from BMR/BPR execution QA disposition. |
| **CSV Validation Projects** | Include backup/restore as a *test type* in IQ/OQ/PQ protocols. The product does not run backups for you. |
| **Lessons Learned** | Capture knowledge from CAPA, change control, and product quality review into the knowledge library. |
| **Users** | Deactivate accounts (do not delete), reset passwords as temporary credentials, unlock lockouts with a signed reason. |

## Site-owned operational runbook (not performed by the application)

Record these as SOPs and as CSV OQ tests. QualiGxP cannot substitute for infrastructure procedures.

1. **Backup and restore** — Take encrypted backups of the application database and private file disks on a defined schedule. Restore to a non-production host and retain evidence. Use CSV Validation Projects with the backup/restore test type.
2. **Clock synchronization** — Keep application and database hosts on NTP. Electronic signature timestamps are stored in UTC.
3. **TLS** — Terminate HTTPS at the reverse proxy or Laravel Herd / production load balancer. Do not serve GxP sessions over plaintext HTTP.
4. **Disaster recovery / business continuity** — Maintain RTO/RPO, restore drills, and an emergency paper process. Annex 11.16 is a site claim.
5. **Idle timeout** — Default session lifetime is 20 minutes (`SESSION_LIFETIME` / `GXP_SESSION_LIFETIME`). Shorten further if your ER/ES SOP requires it.
6. **Optional MFA** — Set `GXP_MFA_REQUIRED=true` for open-system / remote access deployments. Users enroll an authenticator app from their profile.

## Typical IT incident path

1. Create an **IT / CSV Incident** with severity, category, description, and GxP impact.
2. Begin investigation, then resolve with an electronic signature (password re-entry).
3. Close with an independent signed decision, or cancel with a manage-permission signature.
4. Raise a Deviation or CAPA when the incident affects product quality or data integrity.

## Related topics

- **CSV Validation Projects** — intended use, risk, IQ/OQ/PQ including backup/restore.
- **CAPA / Change Control / Product Quality Review** — capture lessons learned after closure.
- **Electronic signatures** — every consequential decision requires the current password as the second identification component.
