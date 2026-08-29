---
paths:
  - 'app/Domain/QMS/**'
---

# Q M S

## Freeze used approval workflows and separate signers
Once a quality approval workflow has approval history, its definition and steps are immutable; only activation may be toggled. A user who submitted or already made a consequential decision in the current approval cycle cannot approve a later step, even when holding management permissions.
